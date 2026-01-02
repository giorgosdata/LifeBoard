<?php
require 'config.php';
require_login();
$user = current_user($pdo);

$page_title = 'Ακύρωση πληρωμής';
require 'layout_top.php';
?>
<div class="card pad" style="max-width:760px;margin:0 auto;">
  <h1>❌ Η πληρωμή ακυρώθηκε</h1>
  <p>Δεν χρεώθηκες. Μπορείς να δοκιμάσεις ξανά όποτε θες.</p>

  <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
    <a href="upgrade.php"><button class="btn primary">Ξανά Upgrade</button></a>
    <a href="dashboard.php"><button class="btn">Πίσω στο Dashboard</button></a>
  </div>
</div>
<?php require 'layout_bottom.php'; ?>
