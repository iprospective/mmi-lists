<?php
declare(strict_types=1);

namespace Services;

use PDO;

// Gestion des réservations (création côté visiteur, administration, regroupement par personne).
final class ReservationService
{
    public function __construct(private PDO $pdo) {}

    // Toutes les réservations avec le nom de l'article associé.
    public function allWithItem(): array
    {
        return $this->pdo->query("
            SELECT r.*, i.name AS item_name, i.category AS item_category
            FROM reservations r
            LEFT JOIN items i ON i.id = r.item_id
            ORDER BY r.created_at DESC
        ")->fetchAll();
    }

    // Une réservation par son identifiant, avec le nom de l'article.
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, i.name AS item_name
            FROM reservations r LEFT JOIN items i ON i.id = r.item_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Une réservation par son jeton (authentifie le lien privé reçu par email).
    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, i.name AS item_name
            FROM reservations r LEFT JOIN items i ON i.id = r.item_id
            WHERE r.token = ?
        ");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    // Toutes les réservations d'une même personne (clé email/nom), récentes d'abord.
    public function forPersonKey(string $key): array
    {
        $rows = $this->pdo->query("
            SELECT r.*, i.name AS item_name
            FROM reservations r LEFT JOIN items i ON i.id = r.item_id
            ORDER BY r.created_at DESC
        ")->fetchAll();
        return array_values(array_filter(
            $rows,
            static fn (array $r): bool => self::personKey($r['guest_name'], $r['guest_email']) === $key
        ));
    }

    // Quantité réservée : seules les réservations confirmées comptent (les réservations
    // en attente de validation par email ne « bloquent » pas le cadeau).
    public function reservedQty(int $itemId): int
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM reservations WHERE item_id = ? AND confirmed = 1");
        $stmt->execute([$itemId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(int $itemId, string $name, string $email, int $qty, bool $confirmed = true): string
    {
        $token = bin2hex(random_bytes(16));
        $this->pdo->prepare("
            INSERT INTO reservations (item_id, guest_name, guest_email, quantity, token, confirmed)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$itemId, $name, $email, $qty, $token, $confirmed ? 1 : 0]);
        return $token;
    }

    // Valide une réservation en attente. Renvoie true si elle vient d'être confirmée
    // (false si le jeton est inconnu ou la réservation déjà confirmée).
    public function confirm(string $token): bool
    {
        $stmt = $this->pdo->prepare("UPDATE reservations SET confirmed = 1 WHERE token = ? AND confirmed = 0");
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, string $name, string $email, int $qty): void
    {
        $this->pdo->prepare("UPDATE reservations SET guest_name = ?, guest_email = ?, quantity = ? WHERE id = ?")
            ->execute([$name, $email, $qty, $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
    }

    public function deleteByToken(string $token): void
    {
        $this->pdo->prepare("DELETE FROM reservations WHERE token = ?")->execute([$token]);
    }

    // Clé d'identité d'une personne : email (minuscules) si présent, sinon nom.
    public static function personKey(string $name, string $email): string
    {
        $email = strtolower(trim($email));
        return $email !== '' ? 'mail:' . $email : 'name:' . strtolower(trim($name));
    }

    // Réservations regroupées par personne, triées par nom.
    public function groupByPerson(): array
    {
        $rows = $this->pdo->query("
            SELECT r.*, i.name AS item_name
            FROM reservations r
            LEFT JOIN items i ON i.id = r.item_id
            ORDER BY r.created_at
        ")->fetchAll();

        $people = [];
        foreach ($rows as $r) {
            $key = self::personKey($r['guest_name'], $r['guest_email']);
            if (!isset($people[$key])) {
                $people[$key] = ['name' => $r['guest_name'], 'email' => $r['guest_email'], 'items' => [], 'qty' => 0];
            }
            $people[$key]['name'] = $r['guest_name'];
            if (trim((string) $r['guest_email']) !== '') {
                $people[$key]['email'] = $r['guest_email'];
            }
            $people[$key]['items'][] = ['name' => $r['item_name'] ?? '— supprimé —', 'qty' => (int) $r['quantity']];
            $people[$key]['qty'] += (int) $r['quantity'];
        }
        uasort($people, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $people;
    }

    // Renomme toutes les réservations d'une personne. Retourne le nombre modifié.
    public function renamePerson(string $key, string $newName): int
    {
        return $this->applyToPerson($key, function (int $id) use ($newName): void {
            $this->pdo->prepare("UPDATE reservations SET guest_name = ? WHERE id = ?")->execute([$newName, $id]);
        });
    }

    // Supprime toutes les réservations d'une personne. Retourne le nombre supprimé.
    public function deletePerson(string $key): int
    {
        return $this->applyToPerson($key, function (int $id): void {
            $this->pdo->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
        });
    }

    private function applyToPerson(string $key, callable $fn): int
    {
        $rows = $this->pdo->query("SELECT id, guest_name, guest_email FROM reservations")->fetchAll();
        $n = 0;
        foreach ($rows as $r) {
            if (self::personKey($r['guest_name'], $r['guest_email']) === $key) {
                $fn((int) $r['id']);
                $n++;
            }
        }
        return $n;
    }
}
