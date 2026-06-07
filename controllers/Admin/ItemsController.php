<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\CategoryService;
use Services\ItemService;

final class ItemsController extends BaseAdminController
{
    protected string $adminPage = 'items';

    public function index(): void
    {
        $items = new ItemService($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
            $action = $_POST['action'] ?? '';

            if ($action === 'upload_photo') {
                $item = $items->find((int) ($_POST['item_id'] ?? 0));
                if ($item && isset($_FILES['photo'])) {
                    $res = $items->savePhoto($item, $_FILES['photo']);
                    $this->msg = $res['msg'];
                    $this->msgType = $res['ok'] ? 'ok' : 'error';
                } else {
                    $this->msg = "Aucun fichier reçu.";
                    $this->msgType = 'error';
                }
            }

            elseif ($action === 'save_item') {
                $name = trim((string) ($_POST['name'] ?? ''));
                if ($name === '') {
                    $this->msg = "Le nom ne peut pas être vide.";
                    $this->msgType = 'error';
                } else {
                    $qtyRaw = trim((string) ($_POST['qty_needed'] ?? ''));
                    $qty = ($qtyRaw === '' || strtolower($qtyRaw) === 'illimité') ? null : max(0, (int) $qtyRaw);
                    $priority = max(0, min(2, (int) ($_POST['priority'] ?? 0)));
                    $early = isset($_POST['needed_early']) ? 1 : 0;
                    $items->update(
                        (int) ($_POST['item_id'] ?? 0),
                        $name,
                        trim((string) ($_POST['category'] ?? '')),
                        trim((string) ($_POST['description'] ?? '')),
                        trim((string) ($_POST['search'] ?? '')),
                        $qty,
                        $priority,
                        $early
                    );
                    $this->msg = "Article mis à jour.";
                }
            }

            elseif ($action === 'add_item') {
                $name = trim((string) ($_POST['name'] ?? ''));
                if ($name === '') {
                    $this->msg = "Indiquez au moins un nom.";
                    $this->msgType = 'error';
                } else {
                    $items->create($name, trim((string) ($_POST['category'] ?? '')));
                    $this->msg = "Article « " . $name . " » ajouté.";
                }
            }

            elseif ($action === 'delete_item') {
                $items->delete((int) ($_POST['item_id'] ?? 0));
                $this->msg = "Article supprimé (et ses réservations).";
            }
        }

        $this->render('admin/items', [
            'items'      => $items->all(),
            'categories' => (new CategoryService($this->pdo))->all(),
        ]);
    }
}
