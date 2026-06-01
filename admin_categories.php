<?php
$adminPage = 'categories';
require __DIR__ . '/lib/admin_auth.php';

$msg = null; $msgType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $icon = trim((string) ($_POST['icon'] ?? '')) ?: '🎁';
        if ($name === '') {
            $msg = "Indiquez un nom de catégorie."; $msgType = 'error';
        } else {
            $exists = $pdo->prepare("SELECT 1 FROM categories WHERE name = ?");
            $exists->execute([$name]);
            if ($exists->fetchColumn()) {
                $msg = "Cette catégorie existe déjà."; $msgType = 'error';
            } else {
                $maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM categories")->fetchColumn();
                $pdo->prepare("INSERT INTO categories (name, icon, sort_order) VALUES (?, ?, ?)")
                    ->execute([$name, $icon, $maxOrder]);
                $msg = "Catégorie « " . $name . " » ajoutée.";
            }
        }
    }

    elseif ($action === 'save_category') {
        $id      = (int) ($_POST['cat_id'] ?? 0);
        $name    = trim((string) ($_POST['name'] ?? ''));
        $icon    = trim((string) ($_POST['icon'] ?? '')) ?: '🎁';
        $oldName = trim((string) ($_POST['old_name'] ?? ''));
        if ($name === '') {
            $msg = "Le nom ne peut pas être vide."; $msgType = 'error';
        } else {
            $clash = $pdo->prepare("SELECT 1 FROM categories WHERE name = ? AND id <> ?");
            $clash->execute([$name, $id]);
            if ($clash->fetchColumn()) {
                $msg = "Une autre catégorie porte déjà ce nom."; $msgType = 'error';
            } else {
                $pdo->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?")
                    ->execute([$name, $icon, $id]);
                if ($oldName !== '' && $oldName !== $name) {
                    $pdo->prepare("UPDATE items SET category = ? WHERE category = ?")
                        ->execute([$name, $oldName]);
                }
                $msg = "Catégorie mise à jour.";
            }
        }
    }

    elseif ($action === 'move_category') {
        $id  = (int) ($_POST['cat_id'] ?? 0);
        $dir = ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down';
        $cats = load_categories($pdo);
        $ids = array_column($cats, 'id');
        $pos = array_search($id, $ids, true);
        if ($pos !== false) {
            $swap = $dir === 'up' ? $pos - 1 : $pos + 1;
            if ($swap >= 0 && $swap < count($cats)) {
                $a = $cats[$pos]; $b = $cats[$swap];
                $upd = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
                $upd->execute([(int) $b['sort_order'], (int) $a['id']]);
                $upd->execute([(int) $a['sort_order'], (int) $b['id']]);
            }
        }
    }

    elseif ($action === 'delete_category') {
        $id = (int) ($_POST['cat_id'] ?? 0);
        $cat = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $cat->execute([$id]);
        $row = $cat->fetch();
        if ($row) {
            $used = $pdo->prepare("SELECT COUNT(*) FROM items WHERE category = ?");
            $used->execute([$row['name']]);
            if ((int) $used->fetchColumn() > 0) {
                $msg = "Impossible : des articles utilisent encore « " . $row['name'] . " »."; $msgType = 'error';
            } else {
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
                $msg = "Catégorie supprimée.";
            }
        }
    }
}

$categories = load_categories($pdo);
$counts = category_item_counts($pdo);

$pageTitle = 'Administration — Catégories';
require __DIR__ . '/lib/header.php';
require __DIR__ . '/lib/admin_nav.php';
?>
<h1 class="admin-h1">🗂️ Catégories</h1>
<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<details class="admin-add">
    <summary>➕ Ajouter une catégorie</summary>
    <form method="post" class="row-form">
        <input type="hidden" name="action" value="add_category">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Icône <input type="text" name="icon" value="🎁" size="2" class="icon-input"></label>
        <label>Nom <input type="text" name="name" required></label>
        <button type="submit">Ajouter</button>
    </form>
</details>

<table class="admin-table">
    <thead>
        <tr><th>Ordre</th><th>Icône</th><th>Nom</th><th>Articles</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $i => $c): ?>
            <tr>
                <td class="nowrap">
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="move_category">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="cat_id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" name="dir" value="up" class="link-btn" <?= $i === 0 ? 'disabled' : '' ?>>▲</button>
                        <button type="submit" name="dir" value="down" class="link-btn" <?= $i === count($categories) - 1 ? 'disabled' : '' ?>>▼</button>
                    </form>
                </td>
                <td colspan="3">
                    <form method="post" class="row-form">
                        <input type="hidden" name="action" value="save_category">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="cat_id" value="<?= (int) $c['id'] ?>">
                        <input type="hidden" name="old_name" value="<?= e($c['name']) ?>">
                        <input type="text" name="icon" value="<?= e($c['icon']) ?>" size="2" class="icon-input">
                        <input type="text" name="name" value="<?= e($c['name']) ?>" required>
                        <span class="muted small nowrap"><?= (int) ($counts[$c['name']] ?? 0) ?> article<?= (int) ($counts[$c['name']] ?? 0) > 1 ? 's' : '' ?></span>
                        <button type="submit">Enregistrer</button>
                    </form>
                </td>
                <td class="nowrap">
                    <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette catégorie ?');">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="cat_id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="link-btn danger-link" <?= (int) ($counts[$c['name']] ?? 0) > 0 ? 'disabled title="Catégorie utilisée"' : '' ?>>supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/lib/footer.php'; ?>
