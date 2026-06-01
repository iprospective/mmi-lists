<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\ReservationService;

final class PeopleController extends BaseAdminController
{
    protected string $adminPage = 'people';

    public function index(): void
    {
        $svc = new ReservationService($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
            $action = $_POST['action'] ?? '';
            $key = (string) ($_POST['person_key'] ?? '');

            if ($action === 'rename_person') {
                $newName = trim((string) ($_POST['new_name'] ?? ''));
                if ($newName === '') {
                    $this->msg = "Le nom ne peut pas être vide.";
                    $this->msgType = 'error';
                } else {
                    $n = $svc->renamePerson($key, $newName);
                    if ($n > 0) {
                        $this->msg = "Personne renommée ($n réservation" . ($n > 1 ? 's' : '') . ").";
                    } else {
                        $this->msg = "Personne introuvable.";
                        $this->msgType = 'error';
                    }
                }
            }

            elseif ($action === 'delete_person') {
                $n = $svc->deletePerson($key);
                $this->msg = "Toutes les réservations de cette personne ont été supprimées ($n).";
            }
        }

        $this->render('admin/people', ['people' => $svc->groupByPerson()]);
    }
}
