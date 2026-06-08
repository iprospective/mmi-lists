<?php /** @var ?string $error */ ?>
<?php
// Photo d'accueil (la même que la photo d'en-tête réglée en administration) :
// affichée à gauche du cadre de connexion, sur deux colonnes de hauteur identique.
$headerPhoto = (string) cfg('header_photo', '');
$hasPhoto    = $headerPhoto !== '' && is_file(APP_ROOT . '/img/' . $headerPhoto);
$photoSrc    = $hasPhoto ? url('img/' . rawurlencode($headerPhoto)) . '?v=' . filemtime(APP_ROOT . '/img/' . $headerPhoto) : '';
?>
<div class="login-layout<?= $hasPhoto ? ' login-layout--with-photo' : '' ?>">
    <?php if ($hasPhoto): ?>
        <div class="login-photo">
            <img src="<?= e($photoSrc) ?>" alt="<?= e(cfg('site_title')) ?>">
        </div>
    <?php endif; ?>
    <section class="login-box">
        <h1>👶 <?= e(cfg('site_title')) ?></h1>
        <p class="muted">Cette liste est privée. Entrez le mot de passe qui vous a été communiqué.</p>
        <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" action="<?= e(url('')) ?>" class="stack">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>Mot de passe
                <input type="password" name="password" autofocus required>
            </label>
            <button type="submit">Entrer</button>
        </form>
    </section>
</div>
