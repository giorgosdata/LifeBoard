<?php require 'config.php'; $page_title='Πολιτική Απορρήτου'; require 'layout_top.php'; ?>
<div class="card pad" style="max-width:900px;margin:0 auto;">
  <h1>Πολιτική Απορρήτου</h1>
  <p class="small">Τελευταία ενημέρωση: <?=date('Y-m-d')?></p>
  <h3>Τι δεδομένα κρατάμε</h3>
  <ul>
    <li>Email λογαριασμού</li>
    <li>Δεδομένα χρήσης (κάρτες/reminders)</li>
    <li>Stripe IDs (customer/subscription) — όχι στοιχεία κάρτας</li>
  </ul>
  <h3>Πληρωμές</h3>
  <p>Οι πληρωμές επεξεργάζονται από Stripe. Δεν αποθηκεύουμε στοιχεία κάρτας.</p>
  <h3>Διαγραφή</h3>
  <p>Μπορείς να ζητήσεις διαγραφή λογαριασμού από την υποστήριξη.</p>
</div>
<?php require 'layout_bottom.php'; ?>
