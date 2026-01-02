<?php
// απαιτεί: config.php πριν (για h()), και optional $user
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title><?= isset($page_title) ? h($page_title) : 'LifeBoard' ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
  <div class="navbar-inner">
    <div class="brand">
      <span class="brand-dot"></span>
      <span>LifeBoard</span>
    </div>

    <div class="nav-links">
      <?php if (!empty($user)): ?>
        <span class="chip"><?= h($user['email']) ?></span>

        <?php if (($user['plan'] ?? 'free') === 'pro'): ?>
          <span class="chip" style="border-color:rgba(34,197,94,.35); color:#bff7d0;">PRO</span>
        <?php else: ?>
          <span class="chip" style="border-color:rgba(124,92,255,.35); color:#d7d0ff;">FREE</span>
        <?php endif; ?>

        <a class="chip" href="dashboard.php">Dashboard</a>
        <a class="chip" href="card_new.php">+ Νέα Κάρτα</a>

        <?php if (($user['plan'] ?? 'free') !== 'pro'): ?>
          <a class="chip primary" href="upgrade.php">Upgrade</a>
        <?php else: ?>
          <a class="chip" href="upgrade.php">Manage</a>
        <?php endif; ?>

        <a class="chip" href="logout.php">Logout</a>
      <?php else: ?>
        <a class="chip" href="login.php">Login</a>
        <a class="chip primary" href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container">
