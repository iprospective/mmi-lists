<?php
// Initialisation commune à toutes les pages.
declare(strict_types=1);

session_start();

$ROOT = dirname(__DIR__);

// --- Configuration ---
$configFile = $ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit("config.php manquant. Copiez config.example.php en config.php puis personnalisez-le.");
}
$CONFIG = require $configFile;

// --- Base de données SQLite ---
$dataDir = $ROOT . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0775, true);
}
$dbFile = $dataDir . '/liste.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbFile, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (Throwable $e) {
    http_response_code(500);
    exit("Impossible d'ouvrir la base de données. Vérifiez que le dossier 'data/' est accessible en écriture.");
}

// --- Schéma ---
$pdo->exec("
    CREATE TABLE IF NOT EXISTS items (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        slug        TEXT UNIQUE NOT NULL,
        category    TEXT NOT NULL,
        name        TEXT NOT NULL,
        description TEXT NOT NULL DEFAULT '',
        qty_needed  INTEGER,            -- NULL = illimité
        search      TEXT NOT NULL DEFAULT '',
        photo       TEXT,               -- nom de fichier dans img/products/, ou NULL
        sort_order  INTEGER NOT NULL DEFAULT 0
    );
");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS reservations (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id     INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
        guest_name  TEXT NOT NULL,
        guest_email TEXT NOT NULL DEFAULT '',
        quantity    INTEGER NOT NULL DEFAULT 1,
        token       TEXT NOT NULL,
        created_at  TEXT NOT NULL DEFAULT (datetime('now'))
    );
");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        name       TEXT UNIQUE NOT NULL,
        icon       TEXT NOT NULL DEFAULT '🎁',
        sort_order INTEGER NOT NULL DEFAULT 0
    );
");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT NOT NULL DEFAULT ''
    );
");

