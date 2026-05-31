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

$token = (string) ($_POST['token'] ?? '');

// On n'autorise l'annulation que si le jeton appartient au visiteur (cookie).
if ($token === '' || !in_array($token, my_tokens(), true)) {
    back('error', "Vous ne pouvez annuler que vos propres réservations.");
}

$del = $pdo->prepare("DELETE FROM reservations WHERE token = ?");
$del->execute([$token]);

back('ok', "Réservation annulée.");
