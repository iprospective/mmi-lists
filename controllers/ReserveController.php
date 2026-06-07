<?php
declare(strict_types=1);

namespace Controllers;

use PDO;
use Services\Auth;
use Services\ItemService;
use Services\ReservationService;

final class ReserveController
{
    public function __construct(private PDO $pdo) {}

    public function store(): void
    {
        Auth::requireGuest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf()) {
            flash('error', "Action invalide, merci de réessayer.");
            redirect('');
        }

        $items = new ItemService($this->pdo);
        $res   = new ReservationService($this->pdo);

        $itemId = (int) ($_POST['item_id'] ?? 0);
        $name   = trim((string) ($_POST['name'] ?? ''));
        $email  = trim((string) ($_POST['email'] ?? ''));
        $qty    = max(1, (int) ($_POST['quantity'] ?? 1));

        if ($name === '') {
            flash('error', "Merci d'indiquer votre prénom.");
            redirect('');
        }

        $item = $items->find($itemId);
        if (!$item) {
            flash('error', "Cadeau introuvable.");
            redirect('');
        }

        // Recalcule la quantité réservée pour éviter de dépasser (concurrence).
        if ($item['qty_needed'] !== null) {
            $remaining = (int) $item['qty_needed'] - $res->reservedQty($itemId);
            if ($remaining <= 0) {
                flash('error', "Trop tard, ce cadeau vient d'être entièrement réservé !");
                redirect('');
            }
            if ($qty > $remaining) {
                $qty = $remaining;
            }
        }

        $token = $res->create($itemId, $name, $email, $qty);
        Auth::rememberToken($token);

        // Notifie les parents et envoie un reçu de confirmation à la personne.
        $this->notifyOwners($item['name'], $name, $email, $qty);
        $receipt = $email !== '' ? $this->sendReceipt($item['name'], $name, $email, $qty, $token) : false;

        $msg = "Merci " . $name . " ! Votre réservation pour « " . $item['name'] . " » est enregistrée 💛";
        if ($receipt) {
            $msg .= " Un email de confirmation vient de vous être envoyé.";
        }
        flash('ok', $msg);
        redirect('');
    }

    // Email de notification aux parents (destinataire « email_to »).
    private function notifyOwners(string $itemName, string $guestName, string $guestEmail, int $qty): void
    {
        $to = trim((string) cfg('email_to', ''));
        if ($to === '') {
            return;
        }
        $subject = "Nouvelle réservation : " . $itemName;
        $lines = [
            "Bonne nouvelle ! Une réservation vient d'être faite sur votre liste de naissance.",
            "",
            "Cadeau   : " . $itemName,
            "Quantité : " . $qty,
            "De       : " . $guestName,
            "Email    : " . ($guestEmail !== '' ? $guestEmail : "(non renseigné)"),
            "",
            "— " . cfg('site_title', 'Liste de naissance'),
        ];
        // Reply-To sur l'email de la personne (si fourni) pour pouvoir lui répondre.
        send_mail($to, $subject, implode("\n", $lines), $guestEmail !== '' ? $guestEmail : null);
    }

    // Reçu de confirmation à la personne, avec le lien privé de gestion/annulation.
    private function sendReceipt(string $itemName, string $guestName, string $email, int $qty, string $token): bool
    {
        $manageUrl = abs_url('mes-reservations') . '?t=' . rawurlencode($token);
        $subject = "Confirmation de votre réservation — " . cfg('site_title', 'Liste de naissance');
        $lines = [
            "Bonjour " . $guestName . ",",
            "",
            "Votre réservation est bien enregistrée :",
            "  • " . $itemName . ($qty > 1 ? " (×" . $qty . ")" : ""),
            "",
            "Pour voir ou annuler vos réservations à tout moment, utilisez ce lien privé :",
            $manageUrl,
            "",
            "Merci du fond du cœur 💛",
            "— " . cfg('site_title', 'Liste de naissance'),
        ];
        return send_mail($email, $subject, implode("\n", $lines));
    }
}
