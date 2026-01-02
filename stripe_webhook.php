<?php
require 'config.php';
require 'stripe_config.php';
require 'mailer.php';

declare(strict_types=1);

// -------------------------
// CONFIG
// -------------------------
$endpoint_secret = $STRIPE_WEBHOOK_SECRET ?? '';
$logFile = __DIR__ . '/webhook.log';

// -------------------------
// HELPERS
// -------------------------
function wlog(string $msg): void {
  $logFile = __DIR__ . '/webhook.log';
  @file_put_contents($logFile, date('c') . " " . $msg . "\n", FILE_APPEND);
}

function json_response(int $code, string $msg): void {
  http_response_code($code);
  echo $msg;
  exit;
}

function set_user_plan(PDO $pdo, int $user_id, string $plan, ?string $customer_id, ?string $subscription_id, ?string $status): void {
  $pdo->prepare("
    UPDATE users
    SET plan = ?, stripe_customer_id = ?, stripe_subscription_id = ?, stripe_status = ?
    WHERE id = ?
  ")->execute([$plan, $customer_id, $subscription_id, $status, $user_id]);
}

function find_user_by_customer(PDO $pdo, string $customer_id): ?int {
  $st = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ? LIMIT 1");
  $st->execute([$customer_id]);
  $r = $st->fetch();
  return $r ? (int)$r['id'] : null;
}

function get_user_by_id(PDO $pdo, int $user_id): ?array {
  $st = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
  $st->execute([$user_id]);
  $u = $st->fetch();
  return $u ?: null;
}

/**
 * Very simple idempotency:
 * Αν θες 100% σωστό, φτιάχνουμε table webhook_events.
 * Για MVP κρατάμε ένα file-based cache.
 */
function seen_event(string $event_id): bool {
  $f = __DIR__ . '/webhook_seen.txt';
  $line = $event_id . "\n";
  if (!file_exists($f)) {
    file_put_contents($f, $line);
    return false;
  }
  $content = file_get_contents($f);
  if (strpos($content, $line) !== false) return true;
  file_put_contents($f, $line, FILE_APPEND);
  return false;
}

// -------------------------
// VERIFY WEBHOOK
// -------------------------
if (!$endpoint_secret) {
  wlog("ERROR missing webhook secret");
  json_response(500, "Webhook secret missing");
}

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
  $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
  wlog("ERROR invalid payload");
  json_response(400, "Invalid payload");
} catch (\Stripe\Exception\SignatureVerificationException $e) {
  wlog("ERROR invalid signature");
  json_response(400, "Invalid signature");
} catch (\Throwable $e) {
  wlog("ERROR webhook verify exception: " . $e->getMessage());
  json_response(400, "Webhook error");
}

$event_id = $event->id ?? '';
$event_type = $event->type ?? 'unknown';

if ($event_id && seen_event($event_id)) {
  // Stripe κάνει retries — αγνόησε duplicate
  wlog("DUPLICATE {$event_type} {$event_id} ignored");
  json_response(200, "ok");
}

wlog("RECV {$event_type} {$event_id}");

