<?php
declare(strict_types=1);

namespace Controllers;

use PDO;

final class LogoutController
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        $_SESSION = [];
        session_destroy();
        redirect('');
    }
}
