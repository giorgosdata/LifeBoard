<?php
require 'config.php';
require_login();

$user = current_user($pdo);
$error = '';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM cards WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user['id']]);
$card = $stmt->fetch();

if (!$card) {
  header('Location: dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $due_date = $_POST['due_date'] ?? '';
  $status = $_POST['status'] ?? $card['status'];

  if ($title === '' || $due_date === '') {
    $error = 'Συμπλήρωσε τίτλο και προθεσμία.';
  } else {
    $stmt = $pdo->prepare("UPDATE cards SET title=?, description=?, category=?, due_date=?, status=? WHERE id=? AND user_id=?");
    $stmt->execute([$title, $description, $category, $due_date, $status, $id, $user['id']]);
    header('Location: dashboard.php');
    exit;
  }
}

$page_title = 'Επεξεργασία Κάρτας - LifeBoard';
require 'layout_top.php';
?>

<div class="card pad">
  <div class="section-title">
    <div>
      <h1>Edit Κάρτας</h1>
      <p>Ενημέρωσε τίτλο, προθεσμία, status και λεπτομέρειες.</p>
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
        <input type="text" name="title" value="<?= h($_POST['title'] ?? $card['title']) ?>" required>
      </div>
      <div>
        <label class="small">Προθεσμία *</label>
        <input type="text" name="due_date" value="<?= h($_POST['due_date'] ?? $card['due_date']) ?>" placeholder="YYYY-MM-DD" required>
      </div>
    </div>

    <div style="margin-top:12px;">
      <label class="small">Κατηγορία</label>
      <input type="text" name="category" value="<?= h($_POST['category'] ?? $card['category']) ?>">
    </div>

    <div style="margin-top:12px;">
      <label class="small">Status</label>
      <select name="status">
        <?php
          $current = $_POST['status'] ?? $card['status'];
          $opts = ['upcoming' => 'Επερχόμενο', 'overdue' => 'Ληγμένο', 'done' => 'Ολοκληρωμένο'];
          foreach ($opts as $k => $label) {
            $sel = ($current === $k) ? 'selected' : '';
            echo "<option value=\"".h($k)."\" $sel>".h($label)."</option>";
          }
        ?>
      </select>
    </div>

    <div style="margin-top:12px;">
      <label class="small">Περιγραφή</label>
      <textarea name="description" rows="6"><?= h($_POST['description'] ?? $card['description']) ?></textarea>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn primary" type="submit">Αποθήκευση</button>
      <a href="card_mark_done.php?id=<?= (int)$card['id'] ?>"><button class="btn" type="button">Mark Done</button></a>
      <a href="card_delete.php?id=<?= (int)$card['id'] ?>" onclick="return confirm('Σίγουρα διαγραφή;')">
        <button class="btn danger" type="button">Delete</button>
      </a>
    </div>
  </form>
</div>

<?php require 'layout_bottom.php'; ?>
