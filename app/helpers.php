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

// Indique si un article possède une vraie photo (sinon photo_url renvoie le visuel par défaut).
function has_photo(array $item): bool {
    $dir = APP_ROOT . '/img/products/';
    if (!empty($item['photo']) && is_file($dir . $item['photo'])) {
        return true;
    }
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
        if (is_file($dir . ($item['slug'] ?? '') . '.' . $ext)) {
            return true;
        }
    }
    return false;
}

// Valide une couleur hexadécimale (#abc ou #aabbcc) saisie en administration, pour
// éviter toute injection dans le bloc <style> (e() n'échappe pas ; { } des CSS).
function css_color(?string $value, string $default): string {
    $value = trim((string) $value);
    return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) ? $value : $default;
}

// Éclaircit (pourcentage positif) ou assombrit (négatif) une couleur hexadécimale.
// Sert à dériver la teinte foncée des boutons à partir de la couleur choisie.
function shade_color(string $hex, float $percent): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6) {
        return '#' . $hex;
    }
    $out = '#';
    for ($i = 0; $i < 3; $i++) {
        $c = (int) hexdec(substr($hex, $i * 2, 2));
        $c = (int) round($c + ($percent / 100) * ($percent < 0 ? $c : 255 - $c));
        $out .= str_pad(dechex(max(0, min(255, $c))), 2, '0', STR_PAD_LEFT);
    }
    return $out;
}

function leboncoin_url(string $terms): string {
    return 'https://www.leboncoin.fr/recherche?text=' . rawurlencode($terms);
}
function vinted_url(string $terms): string {
    return 'https://www.vinted.fr/catalog?search_text=' . rawurlencode($terms);
}

// Assainit du HTML issu de l'éditeur WYSIWYG : ne conserve qu'une liste blanche de
// balises, supprime tous les attributs (sauf un href sûr sur les liens) et neutralise
// scripts et gestionnaires d'événements. Saisie réservée à l'administrateur, mais on
// reste prudent car la valeur est ensuite affichée telle quelle.
function sanitize_html(string $html): string {
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><blockquote><div>';
    $html = strip_tags($html, $allowed);

    $html = preg_replace_callback('/<(\/?)([a-z0-9]+)([^>]*)>/i', static function (array $m): string {
        $close = $m[1];
        $tag   = strtolower($m[2]);
        // L'éditeur insère parfois des <div> pour les sauts de ligne : on les traite comme des paragraphes.
        if ($tag === 'div') {
            $tag = 'p';
        }
        if ($close === '/') {
            return "</$tag>";
        }
        if ($tag === 'a') {
            if (preg_match('/\bhref\s*=\s*("|\')(.*?)\1/i', $m[3], $h)) {
                $href = trim(html_entity_decode($h[2], ENT_QUOTES, 'UTF-8'));
                if (preg_match('#^(https?://|mailto:|/|\#)#i', $href)) {
                    return '<a href="' . e($href) . '" target="_blank" rel="noopener nofollow">';
                }
            }
            return '<a>';
        }
        return "<$tag>"; // toute autre balise autorisée : on retire ses attributs
    }, $html);

    return trim((string) $html);
}
