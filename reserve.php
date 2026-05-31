<?php
require __DIR__ . '/lib/bootstrap.php';
require_guest();

function back(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf()) {
    back('error', "Action invalide, merci de réessayer.");
}

$itemId = (int) ($_POST['item_id'] ?? 0);
$name   = trim((string) ($_POST['name'] ?? ''));
$email  = trim((string) ($_POST['email'] ?? ''));
$qty    = max(1, (int) ($_POST['quantity'] ?? 1));

if ($name === '') {
    back('error', "Merci d'indiquer votre prénom.");
}

$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch();
if (!$item) {
    back('error', "Cadeau introuvable.");
}

// Recalcule la quantité déjà réservée pour éviter de dépasser (concurrence).
$reserved = (int) $pdo->query("SELECT COALESCE(SUM(quantity),0) FROM reservations WHERE item_id = " . (int) $item['id'])->fetchColumn();

if ($item['qty_needed'] !== null) {
    $remaining = (int) $item['qty_needed'] - $reserved;
    if ($remaining <= 0) {
        back('error', "Trop tard, ce cadeau vient d'être entièrement réservé !");
    }
    if ($qty > $remaining) {
        $qty = $remaining; // on ajuste plutôt que de refuser
    }
}

$token = bin2hex(random_bytes(16));
$ins = $pdo->prepare("
    INSERT INTO reservations (item_id, guest_name, guest_email, quantity, token)
    VALUES (?, ?, ?, ?, ?)
");
$ins->execute([$item['id'], $name, $email, $qty, $token]);
remember_token($token);

back('ok', "Merci " . $name . " ! Votre réservation pour « " . $item['name'] . " » est enregistrée 💛");
