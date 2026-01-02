<?php
// cron_reminders.php
require 'config.php';

// ΣΗΜΕΙΩΣΗ: αυτό ΔΕΝ πρέπει να είναι public accessible ιδανικά.
// Μπορείς να βάλεις κάποιο simple secret key ή να το τρέχεις μόνο από CLI.

$now = new DateTime();

$sql = "SELECT c.*, u.email AS user_email
        FROM cards c
        JOIN users u ON c.user_id = u.id
        WHERE c.status != 'done'";
$stmt = $pdo->query($sql);
$cards = $stmt->fetchAll();

// απλό mail() για αρχή, μετά βάζεις PHPMailer / SMTP
function sendReminderEmail($to, $title, $due_date) {
    $subject = "Υπενθύμιση για: $title";
    $message = "Έχεις μία επικείμενη υποχρέωση: $title\nΠροθεσμία: $due_date\n\nLifeBoard";
    @mail($to, $subject, $message, "From: no-reply@lifeboard.test");
}

foreach ($cards as $card) {
    $due = new DateTime($card['due_date']);
    $diff = (int)$now->diff($due)->format('%r%a'); // μέρες διαφορά (μπορεί να είναι αρνητικό)

    // update status σε overdue αν έχει περάσει
    if ($due < $now && $card['status'] !== 'done' && $card['status'] !== 'overdue') {
        $stmtUpdate = $pdo->prepare("UPDATE cards SET status = 'overdue' WHERE id = ?");
        $stmtUpdate->execute([$card['id']]);
    }

    // αν έχει ήδη περάσει η due_date, δεν χρειάζεται reminder (εκτός αν θέλεις)
    if ($diff < 0) {
        continue;
    }

    // Πότε στέλνουμε reminder;
    if ($diff <= (int)$card['remind_before_days']) {
        // Έλεγξε αν έχουμε στείλει ήδη
        if ($card['last_reminded_at'] === null) {
            // Στείλε email
            sendReminderEmail($card['user_email'], $card['title'], $card['due_date']);

            // γράψε notification
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, card_id, type) VALUES (?, ?, 'email')");
            $stmtNotif->execute([$card['user_id'], $card['id']]);

            // ενημέρωσε last_reminded_at
            $stmtUp = $pdo->prepare("UPDATE cards SET last_reminded_at = NOW() WHERE id = ?");
            $stmtUp->execute([$card['id']]);
        }
    }
}
