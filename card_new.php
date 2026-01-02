<?php
require 'config.php';
require_login();

$error = '';
$user = current_user($pdo);

if (!can_create_more_cards($pdo, (int)$user['id'], $user)) {
  header("Location: upgrade.php");
  exit;
}

// όριο καρτών
$stmtCount = $pdo->prepare("SELECT COUNT(*) AS cnt FROM cards WHERE user_id = ?");
$stmtCount->execute([$user['id']]);
$rowCount = $stmtCount->fetch();
$currentCount = (int)$rowCount['cnt'];
$maxCards = user_max_cards($user);

if ($currentCount >= $maxCards) {
    $error = "Έχεις φτάσει το όριο καρτών για το \"" . user_plan_label($user['plan']) . "\" πλάνο. Διέγραψε κάποια κάρτα ή κάνε upgrade σε Pro.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $due_date = $_POST['due_date'] ?? '';

    if ($title === '' || $due_date === '') {
        $error = 'Συμπλήρωσε τίτλο και προθεσμία.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO cards (user_id, title, description, category, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'upcoming', NOW())");
        $stmt->execute([$user['id'], $title, $description, $category, $due_date]);
        header('Location: dashboard.php');
        exit;
    }
}

$page_title = 'Νέα Κάρτα - LifeBoard';
require 'layout_top.php';
?>

<div class="card pad">
  <div class="section-title">
    <div>
      <h1>+ Νέα Κάρτα</h1>
      <p>Γράψε κάτι που δεν πρέπει να ξεχάσεις και βάλε προθεσμία.</p>
      <p class="small">Χρήση: <strong><?= (int)$currentCount ?>/<?= (int)$maxCards ?></strong></p>
    </div>
    <a class="chip" href="dashboard.php">← Πίσω</a>
  </div>

  <?php if ($error): ?>
    <div class="notice err"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" style="margin-top:14px;">
    <div class="grid" style="grid-template-columns:1fr 1fr;">
      <div>
        <label class="small">Τίτλος *</label>
        <input type="text" name="title" value="<?= h($_POST['title'] ?? '') ?>" placeholder="π.χ. Ενοίκιο, Ραντεβού, ΔΕΗ..." required>
      </div>
      <div>
        <label class="small">Προθεσμία *</label>
        <input type="text" name="due_date" value="<?= h($_POST['due_date'] ?? '') ?>" placeholder="YYYY-MM-DD" required>
      </div>
    </div>

    <div style="margin-top:12px;">
      <label class="small">Κατηγορία</label>
      <input type="text" name="category" value="<?= h($_POST['category'] ?? '') ?>" placeholder="π.χ. bills, health, work">
    </div>

    <div style="margin-top:12px;">
      <label class="small">Περιγραφή</label>
      <textarea name="description" rows="5" placeholder="Λεπτομέρειες..."><?= h($_POST['description'] ?? '') ?></textarea>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn primary" type="submit">Αποθήκευση</button>
      <a href="dashboard.php"><button class="btn" type="button">Ακύρωση</button></a>
      <?php if ($user['plan'] !== 'pro'): ?>
        <a class="chip primary" href="upgrade.php">Upgrade</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php require 'layout_bottom.php'; ?>
