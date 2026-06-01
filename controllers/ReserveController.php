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

        flash('ok', "Merci " . $name . " ! Votre réservation pour « " . $item['name'] . " » est enregistrée 💛");
        redirect('');
    }
}
