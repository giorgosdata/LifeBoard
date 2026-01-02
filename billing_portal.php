<?php
require 'config.php';
require_login();
require 'stripe_config.php';

$user = current_user($pdo);

// DEBUG (αν θες να δεις τι επιστρέφει ο user, άνοιξέ το προσωρινά)
// echo "<pre>"; var_dump($user); exit;

if (!$user) {
  header('Location: login.php');
  exit;
}

if (empty($user['stripe_customer_id'])) {
  // Αν για κάποιο λόγο δεν υπάρχει customer id, δεν ανοίγει portal
  header('Location: upgrade.php');
  exit;
}

$session = \Stripe\BillingPortal\Session::create([
  'customer' => $user['stripe_customer_id'],
  'return_url' => $BASE_URL . '/dashboard.php',
]);

header("Location: " . $session->url);
exit;
