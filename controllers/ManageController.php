<?php
declare(strict_types=1);

namespace Controllers;

use PDO;
use Services\ReservationService;

// Gestion des réservations par lien privé reçu par email (sans mot de passe).
// Le jeton de réservation transmis dans l'URL sert d'authentification : il permet
// de voir et d'annuler toutes les réservations faites avec la même adresse email.
final class ManageController
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        $res   = new ReservationService($this->pdo);
        $token = (string) ($_GET['t'] ?? '');
        $owner = $token !== '' ? $res->findByToken($token) : null;

        $reservations = [];
        if ($owner) {
            $key = ReservationService::personKey($owner['guest_name'], $owner['guest_email']);
            $reservations = $res->forPersonKey($key);
        }

        view('manage/index', [
            'pageTitle'     => 'Mes réservations',
            'owner'         => $owner,
            'reservations'  => $reservations,
            'token'         => $token,
            'flash'         => take_flash(),
        ]);
    }

    public function cancel(): void
    {
        $res   = new ReservationService($this->pdo);
        $token = (string) ($_POST['token'] ?? '');
        $id    = (int) ($_POST['id'] ?? 0);

        if (!check_csrf()) {
            flash('error', "Action invalide, merci de réessayer.");
            redirect('mes-reservations?t=' . rawurlencode($token));
        }

        $owner  = $token !== '' ? $res->findByToken($token) : null;
        $target = $id > 0 ? $res->findById($id) : null;

        if (!$owner || !$target) {
            flash('error', "Lien invalide ou réservation introuvable.");
            redirect('mes-reservations?t=' . rawurlencode($token));
        }

        // On n'autorise l'annulation que sur les réservations de la même personne.
        $ownerKey  = ReservationService::personKey($owner['guest_name'], $owner['guest_email']);
        $targetKey = ReservationService::personKey($target['guest_name'], $target['guest_email']);
        if (!hash_equals($ownerKey, $targetKey)) {
            flash('error', "Vous ne pouvez annuler que vos propres réservations.");
            redirect('mes-reservations?t=' . rawurlencode($token));
        }

        $res->delete($id);
        flash('ok', "Réservation annulée.");
        redirect('mes-reservations?t=' . rawurlencode($token));
    }
}
