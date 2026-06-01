<?php
declare(strict_types=1);

namespace Controllers;

use PDO;
use Services\Auth;
use Services\ItemService;

final class HomeController
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
            if (!check_csrf()) {
                $error = "Session expirée, merci de réessayer.";
            } elseif (Auth::checkPassword((string) ($_POST['password'] ?? ''), (string) cfg('guest_password'))) {
                Auth::loginGuest();
                redirect('');
            } else {
                $error = "Mot de passe incorrect.";
            }
        }

        if (!Auth::isGuest()) {
            view('home/login', ['error' => $error]);
            return;
        }

        $items = (new ItemService($this->pdo))->all();

        $byCat = [];
        $catIcons = [];
        foreach ($items as $it) {
            $byCat[$it['category']][] = $it;
            $catIcons[$it['category']] = $it['category_icon'] ?? '🎁';
        }

        view('home/list', [
            'byCat'    => $byCat,
            'catIcons' => $catIcons,
            'flash'    => take_flash(),
            'myTokens' => Auth::myTokens(),
        ]);
    }
}
