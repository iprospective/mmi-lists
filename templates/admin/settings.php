<?php
/** @var ?string $msg */
/** @var string $msgType */
require APP_ROOT . '/templates/layout/admin_nav.php';
?>
<h1 class="admin-h1">⚙️ Paramètres</h1>
<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<form method="post" class="stack admin-settings">
    <input type="hidden" name="action" value="save_settings">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>Titre du site
        <input type="text" name="site_title" value="<?= e(cfg('site_title')) ?>" required>
    </label>

    <label>Texte d'introduction
        <textarea name="intro" rows="3"><?= e(cfg('intro')) ?></textarea>
    </label>

    <label>Parents <span class="muted">(affiché en bas de page)</span>
        <input type="text" name="parents" value="<?= e(cfg('parents')) ?>">
    </label>

    <label>Mot de passe visiteurs <span class="muted">(laisser vide pour ne pas changer)</span>
        <input type="text" name="guest_password" value="" placeholder="•••••••• (inchangé)" autocomplete="off">
    </label>

    <button type="submit">Enregistrer</button>
</form>

<p class="muted small">Le mot de passe administrateur se modifie dans <code>config.php</code>.</p>
