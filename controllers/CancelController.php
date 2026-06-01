<?php
declare(strict_types=1);

namespace Controllers;

use PDO;
use Services\Auth;
use Services\ReservationService;

final class CancelController
{
    public function __construct(private PDO $pdo) {}

    public function store(): void
    {
        Auth::requireGuest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf()) {
            flash('error', "Action invalide, merci de réessayer.");
            redirect('');
        }

        $token = (string) ($_POST['token'] ?? '');

        // On n'autorise l'annulation que si le jeton appartient au visiteur (cookie).
        if ($token === '' || !in_array($token, Auth::myTokens(), true)) {
            flash('error', "Vous ne pouvez annuler que vos propres réservations.");
            redirect('');
        }

        (new ReservationService($this->pdo))->deleteByToken($token);

        flash('ok', "Réservation annulée.");
        redirect('');
    }
}
