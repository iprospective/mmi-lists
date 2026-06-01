<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\CategoryService;

final class CategoriesController extends BaseAdminController
{
    protected string $adminPage = 'categories';

    public function index(): void
    {
        $svc = new CategoryService($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
            $action = $_POST['action'] ?? '';

            if ($action === 'add_category') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $icon = trim((string) ($_POST['icon'] ?? '')) ?: '🎁';
                if ($name === '') {
                    $this->error("Indiquez un nom de catégorie.");
                } elseif ($svc->nameExists($name)) {
                    $this->error("Cette catégorie existe déjà.");
                } else {
                    $svc->create($name, $icon);
                    $this->msg = "Catégorie « " . $name . " » ajoutée.";
                }
            }

            elseif ($action === 'save_category') {
                $id   = (int) ($_POST['cat_id'] ?? 0);
                $name = trim((string) ($_POST['name'] ?? ''));
                $icon = trim((string) ($_POST['icon'] ?? '')) ?: '🎁';
                if ($name === '') {
                    $this->error("Le nom ne peut pas être vide.");
                } elseif ($svc->nameExists($name, $id)) {
                    $this->error("Une autre catégorie porte déjà ce nom.");
                } else {
                    $svc->update($id, $name, $icon, trim((string) ($_POST['old_name'] ?? '')));
                    $this->msg = "Catégorie mise à jour.";
                }
            }

            elseif ($action === 'move_category') {
                $svc->move((int) ($_POST['cat_id'] ?? 0), ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down');
            }

            elseif ($action === 'delete_category') {
                $cat = $svc->findById((int) ($_POST['cat_id'] ?? 0));
                if ($cat) {
                    if ($svc->usageCount($cat['name']) > 0) {
                        $this->error("Impossible : des articles utilisent encore « " . $cat['name'] . " ».");
                    } else {
                        $svc->delete((int) $cat['id']);
                        $this->msg = "Catégorie supprimée.";
                    }
                }
            }
        }

        $this->render('admin/categories', [
            'categories' => $svc->all(),
            'counts'     => $svc->counts(),
        ]);
    }

    private function error(string $msg): void
    {
        $this->msg = $msg;
        $this->msgType = 'error';
    }
}
