<?php
declare(strict_types=1);

namespace Services;

use PDO;
use Throwable;

// Ouverture de la base SQLite, création du schéma et pré-remplissage au 1er lancement.
final class Database
{
    public static function connect(string $dbFile, string $catalogFile): PDO
    {
        $dir = dirname($dbFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

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

        self::migrate($pdo);
        self::seed($pdo, $catalogFile);

        return $pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS items (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                slug        TEXT UNIQUE NOT NULL,
                category    TEXT NOT NULL,
                name        TEXT NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                qty_needed  INTEGER,
                search      TEXT NOT NULL DEFAULT '',
                photo       TEXT,
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

        // Colonnes ajoutées après coup (bases existantes) : niveau de besoin et urgence.
        self::ensureColumn($pdo, 'items', 'priority', 'priority INTEGER NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'items', 'needed_early', 'needed_early INTEGER NOT NULL DEFAULT 0');
        // Validation des réservations par email (opt-in) : les réservations existantes
        // sont considérées comme déjà confirmées (valeur par défaut 1).
        self::ensureColumn($pdo, 'reservations', 'confirmed', 'confirmed INTEGER NOT NULL DEFAULT 1');
    }

    // Ajoute une colonne si elle n'existe pas encore (migration idempotente).
    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        foreach ($pdo->query("PRAGMA table_info(" . $table . ")") as $col) {
            if ($col['name'] === $column) {
                return;
            }
        }
        $pdo->exec("ALTER TABLE " . $table . " ADD COLUMN " . $definition);
    }

    private static function seed(PDO $pdo, string $catalogFile): void
    {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
        if ($count === 0 && is_file($catalogFile)) {
            $catalog = require $catalogFile;
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

        $catCount = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($catCount === 0) {
            $defaultIcons = [
                'Soin & Hygiène'             => '🧴',
                'Allaitement & Alimentation' => '🍼',
                'Vêtements'                  => '👕',
                'Meubles & Mobilier'         => '🛏️',
                'Sécurité'                   => '🛡️',
                'Jouets'                     => '🧸',
                'Voyage & Transport'         => '🚲',
                'Autres (coups de cœur)'     => '💛',
            ];
            $cats = $pdo->query("SELECT category, MIN(sort_order) AS o FROM items GROUP BY category ORDER BY o")->fetchAll();
            $insC = $pdo->prepare("INSERT OR IGNORE INTO categories (name, icon, sort_order) VALUES (?, ?, ?)");
            foreach ($cats as $i => $c) {
                $insC->execute([$c['category'], $defaultIcons[$c['category']] ?? '🎁', $i]);
            }
        }
    }
}