// Save event in DB (audit)
try {
  $customer_id = null;
  $subscription_id = null;

  // best-effort extract ids
  $obj = $event->data->object ?? null;
  if ($obj) {
    $customer_id = $obj->customer ?? null;
    $subscription_id = $obj->subscription ?? ($obj->id ?? null);
  }

  $pdo->prepare("INSERT IGNORE INTO stripe_events (stripe_event_id, event_type, customer_id, subscription_id)
                 VALUES (?, ?, ?, ?)")
      ->execute([$event_id, $event_type, $customer_id, $subscription_id]);
} catch (Throwable $e) {
  wlog("WARN stripe_events insert: " . $e->getMessage());
}

// -------------------------
// HANDLE EVENTS
// -------------------------
try {

  switch ($event_type) {

    // ✅ Checkout ολοκληρώθηκε (δημιουργεί subscription)
    case 'checkout.session.completed': {
      $session = $event->data->object;

      $user_id = null;
      if (!empty($session->metadata->user_id)) {
        $user_id = (int)$session->metadata->user_id;
      } elseif (!empty($session->client_reference_id)) {
        $user_id = (int)$session->client_reference_id;
      }

      $customer_id = $session->customer ?? null;
      $subscription_id = $session->subscription ?? null;

      if (!$user_id) {
        wlog("WARN checkout.session.completed missing user_id");
        break;
      }

      // Pro immediately (MVP). Το “σίγουρο” active έρχεται με payment_succeeded.
      set_user_plan($pdo, $user_id, 'pro', $customer_id, $subscription_id, 'active');
      wlog("OK user {$user_id} -> pro (checkout completed) cus={$customer_id} sub={$subscription_id}");
      break;
    }

    // ✅ Πληρωμή πέτυχε → active
    case 'invoice.payment_succeeded': {
      $invoice = $event->data->object;

      $customer_id = $invoice->customer ?? null;
      $subscription_id = $invoice->subscription ?? null;

      if (!$customer_id) break;

      $user_id = find_user_by_customer($pdo, (string)$customer_id);

      if ($user_id !== null) {
        set_user_plan($pdo, $user_id, 'pro', (string)$customer_id, $subscription_id ? (string)$subscription_id : null, 'active');
        wlog("OK user {$user_id} -> pro active (payment_succeeded)");
      } else {
        wlog("WARN payment_succeeded no user for cus={$customer_id}");
      }

      break;
    }

    // ⚠️ Πληρωμή απέτυχε → past_due (ΔΕΝ ρίχνουμε free αμέσως)
    case 'invoice.payment_failed': {
      $invoice = $event->data->object;

      $customer_id = $invoice->customer ?? null;
      $subscription_id = $invoice->subscription ?? null;

      if (!$customer_id) break;

      $user_id = find_user_by_customer($pdo, (string)$customer_id);
      if ($user_id !== null) {
        // Κρατάμε pro, αλλά status past_due
        set_user_plan($pdo, $user_id, 'pro', (string)$customer_id, $subscription_id ? (string)$subscription_id : null, 'past_due');
        wlog("OK user {$user_id} -> past_due (payment_failed)");
      } else {
        wlog("WARN payment_failed no user for cus={$customer_id}");
      }

      break;
    }

    // ✅ Subscription updated (active/trialing/past_due/cancel_at_period_end)
    case 'customer.subscription.updated': {
      $sub = $event->data->object;

      $customer_id = $sub->customer ?? null;
      $subscription_id = $sub->id ?? null;
      $sub_status = $sub->status ?? null;
      $cancel_at_period_end = $sub->cancel_at_period_end ?? false;

      if (!$customer_id) break;

      $user_id = find_user_by_customer($pdo, (string)$customer_id);
      if ($user_id === null) {
        wlog("WARN sub.updated no user for cus={$customer_id}");
        break;
      }

      // Logic:
      // - active/trialing => pro
      // - αν cancel_at_period_end => κρατάει pro αλλά status canceling
      // - past_due/unpaid/canceled => free (ή pro+locked αν θες)
      $plan = 'free';
      $store_status = $sub_status ? (string)$sub_status : null;

      if ($sub_status === 'active' || $sub_status === 'trialing') {
        $plan = 'pro';
        $store_status = $cancel_at_period_end ? 'canceling' : (string)$sub_status;
      } elseif ($sub_status === 'past_due' || $sub_status === 'unpaid') {
        // MVP επιλογή: κρατάμε pro αλλά past_due (μπορείς να κλειδώσεις pro-features στο app)
        $plan = 'pro';
        $store_status = (string)$sub_status;
      } else {
        $plan = 'free';
        $store_status = $sub_status ? (string)$sub_status : 'inactive';
      }

      set_user_plan($pdo, $user_id, $plan, (string)$customer_id, $subscription_id ? (string)$subscription_id : null, $store_status);
      wlog("OK user {$user_id} -> {$plan} status={$store_status} (sub.updated)");
      break;
    }

    // ✅ Subscription deleted → free
    case 'customer.subscription.deleted': {
      $sub = $event->data->object;

      $customer_id = $sub->customer ?? null;
      $subscription_id = $sub->id ?? null;

      if (!$customer_id) break;

      $user_id = find_user_by_customer($pdo, (string)$customer_id);
      if ($user_id !== null) {
        set_user_plan($pdo, $user_id, 'free', (string)$customer_id, $subscription_id ? (string)$subscription_id : null, 'canceled');
        wlog("OK user {$user_id} -> free (sub.deleted)");
      } else {
        wlog("WARN sub.deleted no user for cus={$customer_id}");
      }

      break;
    }

    default:
      wlog("IGNORE {$event_type}");
      break;
  }

} catch (\Throwable $e) {
  wlog("ERROR handle {$event_type}: " . $e->getMessage());
  json_response(500, "Webhook handler error");
}

json_response(200, "ok");
