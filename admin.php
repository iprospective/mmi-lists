<?php
require __DIR__ . '/lib/bootstrap.php';

$msg = null; $msgType = 'ok';

// --- Connexion / déconnexion admin ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
    if (check_csrf() && check_password((string) ($_POST['password'] ?? ''), (string) cfg('admin_password'))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php'); exit;
    }
    $msg = "Mot de passe administrateur incorrect."; $msgType = 'error';
}
if (($_GET['logout'] ?? '') === '1') {
    unset($_SESSION['admin']);
    header('Location: admin.php'); exit;
}

// --- Écran de connexion admin ---
if (!is_admin()) {
    $pageTitle = 'Administration';
    require __DIR__ . '/lib/header.php'; ?>
    <section class="login-box">
        <h1>🔧 Administration</h1>
        <?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <input type="hidden" name="action" value="admin_login">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>Mot de passe administrateur
                <input type="password" name="password" autofocus required>
            </label>
            <button type="submit">Entrer</button>
        </form>
        <p class="muted"><a href="index.php">← Retour à la liste</a></p>
    </section>
    <?php require __DIR__ . '/lib/footer.php'; exit;
}

// =====================================================================
//  Actions admin (toutes protégées par CSRF)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_photo') {
        $id = (int) ($_POST['item_id'] ?? 0);
        $item = $pdo->query("SELECT * FROM items WHERE id = " . $id)->fetch();
        if ($item && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['photo']['tmp_name'];
            $info = @getimagesize($tmp);
            $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
            if ($info && isset($allowed[$info[2]]) && $_FILES['photo']['size'] <= 8 * 1024 * 1024) {
                $ext = $allowed[$info[2]];
                $fname = $item['slug'] . '.' . $ext;
                $dest = $ROOT . '/img/products/' . $fname;
                // supprime une éventuelle ancienne photo d'une autre extension
                foreach (['jpg','png','gif','webp'] as $oldExt) {
                    $old = $ROOT . '/img/products/' . $item['slug'] . '.' . $oldExt;
                    if ($old !== $dest && is_file($old)) @unlink($old);
                }
                if (move_uploaded_file($tmp, $dest)) {
                    $pdo->prepare("UPDATE items SET photo = ? WHERE id = ?")->execute([$fname, $id]);
                    $msg = "Photo mise à jour pour « " . $item['name'] . " ».";
                } else {
                    $msg = "Échec de l'enregistrement du fichier (droits sur img/products/ ?)."; $msgType = 'error';
                }
            } else {
                $msg = "Fichier invalide : JPG, PNG, GIF ou WEBP, 8 Mo max."; $msgType = 'error';
            }
        } else {
            $msg = "Aucun fichier reçu."; $msgType = 'error';
        }
    }

    elseif ($action === 'save_item') {
        $id = (int) ($_POST['item_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        $search = trim((string) ($_POST['search'] ?? ''));
        $qtyRaw = trim((string) ($_POST['qty_needed'] ?? ''));
        $qty = ($qtyRaw === '' || strtolower($qtyRaw) === 'illimité') ? null : max(0, (int) $qtyRaw);
        if ($name !== '') {
            $pdo->prepare("UPDATE items SET name=?, description=?, search=?, qty_needed=? WHERE id=?")
                ->execute([$name, $desc, $search, $qty, $id]);
            $msg = "Article mis à jour.";
        } else {
            $msg = "Le nom ne peut pas être vide."; $msgType = 'error';
        }
    }

    elseif ($action === 'add_item') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $cat  = trim((string) ($_POST['category'] ?? 'Autres (coups de cœur)'));
        if ($name !== '') {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(
                iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name
            ));
            $slug = trim($slug, '-') ?: ('item-' . time());
            // garantit l'unicité
            $base = $slug; $n = 2;
            while ($pdo->query("SELECT 1 FROM items WHERE slug = " . $pdo->quote($slug))->fetchColumn()) {
                $slug = $base . '-' . $n++;
            }
            $maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM items")->fetchColumn();
            $pdo->prepare("INSERT INTO items (slug, category, name, sort_order) VALUES (?,?,?,?)")
                ->execute([$slug, $cat, $name, $maxOrder]);
            $msg = "Article « " . $name . " » ajouté.";
        } else {
            $msg = "Indiquez au moins un nom."; $msgType = 'error';
        }
    }

    elseif ($action === 'delete_item') {
        $id = (int) ($_POST['item_id'] ?? 0);
        $pdo->prepare("DELETE FROM items WHERE id = ?")->execute([$id]);
        $msg = "Article supprimé (et ses réservations).";
    }

    elseif ($action === 'delete_reservation') {
        $rid = (int) ($_POST['res_id'] ?? 0);
        $pdo->prepare("DELETE FROM reservations WHERE id = ?")->execute([$rid]);
        $msg = "Réservation supprimée.";
    }
}

