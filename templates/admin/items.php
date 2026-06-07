<?php
/** @var array $items */
/** @var array $categories */
/** @var ?string $msg */
/** @var string $msgType */
require APP_ROOT . '/templates/layout/admin_nav.php';
?>
<h1 class="admin-h1">📷 Articles &amp; photos</h1>
<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<details class="admin-add">
    <summary>➕ Ajouter un article</summary>
    <form method="post" class="stack">
        <input type="hidden" name="action" value="add_item">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Nom <input type="text" name="name" required></label>
        <label>Catégorie
            <select name="category">
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['name']) ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Ajouter</button>
    </form>
</details>

<div class="admin-list">
    <?php foreach ($items as $it): ?>
        <article class="admin-item">
            <div class="admin-thumb">
                <img src="<?= e(photo_url($it)) ?>" alt="">
            </div>
            <div class="admin-fields">
                <p class="muted small">
                    <?= e($it['category_icon'] ?? '🎁') ?> <?= e($it['category']) ?>
                    · réservé <?= (int) $it['reserved'] ?><?= $it['qty_needed'] !== null ? ' / ' . (int) $it['qty_needed'] : ' (illimité)' ?>
                </p>

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
                    <label>Catégorie
                        <select name="category">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c['name']) ?>" <?= $c['name'] === $it['category'] ? 'selected' : '' ?>>
                                    <?= e($c['icon']) ?> <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (!in_array($it['category'], array_column($categories, 'name'), true)): ?>
                                <option value="<?= e($it['category']) ?>" selected><?= e($it['category']) ?> (hors liste)</option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label>Description <textarea name="description" rows="2"><?= e($it['description']) ?></textarea></label>
                    <label>Quantité souhaitée <span class="muted">(vide = illimité)</span>
                        <input type="text" name="qty_needed" value="<?= $it['qty_needed'] === null ? '' : (int) $it['qty_needed'] ?>">
                    </label>
                    <label>Mots-clés occasion <span class="muted">(vide = pas de liens)</span>
                        <input type="text" name="search" value="<?= e($it['search']) ?>">
                    </label>
                    <label>Niveau de besoin
                        <select name="priority">
                            <option value="0" <?= (int) ($it['priority'] ?? 0) === 0 ? 'selected' : '' ?>>Normal</option>
                            <option value="1" <?= (int) ($it['priority'] ?? 0) === 1 ? 'selected' : '' ?>>+ (utile)</option>
                            <option value="2" <?= (int) ($it['priority'] ?? 0) === 2 ? 'selected' : '' ?>>++ (très utile)</option>
                        </select>
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="needed_early" value="1" <?= (int) ($it['needed_early'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <span>⏱ Besoin tôt</span>
                    </label>
                    <button type="submit">Enregistrer</button>
                </form>

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
