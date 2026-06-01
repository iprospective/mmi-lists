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

    public function reservedQty(int $itemId): int
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM reservations WHERE item_id = ?");
        $stmt->execute([$itemId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(int $itemId, string $name, string $email, int $qty): string
    {
        $token = bin2hex(random_bytes(16));
        $this->pdo->prepare("
            INSERT INTO reservations (item_id, guest_name, guest_email, quantity, token)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$itemId, $name, $email, $qty, $token]);
        return $token;
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
