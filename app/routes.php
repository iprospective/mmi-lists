<?php
declare(strict_types=1);

use App\Router;
use Controllers\HomeController;
use Controllers\ReserveController;
use Controllers\CancelController;
use Controllers\LogoutController;
use Controllers\Admin\ItemsController;
use Controllers\Admin\CategoriesController;
use Controllers\Admin\ReservationsController;
use Controllers\Admin\PeopleController;
use Controllers\Admin\SettingsController;
use Controllers\Admin\LogoutController as AdminLogoutController;

$router = new Router();

// --- Espace visiteurs ---
$router->any('/',        [HomeController::class, 'index']);
$router->get('/logout',  [LogoutController::class, 'index']);
$router->post('/reserve', [ReserveController::class, 'store']);
$router->post('/cancel',  [CancelController::class, 'store']);

// --- Administration ---
$router->any('/admin',              [ItemsController::class, 'index']);
$router->any('/admin/categories',   [CategoriesController::class, 'index']);
$router->any('/admin/reservations', [ReservationsController::class, 'index']);
$router->any('/admin/people',       [PeopleController::class, 'index']);
$router->any('/admin/settings',     [SettingsController::class, 'index']);
$router->get('/admin/logout',       [AdminLogoutController::class, 'index']);

return $router;
