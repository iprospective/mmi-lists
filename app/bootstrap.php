<?php
declare(strict_types=1);

// Amorçage de l'application : session, configuration, autoload, base de données,
// puis routage de la requête courante. Appelé par le front controller (index.php).

session_start();

// --- Chemin de base (gère une installation dans un sous-répertoire) ---
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
define('BASE_PATH', $scriptDir === '/' ? '' : rtrim($scriptDir, '/'));

// --- Configuration ---
$configFile = APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit("config.php manquant. Copiez config.example.php en config.php puis personnalisez-le.");
}
$GLOBALS['CONFIG'] = require $configFile;

// --- Autoloader (App\, Services\, Controllers\) ---
spl_autoload_register(static function (string $class): void {
    $map = [
        'App\\'         => APP_ROOT . '/app/',
        'Services\\'    => APP_ROOT . '/services/',
        'Controllers\\' => APP_ROOT . '/controllers/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $dir . $rel . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require APP_ROOT . '/app/helpers.php';

// --- Base de données + paramètres ---
$pdo = Services\Database::connect(
    APP_ROOT . '/data/liste.sqlite',
    APP_ROOT . '/app/catalog.php'
);

$settings = new Services\SettingService($pdo);
$settings->seedDefaults($GLOBALS['CONFIG']);
$GLOBALS['CONFIG'] = $settings->overlay($GLOBALS['CONFIG']);

// --- Détermination de la route demandée ---
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);
if (BASE_PATH !== '' && str_starts_with($uri, BASE_PATH)) {
    $uri = substr($uri, strlen(BASE_PATH));
}
$path = '/' . trim($uri, '/');
$GLOBALS['ROUTE_PATH'] = $path;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/** @var App\Router $router */
$router = require APP_ROOT . '/app/routes.php';
$router->dispatch($method, $path, $pdo);