$items = load_items($pdo);
$pageTitle = 'Administration';
require __DIR__ . '/lib/header.php';
?>
<section class="intro">
    <h1>🔧 Administration</h1>
    <p class="muted">
        <a href="index.php">Voir la liste</a> ·
        <a href="admin.php?logout=1">Déconnexion admin</a>
    </p>
</section>

<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<details class="admin-add">
    <summary>➕ Ajouter un article</summary>
    <form method="post" class="stack">
        <input type="hidden" name="action" value="add_item">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Nom <input type="text" name="name" required></label>
        <label>Catégorie <input type="text" name="category" value="Autres (coups de cœur)"></label>
        <button type="submit">Ajouter</button>
    </form>
</details>

<h2 class="cat-title">📷 Articles &amp; photos</h2>
<div class="admin-list">
    <?php foreach ($items as $it): ?>
        <article class="admin-item">
            <div class="admin-thumb">
                <img src="<?= e(photo_url($it)) ?>" alt="">
            </div>
            <div class="admin-fields">
                <p class="muted small"><?= e($it['category']) ?> · réservé <?= (int) $it['reserved'] ?><?= $it['qty_needed'] !== null ? ' / ' . (int) $it['qty_needed'] : ' (illimité)' ?></p>

                <form method="post" class="stack" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_photo">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                    <label>Photo <input type="file" name="photo" accept="image/*" required></label>
                    <button type="submit">Envoyer la photo</button>
                </form>

                <form method="post" class="stack">
                    <input type="hidden" name="action" value="save_item">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                    <label>Nom <input type="text" name="name" value="<?= e($it['name']) ?>"></label>
                    <label>Description <textarea name="description" rows="2"><?= e($it['description']) ?></textarea></label>
                    <label>Quantité souhaitée <span class="muted">(vide = illimité)</span>
                        <input type="text" name="qty_needed" value="<?= $it['qty_needed'] === null ? '' : (int) $it['qty_needed'] ?>">
                    </label>
                    <label>Mots-clés occasion <span class="muted">(vide = pas de liens)</span>
                        <input type="text" name="search" value="<?= e($it['search']) ?>">
                    </label>
                    <button type="submit">Enregistrer</button>
                </form>

                <?php if (!empty($it['reservations'])): ?>
                    <div class="admin-res">
                        <strong>Réservations :</strong>
                        <ul>
                            <?php foreach ($it['reservations'] as $r): ?>
                                <li>
                                    <?= e($r['guest_name']) ?> ×<?= (int) $r['quantity'] ?>
                                    <?php if ($r['guest_email'] !== ''): ?><span class="muted">&lt;<?= e($r['guest_email']) ?>&gt;</span><?php endif; ?>
                                    <span class="muted small"><?= e($r['created_at']) ?></span>
                                    <form method="post" class="inline-cancel" onsubmit="return confirm('Supprimer cette réservation ?');">
                                        <input type="hidden" name="action" value="delete_reservation">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="res_id" value="<?= (int) $r['id'] ?>">
                                        <button type="submit" class="link-btn">supprimer</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="danger" onsubmit="return confirm('Supprimer définitivement cet article ?');">
                    <input type="hidden" name="action" value="delete_item">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                    <button type="submit" class="link-btn danger-link">supprimer l'article</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/lib/footer.php'; ?>
