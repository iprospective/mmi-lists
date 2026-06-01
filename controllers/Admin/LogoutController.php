<?php
declare(strict_types=1);

namespace Controllers\Admin;

use PDO;

final class LogoutController
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        unset($_SESSION['admin']);
        redirect('');
    }
}
