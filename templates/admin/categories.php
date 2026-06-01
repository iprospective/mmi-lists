<?php
/** @var array $categories */
/** @var array $counts */
/** @var ?string $msg */
/** @var string $msgType */
require APP_ROOT . '/templates/layout/admin_nav.php';
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
        <?php foreach ($categories as $i => $c): $n = (int) ($counts[$c['name']] ?? 0); ?>
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
                        <span class="muted small nowrap"><?= $n ?> article<?= $n > 1 ? 's' : '' ?></span>
                        <button type="submit">Enregistrer</button>
                    </form>
                </td>
                <td class="nowrap">
                    <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette catégorie ?');">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="cat_id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="link-btn danger-link" <?= $n > 0 ? 'disabled title="Catégorie utilisée"' : '' ?>>supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
