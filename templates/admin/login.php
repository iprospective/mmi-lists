<?php /** @var ?string $adminLoginError */ ?>
<section class="login-box">
    <h1>🔧 Administration</h1>
    <?php if ($adminLoginError): ?><p class="alert error"><?= e($adminLoginError) ?></p><?php endif; ?>
    <form method="post" class="stack">
        <input type="hidden" name="action" value="admin_login">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Mot de passe administrateur
            <input type="password" name="password" autofocus required>
        </label>
        <button type="submit">Entrer</button>
    </form>
    <p class="muted"><a href="<?= e(url('')) ?>">← Retour à la liste</a></p>
</section>
