<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Services\SettingService;

final class SettingsController extends BaseAdminController
{
    protected string $adminPage = 'settings';

    public function index(): void
    {
        $settings = new SettingService($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf()) {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'save_settings') {
                $this->saveSettings($settings);
            } elseif ($action === 'upload_header') {
                $this->uploadHeader($settings);
            } elseif ($action === 'remove_header') {
                $this->removeHeader($settings);
            } elseif ($action === 'reset_color') {
                $this->resetColor($settings);
            }
            // Reflète les nouvelles valeurs immédiatement sur cette page.
            $GLOBALS['CONFIG'] = $settings->overlay($GLOBALS['CONFIG']);
        }

        $this->render('admin/settings');
    }

    private function saveSettings(SettingService $settings): void
    {
        $title = trim((string) ($_POST['site_title'] ?? ''));
        if ($title === '') {
            $this->msg = "Le titre du site ne peut pas être vide.";
            $this->msgType = 'error';
            return;
        }
        $settings->set('site_title', $title);
        $settings->set('intro', sanitize_html((string) ($_POST['intro'] ?? '')));
        $settings->set('parents', trim((string) ($_POST['parents'] ?? '')));
        // On ne vide pas le mot de passe si le champ est laissé vide.
        $pwd = (string) ($_POST['guest_password'] ?? '');
        if ($pwd !== '') {
            $settings->set('guest_password', $pwd);
        }
        // Charte graphique : couleurs validées en hexa pour éviter toute injection CSS.
        $settings->set('theme_bg', css_color($_POST['theme_bg'] ?? null, '#fbf7f2'));
        $settings->set('theme_heart', css_color($_POST['theme_heart'] ?? null, '#6fae8e'));
        $settings->set('theme_button', css_color($_POST['theme_button'] ?? null, '#e9a17c'));
        // Affichage de la photo d'en-tête : position et format (valeurs imposées).
        $pos = (string) ($_POST['header_position'] ?? 'banner');
        $settings->set('header_position', in_array($pos, ['banner', 'left', 'right'], true) ? $pos : 'banner');
        $fmt = (string) ($_POST['header_format'] ?? 'cover');
        $settings->set('header_format', in_array($fmt, ['cover', 'contain'], true) ? $fmt : 'cover');
        $this->msg = "Paramètres enregistrés.";
    }

    // Restaure une couleur de la charte à sa valeur par défaut (supprime la personnalisation).
    private function resetColor(SettingService $settings): void
    {
        $key = (string) ($_POST['color'] ?? '');
        if (!in_array($key, SettingService::THEME_COLORS, true)) {
            $this->msg = "Couleur inconnue.";
            $this->msgType = 'error';
            return;
        }
        $settings->set($key, SettingService::DEFAULTS[$key]);
        $this->msg = "Couleur réinitialisée à sa valeur par défaut.";
    }

    private function uploadHeader(SettingService $settings): void
    {
        $file = $_FILES['header'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->msg = "Aucun fichier reçu.";
            $this->msgType = 'error';
            return;
        }
        $info = @getimagesize($file['tmp_name']);
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($allowed[$info[2]]) || $file['size'] > 8 * 1024 * 1024) {
            $this->msg = "Fichier invalide : JPG, PNG, GIF ou WEBP, 8 Mo max.";
            $this->msgType = 'error';
            return;
        }
        $ext = $allowed[$info[2]];
        $dir = APP_ROOT . '/img/';
        // Supprime les anciennes en-têtes (quelle que soit l'extension).
        foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $oldExt) {
            $old = $dir . 'header.' . $oldExt;
            if (is_file($old)) {
                @unlink($old);
            }
        }
        $fname = 'header.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $fname)) {
            $this->msg = "Échec de l'enregistrement du fichier (droits sur img/ ?).";
            $this->msgType = 'error';
            return;
        }
        $settings->set('header_photo', $fname);
        $this->msg = "Photo d'en-tête mise à jour.";
    }

    private function removeHeader(SettingService $settings): void
    {
        $current = (string) cfg('header_photo', '');
        if ($current !== '' && is_file(APP_ROOT . '/img/' . $current)) {
            @unlink(APP_ROOT . '/img/' . $current);
        }
        $settings->set('header_photo', '');
        $this->msg = "Photo d'en-tête retirée.";
    }
}
