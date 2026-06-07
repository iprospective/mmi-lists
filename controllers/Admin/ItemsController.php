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
            $type   = 'ok';
            $msg    = '';
            $anchor = '';

            if ($action === 'upload_photo') {
                $item = $items->find((int) ($_POST['item_id'] ?? 0));
                if ($item && isset($_FILES['photo'])) {
                    $res = $items->savePhoto($item, $_FILES['photo']);
                    $msg  = $res['msg'];
                    $type = $res['ok'] ? 'ok' : 'error';
                    $anchor = 'item-' . (int) $item['id'];
                } else {
                    $msg = "Aucun fichier reçu.";
                    $type = 'error';
                }
            }

            elseif ($action === 'save_item') {
                $name = trim((string) ($_POST['name'] ?? ''));
                if ($name === '') {
                    $msg = "Le nom ne peut pas être vide.";
                    $type = 'error';
                } else {
                    $qtyRaw = trim((string) ($_POST['qty_needed'] ?? ''));
                    $qty = ($qtyRaw === '' || strtolower($qtyRaw) === 'illimité') ? null : max(0, (int) $qtyRaw);
                    $priority = max(0, min(2, (int) ($_POST['priority'] ?? 0)));
                    $early = isset($_POST['needed_early']) ? 1 : 0;
                    $descHtml = sanitize_html((string) ($_POST['description'] ?? ''));
                    // Évite de stocker un paragraphe vide (« <p><br></p> ») quand il n'y a pas de texte.
                    $description = trim(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], '', $descHtml))) === '' ? '' : $descHtml;
                    $items->update(
                        (int) ($_POST['item_id'] ?? 0),
                        $name,
                        trim((string) ($_POST['category'] ?? '')),
                        $description,
                        trim((string) ($_POST['search'] ?? '')),
                        $qty,
                        $priority,
                        $early
                    );
                    $msg = "Article mis à jour.";
                }
                $anchor = 'item-' . (int) ($_POST['item_id'] ?? 0);
            }

            elseif ($action === 'move_item') {
                $items->move((int) ($_POST['item_id'] ?? 0), ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down');
                $anchor = 'item-' . (int) ($_POST['item_id'] ?? 0);
            }

            elseif ($action === 'add_item') {
                $name = trim((string) ($_POST['name'] ?? ''));
                if ($name === '') {
                    $msg = "Indiquez au moins un nom.";
                    $type = 'error';
                } else {
                    $newId = $items->create($name, trim((string) ($_POST['category'] ?? '')));
                    $msg = "Article « " . $name . " » ajouté.";
                    $anchor = 'item-' . $newId;
                }
            }

            elseif ($action === 'delete_item') {
                $items->delete((int) ($_POST['item_id'] ?? 0));
                $msg = "Article supprimé (et ses réservations).";
            }

            if ($msg !== '') {
                flash($type, $msg);
            }
            // Conserve les filtres actifs (transmis dans l'URL des formulaires) après l'action.
            $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
            redirect('admin' . ($qs !== '' ? '?' . $qs : '') . ($anchor !== '' ? '#' . $anchor : ''));
        }

        if ($f = take_flash()) {
            $this->msg = $f['msg'];
            $this->msgType = $f['type'];
        }

        // Filtres (catégorie, utilité, urgence) transmis dans l'URL.
        $fCat   = trim((string) ($_GET['category'] ?? ''));
        $fPrio  = ($_GET['priority'] ?? '') === '' ? '' : (string) (int) $_GET['priority'];
        $fEarly = ($_GET['early'] ?? '') === '1' ? '1' : '';

        $all = $items->all();
        $filtered = array_values(array_filter($all, static function (array $it) use ($fCat, $fPrio, $fEarly): bool {
            if ($fCat !== '' && $it['category'] !== $fCat) {
                return false;
            }
            if ($fPrio !== '' && (int) ($it['priority'] ?? 0) !== (int) $fPrio) {
                return false;
            }
            if ($fEarly === '1' && (int) ($it['needed_early'] ?? 0) !== 1) {
                return false;
            }
            return true;
        }));

        $this->render('admin/items', [
            'items'        => $filtered,
            'totalCount'   => count($all),
            'categories'   => (new CategoryService($this->pdo))->all(),
            'filterCat'    => $fCat,
            'filterPrio'   => $fPrio,
            'filterEarly'  => $fEarly,
            'filterActive' => ($fCat !== '' || $fPrio !== '' || $fEarly === '1'),
        ]);
    }
}
