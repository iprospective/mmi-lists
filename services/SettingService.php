<?php
declare(strict_types=1);

namespace Services;

use PDO;

// Paramètres modifiables, stockés en base et surchargeant config.php.
final class SettingService
{
    // Clés pilotées depuis l'interface d'administration.
    public const MANAGED = [
        'site_title', 'intro', 'parents', 'guest_password',
        'theme_bg', 'theme_heart', 'theme_button',
        'header_photo', 'header_position', 'header_format',
        'email_from', 'email_to',
    ];

    // Couleurs de la charte (réinitialisables individuellement).
    public const THEME_COLORS = ['theme_bg', 'theme_heart', 'theme_button'];

    // Valeurs par défaut pour les clés absentes de config.php (charte + mise en page).
    public const DEFAULTS = [
        'theme_bg'        => '#fbf7f2',
        'theme_heart'     => '#6fae8e',
        'theme_button'    => '#e9a17c',
        'header_position' => 'banner', // banner | left | right
        'header_format'   => 'cover',  // cover (rogné) | contain (image entière)
    ];

    public function __construct(private PDO $pdo) {}

    public function set(string $key, string $value): void
    {
        $this->pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)
                             ON CONFLICT(key) DO UPDATE SET value = excluded.value")
            ->execute([$key, $value]);
    }

    public function all(): array
    {
        $out = [];
        foreach ($this->pdo->query("SELECT key, value FROM settings") as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }

    // Initialise les paramètres manquants depuis config.php (au 1er lancement).
    public function seedDefaults(array $config): void
    {
        $has = $this->pdo->prepare("SELECT 1 FROM settings WHERE key = ?");
        foreach (self::MANAGED as $k) {
            $has->execute([$k]);
            if ($has->fetchColumn()) {
                continue;
            }
            $value = $config[$k] ?? self::DEFAULTS[$k] ?? null;
            if ($value !== null) {
                $this->set($k, (string) $value);
            }
        }
    }

    // Renvoie la config avec les valeurs de la base appliquées par-dessus.
    public function overlay(array $config): array
    {
        foreach ($this->all() as $k => $v) {
            $config[$k] = $v;
        }
        return $config;
    }
}
