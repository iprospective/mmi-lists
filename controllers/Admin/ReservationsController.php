<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\ReservationService;

final class ReservationsController extends BaseAdminController
{
    protected string $adminPage = 'reservations';

    public function index(): void
    {
        $svc = new ReservationService($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
            $action = $_POST['action'] ?? '';

            if ($action === 'delete_reservation') {
                $svc->delete((int) ($_POST['res_id'] ?? 0));
                $this->msg = "Réservation supprimée.";
            }

            elseif ($action === 'save_reservation') {
                $name = trim((string) ($_POST['guest_name'] ?? ''));
                if ($name === '') {
                    $this->msg = "Le nom ne peut pas être vide.";
                    $this->msgType = 'error';
                } else {
                    $svc->update(
                        (int) ($_POST['res_id'] ?? 0),
                        $name,
                        trim((string) ($_POST['guest_email'] ?? '')),
                        max(1, (int) ($_POST['quantity'] ?? 1))
                    );
                    $this->msg = "Réservation mise à jour.";
                }
            }
        }

        $this->render('admin/reservations', ['rows' => $svc->allWithItem()]);
    }
}
