<?php
/** @var array $people */
/** @var ?string $msg */
/** @var string $msgType */
require APP_ROOT . '/templates/layout/admin_nav.php';
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
