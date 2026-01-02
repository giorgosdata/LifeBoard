<?php
require 'config.php';
require_login();
$user = current_user($pdo);

$page_title = 'Επιτυχία πληρωμής';
require 'layout_top.php';
?>
<div class="card pad" style="max-width:760px;margin:0 auto;">
  <h1>✅ Πληρωμή ολοκληρώθηκε</h1>
  <p>Η αναβάθμιση γίνεται αυτόματα μέσω Stripe webhooks.</p>
  <p class="small">Αν δεν δεις PRO άμεσα, κάνε refresh σε λίγα δευτερόλεπτα.</p>

  <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
    <a href="dashboard.php"><button class="btn primary">Πήγαινε στο Dashboard</button></a>
    <a href="upgrade.php"><button class="btn">Σελίδα Upgrade</button></a>
  </div>
</div>
<?php require 'layout_bottom.php'; ?>
