<?php
require 'config.php';
require_login();

$user = current_user($pdo);

// εδώ assume ότι μόνο εσύ μπαίνεις admin – αλλιώς βάλε έλεγχο
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $plan = trim($_POST['plan'] ?? 'free');

  if ($email === '') {
    $msg = 'Δώσε email.';
  } else {
    $stmt = $pdo->prepare("UPDATE users SET plan=? WHERE email=?");
    $stmt->execute([$plan, $email]);
    $msg = "OK: Έγινε update σε $plan για $email";
  }
}

if (($user['email'] ?? '') !== 'TO_DIKO_SOU_EMAIL@gmail.com') {
  http_response_code(403);
  exit('Forbidden');
}

$page_title = 'Admin - Upgrade Users';
require 'layout_top.php';
?>

<div class="card pad">
  <div class="section-title">
    <div>
      <h1>Admin · Upgrade User</h1>
      <p>Χειροκίνητο update plan για testing / support.</p>
    </div>
    <span class="badge warn">Admin</span>
  </div>

  <?php if ($msg): ?>
    <div class="notice ok"><?= h($msg) ?></div>
  <?php endif; ?>

  <form method="POST" style="margin-top:14px;">
    <div class="grid" style="grid-template-columns: 1fr 220px;">
      <div>
        <label class="small">User Email</label>
        <input type="email" name="email" placeholder="user@email.com" required>
      </div>
      <div>
        <label class="small">Plan</label>
        <select name="plan">
          <option value="free">free</option>
          <option value="pro">pro</option>
        </select>
      </div>
    </div>

    <div style="margin-top:14px;">
      <button class="btn primary" type="submit">Update</button>
    </div>
  </form>
</div>

<?php require 'layout_bottom.php'; ?>
