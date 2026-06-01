<?php
$adminPage = 'people';
require __DIR__ . '/lib/admin_auth.php';

$msg = null; $msgType = 'ok';

// Clé d'identité d'une personne : email (en minuscules) si présent, sinon le nom.
function person_key(string $name, string $email): string {
    $email = strtolower(trim($email));
    return $email !== '' ? 'mail:' . $email : 'name:' . strtolower(trim($name));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'rename_person') {
        $key     = (string) ($_POST['person_key'] ?? '');
        $newName = trim((string) ($_POST['new_name'] ?? ''));
        if ($newName === '') {
            $msg = "Le nom ne peut pas être vide."; $msgType = 'error';
        } else {
            $all = $pdo->query("SELECT id, guest_name, guest_email FROM reservations")->fetchAll();
            $upd = $pdo->prepare("UPDATE reservations SET guest_name = ? WHERE id = ?");
            $n = 0;
            foreach ($all as $r) {
                if (person_key($r['guest_name'], $r['guest_email']) === $key) {
                    $upd->execute([$newName, $r['id']]);
                    $n++;
                }
            }
            $msg = $n > 0 ? "Personne renommée ($n réservation" . ($n > 1 ? 's' : '') . ")." : "Personne introuvable.";
            if ($n === 0) $msgType = 'error';
        }
    }

    elseif ($action === 'delete_person') {
        $key = (string) ($_POST['person_key'] ?? '');
        $all = $pdo->query("SELECT id, guest_name, guest_email FROM reservations")->fetchAll();
        $del = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
        $n = 0;
        foreach ($all as $r) {
            if (person_key($r['guest_name'], $r['guest_email']) === $key) {
                $del->execute([$r['id']]);
                $n++;
            }
        }
        $msg = "Toutes les réservations de cette personne ont été supprimées ($n).";
    }
}

// Regroupe les réservations par personne.
$rows = $pdo->query("
    SELECT r.*, i.name AS item_name
    FROM reservations r
    LEFT JOIN items i ON i.id = r.item_id
    ORDER BY r.created_at
")->fetchAll();

$people = [];
foreach ($rows as $r) {
    $key = person_key($r['guest_name'], $r['guest_email']);
    if (!isset($people[$key])) {
        $people[$key] = ['name' => $r['guest_name'], 'email' => $r['guest_email'], 'items' => [], 'qty' => 0];
    }
    // Garde le nom le plus récent, et un email s'il en existe un.
    $people[$key]['name'] = $r['guest_name'];
    if (trim((string) $r['guest_email']) !== '') {
        $people[$key]['email'] = $r['guest_email'];
    }
    $people[$key]['items'][] = ['name' => $r['item_name'] ?? '— supprimé —', 'qty' => (int) $r['quantity']];
    $people[$key]['qty'] += (int) $r['quantity'];
}
uasort($people, fn($a, $b) => strcasecmp($a['name'], $b['name']));

$pageTitle = 'Administration — Personnes';
require __DIR__ . '/lib/header.php';
require __DIR__ . '/lib/admin_nav.php';
?>
<h1 class="admin-h1">🧑‍🤝‍🧑 Personnes</h1>
<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<p class="muted small"><?= count($people) ?> personne<?= count($people) > 1 ? 's' : '' ?> ont réservé un cadeau.</p>

<?php if (!$people): ?>
    <p class="muted">Personne n'a encore réservé de cadeau.</p>
<?php else: ?>
    <div class="admin-list">
        <?php foreach ($people as $key => $p): ?>
            <article class="admin-item person">
                <div class="admin-fields">
                    <h3><?= e($p['name']) ?>
                        <span class="qbadge">×<?= (int) $p['qty'] ?></span>
                    </h3>
                    <p class="muted small"><?= $p['email'] !== '' ? e($p['email']) : 'aucun email' ?></p>
                    <ul class="reservers">
                        <?php foreach ($p['items'] as $it): ?>
                            <li><?= e($it['name']) ?><?php if ($it['qty'] > 1): ?> <span class="qbadge">×<?= $it['qty'] ?></span><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <form method="post" class="row-form">
                        <input type="hidden" name="action" value="rename_person">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="person_key" value="<?= e($key) ?>">
                        <input type="text" name="new_name" value="<?= e($p['name']) ?>" required>
                        <button type="submit">Renommer</button>
                    </form>

                    <form method="post" class="danger" onsubmit="return confirm('Supprimer toutes les réservations de cette personne ?');">
                        <input type="hidden" name="action" value="delete_person">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="person_key" value="<?= e($key) ?>">
                        <button type="submit" class="link-btn danger-link">supprimer ses réservations</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/lib/footer.php'; ?>
