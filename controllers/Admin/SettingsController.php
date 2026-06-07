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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_csrf() && ($_POST['action'] ?? '') === 'save_settings') {
            $title = trim((string) ($_POST['site_title'] ?? ''));
            if ($title === '') {
                $this->msg = "Le titre du site ne peut pas être vide.";
                $this->msgType = 'error';
            } else {
                $settings->set('site_title', $title);
                $settings->set('intro', sanitize_html((string) ($_POST['intro'] ?? '')));
                $settings->set('parents', trim((string) ($_POST['parents'] ?? '')));
                // On ne vide pas le mot de passe si le champ est laissé vide.
                $pwd = (string) ($_POST['guest_password'] ?? '');
                if ($pwd !== '') {
                    $settings->set('guest_password', $pwd);
                }
                // Reflète les nouvelles valeurs immédiatement sur cette page.
                $GLOBALS['CONFIG'] = $settings->overlay($GLOBALS['CONFIG']);
                $this->msg = "Paramètres enregistrés.";
            }
        }

        $this->render('admin/settings');
    }
}
