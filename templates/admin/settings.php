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

    <div class="field">
        <span class="field-label">Texte d'introduction</span>
        <div class="wysiwyg" data-target="intro-field">
            <div class="wysiwyg-toolbar">
                <button type="button" data-cmd="bold" title="Gras"><b>B</b></button>
                <button type="button" data-cmd="italic" title="Italique"><i>I</i></button>
                <button type="button" data-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
                <button type="button" data-cmd="createLink" title="Insérer un lien">🔗 Lien</button>
                <button type="button" data-cmd="unlink" title="Retirer le lien">Retirer lien</button>
                <button type="button" data-cmd="removeFormat" title="Effacer la mise en forme">✗ Nettoyer</button>
            </div>
            <div id="intro-editor" class="wysiwyg-editor" contenteditable="true"><?= cfg('intro') ?></div>
        </div>
        <textarea id="intro-field" name="intro" class="wysiwyg-source" hidden><?= e(cfg('intro')) ?></textarea>
    </div>

    <label>Parents <span class="muted">(affiché en bas de page)</span>
        <input type="text" name="parents" value="<?= e(cfg('parents')) ?>">
    </label>

    <label>Mot de passe visiteurs <span class="muted">(laisser vide pour ne pas changer)</span>
        <input type="text" name="guest_password" value="" placeholder="•••••••• (inchangé)" autocomplete="off">
    </label>

    <button type="submit">Enregistrer</button>
</form>

<p class="muted small">Le mot de passe administrateur se modifie dans <code>config.php</code>.</p>

<script>
(function () {
    document.querySelectorAll('.wysiwyg').forEach(function (box) {
        var editor = box.querySelector('.wysiwyg-editor');
        var field  = document.getElementById(box.dataset.target);
        if (!editor || !field) return;

        function sync() { field.value = editor.innerHTML.trim(); }

        box.querySelectorAll('[data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cmd = btn.dataset.cmd;
                if (cmd === 'createLink') {
                    var url = prompt('Adresse du lien (https://…)');
                    if (url) { document.execCommand('createLink', false, url); }
                } else {
                    document.execCommand(cmd, false, null);
                }
                editor.focus();
                sync();
            });
        });

        editor.addEventListener('input', sync);
        var form = box.closest('form');
        if (form) { form.addEventListener('submit', sync); }
        sync();
    });
})();
</script>
