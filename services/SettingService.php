<?php
declare(strict_types=1);

namespace Services;

use PDO;

// Paramètres modifiables, stockés en base et surchargeant config.php.
final class SettingService
{
    // Clés pilotées depuis l'interface d'administration.
    public const MANAGED = ['site_title', 'intro', 'parents', 'guest_password'];

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
            if (!$has->fetchColumn() && isset($config[$k])) {
                $this->set($k, (string) $config[$k]);
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
