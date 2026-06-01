<?php
// À inclure en tête de chaque page d'administration.
// Gère la connexion / déconnexion admin et bloque l'accès si non connecté.
require __DIR__ . '/bootstrap.php';

$adminLoginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
    if (check_csrf() && check_password((string) ($_POST['password'] ?? ''), (string) cfg('admin_password'))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: ' . admin_self());
        exit;
    }
    $adminLoginError = "Mot de passe administrateur incorrect.";
}

if (($_GET['logout'] ?? '') === '1') {
    unset($_SESSION['admin']);
    header('Location: index.php');
    exit;
}

if (!is_admin()) {
    $pageTitle = 'Administration';
    require __DIR__ . '/header.php'; ?>
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
        <p class="muted"><a href="index.php">← Retour à la liste</a></p>
    </section>
    <?php require __DIR__ . '/footer.php';
    exit;
}
