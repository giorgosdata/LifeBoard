<?php
require 'config.php';
require_login();
require 'stripe_config.php';

$user = current_user($pdo);

$session = \Stripe\Checkout\Session::create([
  'mode' => 'subscription',
  'customer_email' => $user['email'],
  'client_reference_id' => (string)$user['id'],
  'metadata' => [
    'user_id' => (string)$user['id'],
    'user_email' => $user['email'],
  ],
  'line_items' => [[
    'price' => $STRIPE_PRICE_ID_PRO,
    'quantity' => 1,
  ]],
  'success_url' => $BASE_URL . '/upgrade_success.php?session_id={CHECKOUT_SESSION_ID}',
  'cancel_url'  => $BASE_URL . '/upgrade_cancel.php',
]);

header("Location: " . $session->url);
exit;
