<?php
require 'config.php';

if (is_logged_in()) {
  header('Location: dashboard.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = 'Συμπλήρωσε email και κωδικό.';
  } else {
    // Rate limit: 10 προσπάθειες / 5 λεπτά ανά IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'na';
    if (!rate_limit('login_' . $ip, 10, 300)) {
      $error = 'Πάρα πολλές προσπάθειες. Δοκίμασε ξανά σε λίγα λεπτά.';
    } else {
      if (login_user($pdo, $email, $password)) {
        header('Location: dashboard.php');
        exit;
      } else {
        $error = 'Λάθος στοιχεία σύνδεσης.';
      }
    }
  }
}

$page_title = 'Login - LifeBoard';
$user = null;
require 'layout_top.php';
?>

<div class="card pad" style="max-width:560px;margin:0 auto;">
  <div class="section-title">
    <div>
      <h1>Καλώς ήρθες πίσω</h1>
      <p>Σύνδεση για να δεις το dashboard σου.</p>
    </div>
    <span class="badge">Secure</span>
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

    <div style="margin-top:14px;">
      <button class="btn primary full" type="submit">Login →</button>
    </div>

    <div class="hr"></div>

    <p class="small">
      Δεν έχεις λογαριασμό; <a class="chip primary" href="register.php">Register</a>
    </p>
  </form>
</div>

<?php require 'layout_bottom.php'; ?>
