<?php
require 'config.php';
require_login();
$user = current_user($pdo);

$page_title = 'Upgrade - LifeBoard';
require 'layout_top.php';
?>

<div class="card pad">
  <div class="section-title">
    <div>
      <h1>Upgrade σε Pro</h1>
      <p>Ξεκλείδωσε όλα τα features και δούλεψε χωρίς περιορισμούς.</p>
      <p class="small">Τρέχον πλάνο: <strong><?= h(user_plan_label($user['plan'])) ?></strong></p>
    </div>

    <?php if (($user['plan'] ?? 'free') === 'pro'): ?>
      <span class="badge pro">PRO</span>
    <?php else: ?>
      <span class="badge free">FREE</span>
    <?php endif; ?>
  </div>

  <?php if (($user['plan'] ?? 'free') === 'pro'): ?>
    <div class="notice ok">Έχεις ήδη Pro πλάνο 🎉</div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
      <a href="billing_portal.php"><button class="btn">Διαχείριση Συνδρομής</button></a>
      <a href="dashboard.php"><button class="btn primary">Πίσω στον πίνακα</button></a>
    </div>

  <?php else: ?>

    <div class="pricing">
      <div class="price-card">
        <h2>Free</h2>
        <div class="price">0€</div>
        <div class="small">Για βασική χρήση.</div>
        <div class="hr"></div>
        <ul>
          <li>Έως 10 κάρτες</li>
          <li>Βασικά reminders</li>
          <li>Core dashboard</li>
        </ul>
        <div class="hr"></div>
        <button class="btn full" disabled>Είσαι στο Free</button>
      </div>

      <div class="price-card" style="border-color: rgba(124,92,255,.45);">
        <h2>Pro <span class="badge pro" style="margin-left:8px;">Προτεινόμενο</span></h2>
        <div class="price">5€<span class="small">/μήνα</span></div>
        <div class="small">Ακύρωση οποιαδήποτε στιγμή.</div>
        <div class="hr"></div>
        <ul>
          <li>Unlimited κάρτες</li>
          <li>Advanced reminders</li>
          <li>Προτεραιότητα σε νέα features</li>
        </ul>
        <div class="hr"></div>

        <form action="checkout.php" method="POST">
          <button class="btn primary full" type="submit">Αναβάθμιση με Stripe →</button>
        </form>

        <div class="small" style="margin-top:10px;">
          Ασφαλής πληρωμή μέσω Stripe Checkout.
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php require 'layout_bottom.php'; ?>
