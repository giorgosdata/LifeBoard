<?php
require 'config.php';
require_login();
$user = current_user($pdo);

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE cards SET status = 'done' WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user['id']]);
}

header('Location: dashboard.php');
exit;