// --- Pré-remplissage au premier lancement ---
$count = (int) $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
if ($count === 0) {
    $catalog = require __DIR__ . '/catalog.php';
    $ins = $pdo->prepare("
        INSERT INTO items (slug, category, name, description, qty_needed, search, sort_order)
        VALUES (:slug, :category, :name, :description, :qty_needed, :search, :sort_order)
    ");
    foreach ($catalog as $i => $row) {
        $ins->execute([
            ':slug'        => $row['slug'],
            ':category'    => $row['category'],
            ':name'        => $row['name'],
            ':description' => $row['description'],
            ':qty_needed'  => $row['qty_needed'],
            ':search'      => $row['search'],
            ':sort_order'  => $i,
        ]);
    }
}

// --- Catégories : déduites des articles si la table est vide ---
$catCount = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($catCount === 0) {
    $defaultIcons = [
        'Soin & Hygiène'              => '🧴',
        'Allaitement & Alimentation'  => '🍼',
        'Vêtements'                   => '👕',
        'Meubles & Mobilier'          => '🛏️',
        'Sécurité'                    => '🛡️',
        'Jouets'                      => '🧸',
        'Voyage & Transport'          => '🚲',
        'Autres (coups de cœur)'      => '💛',
    ];
    $cats = $pdo->query("SELECT category, MIN(sort_order) AS o FROM items GROUP BY category ORDER BY o")->fetchAll();
    $insC = $pdo->prepare("INSERT OR IGNORE INTO categories (name, icon, sort_order) VALUES (?, ?, ?)");
    foreach ($cats as $i => $c) {
        $insC->execute([$c['category'], $defaultIcons[$c['category']] ?? '🎁', $i]);
    }
}

// --- Paramètres modifiables : initialisés depuis config.php au 1er lancement ---
$has = $pdo->prepare("SELECT 1 FROM settings WHERE key = ?");
$insS = $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
foreach (['site_title', 'intro', 'parents', 'guest_password'] as $k) {
    $has->execute([$k]);
    if (!$has->fetchColumn() && isset($CONFIG[$k])) {
        $insS->execute([$k, (string) $CONFIG[$k]]);
    }
}
// La base fait foi pour ces paramètres : on surcharge $CONFIG.
foreach ($pdo->query("SELECT key, value FROM settings") as $row) {
    $CONFIG[$row['key']] = $row['value'];
}

// =====================================================================
//  Fonctions utilitaires
// =====================================================================

function cfg(string $key, $default = null) {
    global $CONFIG;
    return $CONFIG[$key] ?? $default;
}

function e(?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function is_guest(): bool {
    return !empty($_SESSION['guest']);
}

function is_admin(): bool {
    return !empty($_SESSION['admin']);
}

function require_guest(): void {
    if (!is_guest()) {
        header('Location: index.php');
        exit;
    }
}

// Nom de la page admin courante (pour les redirections, sans risque de redirection ouverte).
function admin_self(): string {
    return basename($_SERVER['SCRIPT_NAME'] ?? 'admin.php');
}

// Lecture / écriture d'un paramètre en base.
function set_setting(PDO $pdo, string $key, string $value): void {
    $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)
                   ON CONFLICT(key) DO UPDATE SET value = excluded.value")
        ->execute([$key, $value]);
}

function load_categories(PDO $pdo): array {
    return $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
}

// Nombre d'articles par catégorie (indexé par nom de catégorie).
function category_item_counts(PDO $pdo): array {
    $out = [];
    foreach ($pdo->query("SELECT category, COUNT(*) AS n FROM items GROUP BY category") as $r) {
        $out[$r['category']] = (int) $r['n'];
    }
    return $out;
}

// Vérifie un mot de passe en limitant les fuites par timing.
function check_password(string $given, string $expected): bool {
    return $expected !== '' && hash_equals($expected, $given);
}

// Jeton CSRF
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function check_csrf(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

// Liste des jetons de réservation appartenant au visiteur (cookie).
function my_tokens(): array {
    $raw = $_COOKIE['my_reservations'] ?? '';
    $arr = array_filter(array_map('trim', explode(',', $raw)));
    return array_values($arr);
}
function remember_token(string $token): void {
    $tokens = my_tokens();
    $tokens[] = $token;
    $tokens = array_slice(array_unique($tokens), -100);
    setcookie('my_reservations', implode(',', $tokens), [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Charge tous les articles avec quantité réservée et la liste des réservations.
function load_items(PDO $pdo): array {
    $items = $pdo->query("
        SELECT i.*, c.icon AS category_icon, c.sort_order AS category_order
        FROM items i
        LEFT JOIN categories c ON c.name = i.category
        ORDER BY COALESCE(c.sort_order, 9999), i.sort_order, i.id
    ")->fetchAll();
    $resStmt = $pdo->query("SELECT * FROM reservations ORDER BY created_at");
    $byItem = [];
    foreach ($resStmt->fetchAll() as $r) {
        $byItem[$r['item_id']][] = $r;
    }
    foreach ($items as &$it) {
        $res = $byItem[$it['id']] ?? [];
        $it['reservations'] = $res;
        $it['reserved'] = array_sum(array_map(fn($r) => (int) $r['quantity'], $res));
        if ($it['qty_needed'] === null) {
            $it['remaining'] = null;            // illimité
            $it['complete'] = false;
        } else {
            $it['remaining'] = max(0, (int) $it['qty_needed'] - $it['reserved']);
            $it['complete'] = $it['remaining'] === 0;
        }
    }
    unset($it);
    return $items;
}

// URL de la photo d'un article.
// Priorité : 1) photo explicite en base ; 2) fichier <slug>.{jpg,jpeg,png,gif,webp}
// déposé dans img/products/ ; 3) image par défaut.
function photo_url(array $item): string {
    global $ROOT;
    $dir = $ROOT . '/img/products/';
    if (!empty($item['photo']) && is_file($dir . $item['photo'])) {
        return 'img/products/' . rawurlencode($item['photo']) . '?v=' . filemtime($dir . $item['photo']);
    }
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
        $f = $item['slug'] . '.' . $ext;
        if (is_file($dir . $f)) {
            return 'img/products/' . rawurlencode($f) . '?v=' . filemtime($dir . $f);
        }
    }
    return 'assets/placeholder.svg';
}

function leboncoin_url(string $terms): string {
    return 'https://www.leboncoin.fr/recherche?text=' . rawurlencode($terms);
}
function vinted_url(string $terms): string {
    return 'https://www.vinted.fr/catalog?search_text=' . rawurlencode($terms);
}
