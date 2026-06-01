<?php /** @var ?string $error */ ?>
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
