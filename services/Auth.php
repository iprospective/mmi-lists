<?php
declare(strict_types=1);

namespace Services;

// Authentification visiteur / administrateur (sessions) et jetons de réservation (cookie).
final class Auth
{
    public static function isGuest(): bool { return !empty($_SESSION['guest']); }
    public static function isAdmin(): bool { return !empty($_SESSION['admin']); }

    public static function requireGuest(): void
    {
        if (!self::isGuest()) {
            redirect('');
        }
    }

    // Comparaison de mot de passe résistante aux attaques temporelles.
    public static function checkPassword(string $given, string $expected): bool
    {
        return $expected !== '' && hash_equals($expected, $given);
    }

    public static function loginGuest(): void
    {
        session_regenerate_id(true);
        $_SESSION['guest'] = true;
    }

    public static function loginAdmin(): void
    {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
    }

    // Jetons des réservations appartenant au visiteur (stockés en cookie).
    public static function myTokens(): array
    {
        $raw = $_COOKIE['my_reservations'] ?? '';
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    public static function rememberToken(string $token): void
    {
        $tokens = self::myTokens();
        $tokens[] = $token;
        $tokens = array_slice(array_unique($tokens), -100);
        setcookie('my_reservations', implode(',', $tokens), [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
