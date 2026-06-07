<?php
/** @var string $status */
/** @var ?array $owner */
/** @var string $token */
?>
<section class="intro">
    <h1>Validation de réservation</h1>
</section>

<?php if ($status === 'ok'): ?>
    <p class="alert ok">Merci <?= e($owner['guest_name']) ?> ! Votre réservation pour
        « <?= e($owner['item_name'] ?? 'votre cadeau') ?> » est désormais validée 💛</p>
    <p>Vous pouvez retrouver et gérer vos réservations à tout moment grâce à votre lien privé :</p>
    <p><a href="<?= e(url('mes-reservations')) ?>?t=<?= e(rawurlencode($token)) ?>">→ Voir mes réservations</a></p>
    <p><a href="<?= e(url('')) ?>">← Retour à la liste</a></p>
<?php elseif ($status === 'already'): ?>
    <p class="alert ok">Cette réservation a déjà été validée. Tout est bon, rien d'autre à faire 🙂</p>
    <p><a href="<?= e(url('mes-reservations')) ?>?t=<?= e(rawurlencode($token)) ?>">→ Voir mes réservations</a></p>
    <p><a href="<?= e(url('')) ?>">← Retour à la liste</a></p>
<?php else: ?>
    <p class="alert error">Ce lien de validation n'est pas valide (ou la réservation a été supprimée).
        Vérifiez l'adresse depuis l'email reçu.</p>
    <p><a href="<?= e(url('')) ?>">← Retour à la liste</a></p>
<?php endif; ?>
