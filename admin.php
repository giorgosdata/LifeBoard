<?php
require 'config.php';
require_login();

$user = current_user($pdo);

// 1) ΒΑΛΕ ΤΟ ΔΙΚΟ ΣΟΥ EMAIL ΕΔΩ
$ADMIN_EMAIL = 'giorgosigo@icloud.com';

if (!$user || ($user['email'] ?? '') !== $ADMIN_EMAIL) {
  http_response_code(403);
  exit('Forbidden');
}

$msg = '';

if (is_post()) {
  $target_email = trim($_POST['email'] ?? '');
  $action = $_POST['action'] ?? '';

  if (!filter_var($target_email, FILTER_VALIDATE_EMAIL)) {
    $msg = 'Λάθος email.';
  } else {
    $st = $pdo->prepare("SELECT id, email, plan FROM users WHERE email=? LIMIT 1");
    $st->execute([$target_email]);
    $u = $st->fetch();

    if (!$u) {
      $msg = 'User δεν βρέθηκε.';
    } else {
      if ($action === 'make_pro') {
        $pdo->prepare("UPDATE users SET plan='pro', stripe_status='admin_pro' WHERE id=?")->execute([$u['id']]);
        $msg = "OK: {$u['email']} έγινε PRO.";
      } elseif ($action === 'make_free') {
        $pdo->prepare("UPDATE users SET plan='free', stripe_status='admin_free' WHERE id=?")->execute([$u['id']]);
        $msg = "OK: {$u['email']} έγινε FREE.";
      } elseif ($action === 'clear_stripe') {
        $pdo->prepare("UPDATE users SET stripe_customer_id=NULL, stripe_subscription_id=NULL, stripe_status=NULL WHERE id=?")->execute([$u['id']]);
        $msg = "OK: {$u['email']} καθάρισε Stripe fields.";
      } else {
        $msg = 'Άγνωστη ενέργεια.';
      }
    }
  }
}

require 'layout_top.php';
?>
<div class="card pad" style="max-width:820px;margin:0 auto;">
  <h1>Admin</h1>
  <p class="small">Μόνο για εσένα. Email admin: <strong><?=h($ADMIN_EMAIL)?></strong></p>

  <?php if ($msg): ?>
    <div class="notice ok"><?=h($msg)?></div>
  <?php endif; ?>

  <form method="POST" style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
    <div style="flex:1;min-width:260px;">
      <label class="small">User Email</label>
      <input class="input" name="email" placeholder="user@email.com" required>
    </div>

    <button class="btn primary" name="action" value="make_pro" type="submit">Make PRO</button>
    <button class="btn" name="action" value="make_free" type="submit">Make FREE</button>
    <button class="btn" name="action" value="clear_stripe" type="submit">Clear Stripe</button>
  </form>
</div>
<?php require 'layout_bottom.php'; ?>
