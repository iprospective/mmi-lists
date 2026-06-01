<?php
$adminPage = 'settings';
require __DIR__ . '/lib/admin_auth.php';

$msg = null; $msgType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf() && ($_POST['action'] ?? '') === 'save_settings') {
    $title   = trim((string) ($_POST['site_title'] ?? ''));
    $intro   = trim((string) ($_POST['intro'] ?? ''));
    $parents = trim((string) ($_POST['parents'] ?? ''));
    $pwd     = (string) ($_POST['guest_password'] ?? '');

    if ($title === '') {
        $msg = "Le titre du site ne peut pas être vide."; $msgType = 'error';
    } else {
        set_setting($pdo, 'site_title', $title);
        set_setting($pdo, 'intro', $intro);
        set_setting($pdo, 'parents', $parents);
        // On ne vide pas le mot de passe si le champ est laissé vide.
        if ($pwd !== '') {
            set_setting($pdo, 'guest_password', $pwd);
        }
        // Recharge $CONFIG pour refléter les nouvelles valeurs sur cette page.
        foreach ($pdo->query("SELECT key, value FROM settings") as $row) {
            $CONFIG[$row['key']] = $row['value'];
        }
        $msg = "Paramètres enregistrés.";
    }
}

$pageTitle = 'Administration — Paramètres';
require __DIR__ . '/lib/header.php';
require __DIR__ . '/lib/admin_nav.php';
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

<?php require __DIR__ . '/lib/footer.php'; ?>
