<?php
require 'config.php';
require_login();

$user = current_user($pdo);

// Φέρε κάρτες χρήστη
$stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY due_date ASC");
$stmt->execute([$user['id']]);
$cards = $stmt->fetchAll();

$upcoming = [];
$overdue  = [];
$done     = [];

foreach ($cards as $card) {
    if ($card['status'] === 'done') $done[] = $card;
    elseif ($card['status'] === 'overdue') $overdue[] = $card;
    else $upcoming[] = $card;
}

// KPIs
$totalCards = count($cards);
$maxCards = user_max_cards($user);
$usagePct = $maxCards > 0 ? min(100, (int)round(($totalCards / $maxCards) * 100)) : 0;

$page_title = 'Dashboard - LifeBoard';
require 'layout_top.php';
?>

<div class="card pad">
  <div class="section-title">
    <div>
      <h1>Dashboard</h1>
      <p>Όλα τα σημαντικά σου σε μία εικόνα. Παρακολούθησε προθεσμίες, πρόοδο και όρια πλάνου.</p>
    </div>
<a href="billing_portal.php"><button class="btn">Διαχείριση Συνδρομής</button></a>

    <?php if ($user['plan'] === 'pro'): ?>
      <span class="badge pro">PRO ενεργό</span>
    <?php else: ?>
      <span class="badge free">FREE</span>
    <?php endif; ?>
  </div>

  <div class="kpi">
    <div class="item">
      <div class="num"><?= (int)$totalCards ?></div>
      <div class="lbl">Σύνολο καρτών</div>
      <div class="bar"><div style="width: <?= (int)$usagePct ?>%"></div></div>
      <div class="small" style="margin-top:8px;">Χρήση: <?= (int)$usagePct ?>% (<?= (int)$totalCards ?>/<?= (int)$maxCards ?>)</div>
    </div>

    <div class="item">
      <div class="num"><?= (int)count($upcoming) ?></div>
      <div class="lbl">Επερχόμενα</div>
      <div class="small" style="margin-top:8px;">Αυτά που έρχονται σύντομα.</div>
    </div>

    <div class="item">
      <div class="num"><?= (int)count($overdue) ?></div>
      <div class="lbl">Ληγμένα</div>
      <div class="small" style="margin-top:8px;">Χρειάζονται άμεσα action.</div>
    </div>

    <div class="item">
      <div class="num"><?= (int)count($done) ?></div>
      <div class="lbl">Ολοκληρωμένα</div>
      <div class="small" style="margin-top:8px;">Καλή δουλειά 👌</div>
    </div>
  </div>

  <?php if ($user['plan'] !== 'pro' && $totalCards >= $maxCards): ?>
    <div class="notice warn" style="margin-top:14px;">
      Έφτασες το όριο καρτών για το Free πλάνο. Κάνε Upgrade για unlimited.
    </div>
  <?php endif; ?>
</div>

<div class="grid">
  <div>
    <div class="card pad">
      <div class="section-title">
        <h2>Ληγμένα</h2>
        <span class="badge danger"><?= (int)count($overdue) ?></span>
      </div>

      <div class="cards">
        <?php if (!$overdue): ?>
          <div class="notice ok">Δεν έχεις ληγμένες κάρτες 🎉</div>
        <?php else: ?>
          <?php foreach ($overdue as $card): ?>
            <div class="card-row">
              <div>
                <div class="card-title"><?= h($card['title']) ?></div>
                <div class="card-meta">
                  Κατηγορία: <?= h($card['category'] ?: 'χωρίς') ?> ·
                  Προθεσμία: <?= h($card['due_date']) ?> ·
                  <?= h(human_due_label($card['due_date'], $card['status'])) ?>
                </div>
              </div>
              <div class="actions">
                <a class="chip" href="card_edit.php?id=<?= (int)$card['id'] ?>">Edit</a>
                <a class="chip" href="card_mark_done.php?id=<?= (int)$card['id'] ?>">Done</a>
                <a class="chip" href="card_delete.php?id=<?= (int)$card['id'] ?>" onclick="return confirm('Σίγουρα διαγραφή;')">Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card pad">
      <div class="section-title">
        <h2>Επερχόμενα</h2>
        <span class="badge warn"><?= (int)count($upcoming) ?></span>
      </div>

      <div class="cards">
        <?php if (!$upcoming): ?>
          <div class="notice">Δεν έχεις επερχόμενες κάρτες.</div>
        <?php else: ?>
          <?php foreach ($upcoming as $card): ?>
            <div class="card-row">
              <div>
                <div class="card-title"><?= h($card['title']) ?></div>
                <div class="card-meta">
                  Κατηγορία: <?= h($card['category'] ?: 'χωρίς') ?> ·
                  Προθεσμία: <?= h($card['due_date']) ?> ·
                  <?= h(human_due_label($card['due_date'], $card['status'])) ?>
                </div>
              </div>
              <div class="actions">
                <a class="chip" href="card_edit.php?id=<?= (int)$card['id'] ?>">Edit</a>
                <a class="chip" href="card_mark_done.php?id=<?= (int)$card['id'] ?>">Done</a>
                <a class="chip" href="card_delete.php?id=<?= (int)$card['id'] ?>" onclick="return confirm('Σίγουρα διαγραφή;')">Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="card pad <?= ($user['plan'] === 'pro') ? '' : 'locked' ?>">
      <div class="<?= ($user['plan'] === 'pro') ? '' : 'blur' ?>">
        <div class="section-title">
          <h2>Analytics (Pro)</h2>
          <span class="badge pro">Pro</span>
        </div>

        <?php
          // Data-heavy table example (Pro)
          $cats = [];
          foreach ($cards as $c) {
            $key = trim($c['category'] ?? '');
            if ($key === '') $key = 'χωρίς';
            $cats[$key] = ($cats[$key] ?? 0) + 1;
          }
          arsort($cats);
        ?>

        <table class="table">
          <thead>
            <tr>
              <th>Κατηγορία</th>
              <th>Πλήθος</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$cats): ?>
              <tr><td colspan="2">—</td></tr>
            <?php else: ?>
              <?php foreach ($cats as $k => $v): ?>
                <tr>
                  <td><?= h($k) ?></td>
                  <td><?= (int)$v ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="hr"></div>

        <div class="small">
          Στο Pro μπορούμε να βάλουμε: trends, reminders success rate, heatmap ημερών κτλ.
        </div>
      </div>

      <?php if ($user['plan'] !== 'pro'): ?>
        <div class="hr"></div>
        <a class="chip primary" href="upgrade.php">Ξεκλείδωσε Pro →</a>
      <?php endif; ?>
    </div>

    <div class="card pad">
      <div class="section-title">
        <h2>Ολοκληρωμένα</h2>
        <span class="badge"><?= (int)count($done) ?></span>
      </div>

      <div class="cards">
        <?php if (!$done): ?>
          <div class="notice">Δεν έχεις ολοκληρωμένα ακόμα.</div>
        <?php else: ?>
          <?php foreach (array_slice($done, 0, 8) as $card): ?>
            <div class="card-row">
              <div>
                <div class="card-title"><?= h($card['title']) ?></div>
                <div class="card-meta">
                  Κατηγορία: <?= h($card['category'] ?: 'χωρίς') ?> ·
                  Προθεσμία: <?= h($card['due_date']) ?>
                </div>
              </div>
              <div class="actions">
                <a class="chip" href="card_edit.php?id=<?= (int)$card['id'] ?>">View</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require 'layout_bottom.php'; ?>
