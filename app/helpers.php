<?php
declare(strict_types=1);

use Services\Auth;

// Échappement HTML.
function e(?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Lecture d'un paramètre de configuration (config.php surchargé par la table settings).
function cfg(string $key, $default = null) {
    return $GLOBALS['CONFIG'][$key] ?? $default;
}

// Construit une URL interne en tenant compte du sous-répertoire d'installation.
function url(string $path = ''): string {
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    return $base . '/' . ltrim($path, '/');
}

// Redirection vers une route interne.
function redirect(string $path = ''): never {
    header('Location: ' . url($path));
    exit;
}

function is_guest(): bool { return Auth::isGuest(); }
function is_admin(): bool { return Auth::isAdmin(); }

// Jeton CSRF de session.
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function check_csrf(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

// Messages flash (affichés après une redirection).
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function take_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// Rendu d'un template avec son layout (header + footer).
function view(string $template, array $data = []): void {
    $data['pageTitle'] = $data['pageTitle'] ?? cfg('site_title');
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/templates/layout/header.php';
    require APP_ROOT . '/templates/' . $template . '.php';
    require APP_ROOT . '/templates/layout/footer.php';
}

// URL de la photo d'un article (préfixée pour fonctionner sur toutes les routes).
function photo_url(array $item): string {
    $dir = APP_ROOT . '/img/products/';
    if (!empty($item['photo']) && is_file($dir . $item['photo'])) {
        return url('img/products/' . rawurlencode($item['photo'])) . '?v=' . filemtime($dir . $item['photo']);
    }
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
        $f = $item['slug'] . '.' . $ext;
        if (is_file($dir . $f)) {
            return url('img/products/' . rawurlencode($f)) . '?v=' . filemtime($dir . $f);
        }
    }
    return url('assets/placeholder.svg');
}

function leboncoin_url(string $terms): string {
    return 'https://www.leboncoin.fr/recherche?text=' . rawurlencode($terms);
}
function vinted_url(string $terms): string {
    return 'https://www.vinted.fr/catalog?search_text=' . rawurlencode($terms);
}
