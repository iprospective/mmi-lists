<?php
/** @var ?string $msg */
/** @var string $msgType */
require APP_ROOT . '/templates/layout/admin_nav.php';
?>
<h1 class="admin-h1">⚙️ Paramètres</h1>
<?php if ($msg): ?><p class="alert <?= e($msgType) ?>"><?= e($msg) ?></p><?php endif; ?>

<form id="settings-form" method="post" class="stack admin-settings">
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

    <fieldset class="theme-fields">
        <legend>Charte graphique</legend>
        <div class="theme-colors">
            <?php
            $themeDefaults = ['theme_bg' => '#fbf7f2', 'theme_heart' => '#6fae8e', 'theme_button' => '#e9a17c'];
            $themeLabels   = ['theme_bg' => 'Couleur de fond', 'theme_heart' => 'Couleur des cœurs', 'theme_button' => 'Couleur des boutons'];
            foreach ($themeLabels as $key => $lbl):
                $val    = css_color(cfg($key), $themeDefaults[$key]);
                $custom = strtolower($val) !== strtolower($themeDefaults[$key]);
            ?>
                <div class="theme-color">
                    <label><?= e($lbl) ?>
                        <input type="color" name="<?= e($key) ?>" value="<?= e($val) ?>">
                    </label>
                    <?php if ($custom): ?>
                        <button type="submit" form="reset-color-form" name="color" value="<?= e($key) ?>" class="link-btn reset-color">↺ Réinitialiser</button>
                    <?php else: ?>
                        <span class="muted small reset-color">Par défaut</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <button type="submit">Enregistrer</button>
</form>

<?php // Formulaire à part pour réinitialiser une couleur (déclenché par les boutons « Réinitialiser » via l'attribut form). ?>
<form id="reset-color-form" method="post" hidden>
    <input type="hidden" name="action" value="reset_color">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
</form>

<fieldset class="theme-fields">
    <legend>Photo d'en-tête</legend>
    <?php
    $headerPhoto = (string) cfg('header_photo', '');
    $hasHeader = $headerPhoto !== '' && is_file(APP_ROOT . '/img/' . $headerPhoto);
    ?>
    <?php if ($hasHeader): ?>
        <div class="header-preview">
            <img src="<?= e(url('img/' . rawurlencode($headerPhoto)) . '?v=' . filemtime(APP_ROOT . '/img/' . $headerPhoto)) ?>" alt="En-tête actuel">
        </div>
    <?php else: ?>
        <p class="muted small">Aucune photo d'en-tête pour le moment.</p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="stack">
        <input type="hidden" name="action" value="upload_header">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Choisir une image <span class="muted">(JPG, PNG, GIF ou WEBP, 8 Mo max)</span>
            <input type="file" name="header" accept="image/*" required>
        </label>
        <button type="submit"><?= $hasHeader ? 'Remplacer la photo' : 'Ajouter la photo' ?></button>
    </form>

    <?php if ($hasHeader): ?>
        <form method="post" class="inline-remove" onsubmit="return confirm('Retirer la photo d\'en-tête ?');">
            <input type="hidden" name="action" value="remove_header">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="link-btn">Retirer la photo</button>
        </form>
    <?php endif; ?>

    <?php
    // Affichage de la photo : rattaché au formulaire principal (form="settings-form").
    $headerPos = (string) cfg('header_position', 'banner');
    $headerFmt = (string) cfg('header_format', 'cover');
    ?>
    <div class="header-display">
        <label>Position
            <select name="header_position" form="settings-form">
                <option value="banner" <?= $headerPos === 'banner' ? 'selected' : '' ?>>Bandeau en haut</option>
                <option value="right" <?= $headerPos === 'right' ? 'selected' : '' ?>>À droite du texte</option>
                <option value="left" <?= $headerPos === 'left' ? 'selected' : '' ?>>À gauche du texte</option>
            </select>
        </label>
        <label>Format
            <select name="header_format" form="settings-form">
                <option value="cover" <?= $headerFmt === 'cover' ? 'selected' : '' ?>>Rogné (remplit le cadre)</option>
                <option value="contain" <?= $headerFmt === 'contain' ? 'selected' : '' ?>>Image entière</option>
            </select>
        </label>
        <button type="submit" form="settings-form" class="secondary">Enregistrer l'affichage</button>
    </div>
    <p class="muted small">La position et le format s'appliquent dès qu'une photo d'en-tête est définie.</p>
</fieldset>

<p class="muted small">Le mot de passe administrateur se modifie dans <code>config.php</code>.</p>

<script src="<?= e(url('assets/wysiwyg.js')) ?>" defer></script>
