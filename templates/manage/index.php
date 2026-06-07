<?php
/** @var ?array $owner */
/** @var array $reservations */
/** @var string $token */
/** @var ?array $flash */
?>
<section class="intro">
    <h1>Mes réservations</h1>
</section>

<?php if ($flash): ?>
    <p class="alert <?= e($flash['type'] ?? 'ok') ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if (!$owner): ?>
    <p class="alert error">Ce lien n'est pas valide (ou la réservation a été supprimée).
        Vérifiez l'adresse depuis l'email reçu.</p>
    <p><a href="<?= e(url('')) ?>">← Retour à la liste</a></p>
<?php elseif (empty($reservations)): ?>
    <p>Vous n'avez plus aucune réservation en cours.</p>
    <p><a href="<?= e(url('')) ?>">← Retour à la liste</a></p>
<?php else: ?>
    <p class="muted">Bonjour <?= e($owner['guest_name']) ?>, voici vos réservations. Vous pouvez en annuler à tout moment.</p>
    <ul class="manage-list">
        <?php foreach ($reservations as $r): ?>
            <li class="manage-item">
                <span class="manage-name">
                    <?= e($r['item_name'] ?? '— cadeau supprimé —') ?>
                    <?php if ((int) $r['quantity'] > 1): ?><span class="qbadge">×<?= (int) $r['quantity'] ?></span><?php endif; ?>
                </span>
                <form method="post" action="<?= e(url('mes-reservations/cancel')) ?>"
                      onsubmit="return confirm('Annuler cette réservation ?');">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button type="submit" class="link-btn">annuler</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <p><a href="<?= e(url('')) ?>">← Retour à la liste</a></p>
<?php endif; ?>
