<?php
declare(strict_types=1);

namespace Controllers\Admin;

use PDO;
use Services\Auth;

// Contrôleur de base de l'administration : connexion admin et garde d'accès.
abstract class BaseAdminController
{
    protected string $adminPage = '';
    protected ?string $msg = null;
    protected string $msgType = 'ok';

    public function __construct(protected PDO $pdo)
    {
        $loginError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
            if (check_csrf() && Auth::checkPassword((string) ($_POST['password'] ?? ''), (string) cfg('admin_password'))) {
                Auth::loginAdmin();
                redirect(ltrim($GLOBALS['ROUTE_PATH'] ?? '/', '/'));
            }
            $loginError = "Mot de passe administrateur incorrect.";
        }

        if (!Auth::isAdmin()) {
            view('admin/login', ['adminLoginError' => $loginError, 'pageTitle' => 'Administration']);
            exit;
        }
    }

    // Rend un template d'admin en injectant les variables communes.
    protected function render(string $template, array $data = []): void
    {
        $data['adminPage'] = $this->adminPage;
        $data['msg']       = $this->msg;
        $data['msgType']   = $this->msgType;
        $data['pageTitle'] = $data['pageTitle'] ?? 'Administration';
        view($template, $data);
    }
}
