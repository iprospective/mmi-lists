<?php
declare(strict_types=1);

namespace Controllers;

use PDO;
use Services\Auth;
use Services\ItemService;
use Services\ReservationService;
use Services\ReservationMailer;

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

        // L'option « validation par email » n'est active que si un expéditeur est
        // configuré (sinon la personne ne pourrait jamais valider sa réservation).
        $requireConfirm = cfg('require_confirmation') === '1'
            && filter_var((string) cfg('email_from', ''), FILTER_VALIDATE_EMAIL);

        if ($requireConfirm && $email === '') {
            flash('error', "Merci d'indiquer votre email : il sert à valider votre réservation.");
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

        $token = $res->create($itemId, $name, $email, $qty, !$requireConfirm);
        Auth::rememberToken($token);

        $mailer = new ReservationMailer();

        if ($requireConfirm) {
            // Réservation en attente : on envoie le lien de validation, rien d'autre.
            $mailer->sendConfirmationRequest($item['name'], $name, $email, $qty, $token);
            flash('ok', "Presque terminé " . $name . " ! Un email vient de vous être envoyé : "
                . "cliquez sur le lien qu'il contient pour valider votre réservation.");
            redirect('');
        }

        // Réservation immédiate : notifie les parents et envoie le reçu à la personne.
        $mailer->notifyOwners($item['name'], $name, $email, $qty);
        $receipt = $email !== '' ? $mailer->sendReceipt($item['name'], $name, $email, $qty, $token) : false;

        $msg = "Merci " . $name . " ! Votre réservation pour « " . $item['name'] . " » est enregistrée 💛";
        if ($receipt) {
            $msg .= " Un email de confirmation vient de vous être envoyé.";
        }
        flash('ok', $msg);
        redirect('');
    }
}
