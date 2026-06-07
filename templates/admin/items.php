<?php
/** @var array $items */
/** @var int $totalCount */
/** @var array $categories */
/** @var string $filterCat */
/** @var string $filterPrio */
/** @var string $filterEarly */
/** @var bool $filterActive */
/** @var ?string $msg */
/** @var string $msgType */
require APP_ROOT . '/templates/layout/admin_nav.php';
// La réorganisation n'a de sens que si une catégorie est affichée en entier.
$reorderable = ($filterPrio === '' && $filterEarly === '');
$shown = count($items);
?>
<h1 class="admin-h1">📷 Articles &amp; photos</h1>
<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<form method="get" action="<?= e(url('admin')) ?>" class="admin-filter row-form">
    <label>Catégorie
        <select name="category">
            <option value="">Toutes</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['name']) ?>" <?= $filterCat === $c['name'] ? 'selected' : '' ?>><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Utilité
        <select name="priority">
            <option value="">Toutes</option>
            <option value="0" <?= $filterPrio === '0' ? 'selected' : '' ?>>Normale</option>
            <option value="1" <?= $filterPrio === '1' ? 'selected' : '' ?>>+ Utile</option>
            <option value="2" <?= $filterPrio === '2' ? 'selected' : '' ?>>++ Très utile</option>
        </select>
    </label>
    <label class="checkbox">
        <input type="checkbox" name="early" value="1" <?= $filterEarly === '1' ? 'checked' : '' ?>>
        <span>⏱ Besoin tôt</span>
    </label>
    <button type="submit">Filtrer</button>
    <?php if ($filterActive): ?>
        <a href="<?= e(url('admin')) ?>" class="link-btn">réinitialiser</a>
    <?php endif; ?>
</form>

<p class="muted small">
    <?= $shown ?> article<?= $shown > 1 ? 's' : '' ?> affiché<?= $shown > 1 ? 's' : '' ?><?= $filterActive ? ' sur ' . (int) $totalCount : '' ?>.
    <?php if (!$reorderable): ?>
        <span>Réorganisation indisponible tant qu'un filtre « utilité » ou « besoin tôt » est actif.</span>
    <?php endif; ?>
</p>

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

<?php
$byCat = [];
foreach ($items as $it) {
    $byCat[$it['category']][] = $it;
}
?>
<?php if ($shown === 0): ?>
<p class="alert">Aucun article ne correspond à ces filtres.</p>
<?php endif; ?>
<?php foreach ($byCat as $cat => $catItems): ?>
<h2 class="cat-title"><?= e($catItems[0]['category_icon'] ?? '🎁') ?> <?= e($cat) ?> <span class="muted small">(<?= count($catItems) ?>)</span></h2>
<div class="admin-list">
    <?php foreach ($catItems as $idx => $it): ?>
        <article class="admin-item" id="item-<?= (int) $it['id'] ?>">
            <div class="admin-thumb">
                <img src="<?= e(photo_url($it)) ?>" alt="">
            </div>
            <div class="admin-fields">
                <p class="muted small admin-item-meta">
                    <span class="admin-move">
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="move_item">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                            <button type="submit" name="dir" value="up" class="link-btn" <?= (!$reorderable || $idx === 0) ? 'disabled' : '' ?> title="Monter">▲</button>
                            <button type="submit" name="dir" value="down" class="link-btn" <?= (!$reorderable || $idx === count($catItems) - 1) ? 'disabled' : '' ?> title="Descendre">▼</button>
                        </form>
                    </span>
                    réservé <?= (int) $it['reserved'] ?><?= $it['qty_needed'] !== null ? ' / ' . (int) $it['qty_needed'] : ' (illimité)' ?>
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
                    <div class="field">
                        <span class="field-label">Description</span>
                        <div class="wysiwyg" data-target="desc-field-<?= (int) $it['id'] ?>">
                            <div class="wysiwyg-toolbar">
                                <button type="button" data-cmd="bold" title="Gras"><b>B</b></button>
                                <button type="button" data-cmd="italic" title="Italique"><i>I</i></button>
                                <button type="button" data-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
                                <button type="button" data-cmd="createLink" title="Insérer un lien">🔗 Lien</button>
                                <button type="button" data-cmd="unlink" title="Retirer le lien">Retirer lien</button>
                                <button type="button" data-cmd="removeFormat" title="Effacer la mise en forme">✗ Nettoyer</button>
                            </div>
                            <div class="wysiwyg-editor" contenteditable="true"><?= sanitize_html((string) $it['description']) ?></div>
                        </div>
                        <textarea id="desc-field-<?= (int) $it['id'] ?>" name="description" hidden><?= e($it['description']) ?></textarea>
                    </div>
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
<?php endforeach; ?>

<script src="<?= e(url('assets/wysiwyg.js')) ?>" defer></script>
