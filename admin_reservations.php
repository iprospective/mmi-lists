<?php
$adminPage = 'reservations';
require __DIR__ . '/lib/admin_auth.php';

$msg = null; $msgType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_reservation') {
        $id = (int) ($_POST['res_id'] ?? 0);
        $pdo->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
        $msg = "Réservation supprimée.";
    }

    elseif ($action === 'save_reservation') {
        $id   = (int) ($_POST['res_id'] ?? 0);
        $name = trim((string) ($_POST['guest_name'] ?? ''));
        $mail = trim((string) ($_POST['guest_email'] ?? ''));
        $qty  = max(1, (int) ($_POST['quantity'] ?? 1));
        if ($name === '') {
            $msg = "Le nom ne peut pas être vide."; $msgType = 'error';
        } else {
            $pdo->prepare("UPDATE reservations SET guest_name = ?, guest_email = ?, quantity = ? WHERE id = ?")
                ->execute([$name, $mail, $qty, $id]);
            $msg = "Réservation mise à jour.";
        }
    }
}

$rows = $pdo->query("
    SELECT r.*, i.name AS item_name, i.category AS item_category
    FROM reservations r
    LEFT JOIN items i ON i.id = r.item_id
    ORDER BY r.created_at DESC
")->fetchAll();

$pageTitle = 'Administration — Réservations';
require __DIR__ . '/lib/header.php';
require __DIR__ . '/lib/admin_nav.php';
?>
<h1 class="admin-h1">🎁 Réservations</h1>
<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<p class="muted small"><?= count($rows) ?> réservation<?= count($rows) > 1 ? 's' : '' ?> au total.</p>

<?php if (!$rows): ?>
    <p class="muted">Aucune réservation pour l'instant.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr><th>Article</th><th>Personne</th><th>Email</th><th>Qté</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['item_name'] ?? '— supprimé —') ?></td>
                    <td colspan="3">
                        <form method="post" class="row-form">
                            <input type="hidden" name="action" value="save_reservation">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="res_id" value="<?= (int) $r['id'] ?>">
                            <input type="text" name="guest_name" value="<?= e($r['guest_name']) ?>" required>
                            <input type="email" name="guest_email" value="<?= e($r['guest_email']) ?>" placeholder="(aucun)">
                            <input type="number" name="quantity" value="<?= (int) $r['quantity'] ?>" min="1" class="qty-input">
                            <button type="submit">Enregistrer</button>
                        </form>
                    </td>
                    <td class="nowrap muted small"><?= e($r['created_at']) ?></td>
                    <td class="nowrap">
                        <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette réservation ?');">
                            <input type="hidden" name="action" value="delete_reservation">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="res_id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" class="link-btn danger-link">supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/lib/footer.php'; ?>
