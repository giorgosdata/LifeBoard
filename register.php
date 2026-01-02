<?php
require 'config.php';

if (is_logged_in()) {
  header('Location: dashboard.php');
  exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';

  if ($email === '' || $password === '') {
    $error = 'Συμπλήρωσε email και κωδικό.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Μη έγκυρο email.';
  } elseif ($password !== $password2) {
    $error = 'Οι κωδικοί δεν ταιριάζουν.';
  } elseif (strlen($password) < 6) {
    $error = 'Ο κωδικός πρέπει να έχει τουλάχιστον 6 χαρακτήρες.';
  } else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $error = 'Υπάρχει ήδη λογαριασμός με αυτό το email.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, plan, created_at) VALUES (?, ?, 'free', NOW())");
      $stmt->execute([$email, $hash]);

      $_SESSION['user_id'] = (int)$pdo->lastInsertId();
      header('Location: dashboard.php');
      exit;
    }
  }
}

$page_title = 'Register - LifeBoard';
$user = null;
require 'layout_top.php';
?>

<div class="card pad" style="max-width:560px;margin:0 auto;">
  <div class="section-title">
    <div>
      <h1>Δημιούργησε λογαριασμό</h1>
      <p>Ξεκίνα δωρεάν. Upgrade σε Pro όποτε θες.</p>
    </div>
    <span class="badge free">FREE</span>
  </div>

  <?php if ($error): ?>
    <div class="notice err"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" style="margin-top:14px;">
    <div style="margin-top:10px;">
      <label class="small">Email</label>
      <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@email.com" required>
    </div>

    <div style="margin-top:12px;">
      <label class="small">Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>

    <div style="margin-top:12px;">
      <label class="small">Confirm password</label>
      <input type="password" name="password2" placeholder="••••••••" required>
    </div>

    <div style="margin-top:14px;">
      <button class="btn primary full" type="submit">Create account →</button>
    </div>

    <div class="hr"></div>

    <p class="small">
      Έχεις ήδη λογαριασμό; <a class="chip" href="login.php">Login</a>
    </p>
  </form>
</div>

<?php require 'layout_bottom.php'; ?>
