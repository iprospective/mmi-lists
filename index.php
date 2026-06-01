<?php
declare(strict_types=1);

// Front controller : point d'entrée unique de l'application.
define('APP_ROOT', __DIR__);

// Sous le serveur intégré de PHP, laisser servir directement les fichiers existants.
if (PHP_SAPI === 'cli-server') {
    $file = APP_ROOT . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require APP_ROOT . '/app/bootstrap.php';
