<?php
declare(strict_types=1);

namespace Services;

use PDO;

// Accès et opérations sur les articles de la liste.
final class ItemService
{
    public function __construct(private PDO $pdo) {}

    // Tous les articles, triés par catégorie puis ordre, avec quantité réservée et réservations.
    public function all(): array
    {
        $items = $this->pdo->query("
            SELECT i.*, c.icon AS category_icon, c.sort_order AS category_order
            FROM items i
            LEFT JOIN categories c ON c.name = i.category
            ORDER BY COALESCE(c.sort_order, 9999), i.sort_order, i.id
        ")->fetchAll();

        $byItem = [];
        foreach ($this->pdo->query("SELECT * FROM reservations ORDER BY created_at")->fetchAll() as $r) {
            $byItem[$r['item_id']][] = $r;
        }

        foreach ($items as &$it) {
            $res = $byItem[$it['id']] ?? [];
            $it['reservations'] = $res;
            $it['reserved'] = array_sum(array_map(static fn ($r) => (int) $r['quantity'], $res));
            if ($it['qty_needed'] === null) {
                $it['remaining'] = null;
                $it['complete'] = false;
            } else {
                $it['remaining'] = max(0, (int) $it['qty_needed'] - $it['reserved']);
                $it['complete'] = $it['remaining'] === 0;
            }
        }
        unset($it);

        return $items;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $category): int
    {
        $slug = $this->uniqueSlug($name);
        $maxOrder = (int) $this->pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM items")->fetchColumn();
        $this->pdo->prepare("INSERT INTO items (slug, category, name, sort_order) VALUES (?,?,?,?)")
            ->execute([$slug, $category, $name, $maxOrder]);
        return (int) $this->pdo->lastInsertId();
    }

    // Déplace un article vers le haut/bas dans sa catégorie (échange l'ordre avec son voisin).
    public function move(int $id, string $dir): void
    {
        $item = $this->find($id);
        if (!$item) {
            return;
        }
        $stmt = $this->pdo->prepare("SELECT id, sort_order FROM items WHERE category = ? ORDER BY sort_order, id");
        $stmt->execute([$item['category']]);
        $list = $stmt->fetchAll();
        $ids = array_map(static fn ($r) => (int) $r['id'], $list);
        $pos = array_search($id, $ids, true);
        if ($pos === false) {
            return;
        }
        $swap = $dir === 'up' ? $pos - 1 : $pos + 1;
        if ($swap < 0 || $swap >= count($list)) {
            return;
        }
        $a = $list[$pos];
        $b = $list[$swap];
        $upd = $this->pdo->prepare("UPDATE items SET sort_order = ? WHERE id = ?");
        $upd->execute([(int) $b['sort_order'], (int) $a['id']]);
        $upd->execute([(int) $a['sort_order'], (int) $b['id']]);
    }

    public function update(int $id, string $name, string $category, string $description, string $search, ?int $qtyNeeded, int $priority = 0, int $neededEarly = 0): void
    {
        $this->pdo->prepare("UPDATE items SET name=?, category=?, description=?, search=?, qty_needed=?, priority=?, needed_early=? WHERE id=?")
            ->execute([$name, $category, $description, $search, $qtyNeeded, $priority, $neededEarly, $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM items WHERE id = ?")->execute([$id]);
    }

    // Enregistre la photo d'un article. Retourne ['ok' => bool, 'msg' => string].
    public function savePhoto(array $item, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => "Aucun fichier reçu."];
        }
        $info = @getimagesize($file['tmp_name']);
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($allowed[$info[2]]) || $file['size'] > 8 * 1024 * 1024) {
            return ['ok' => false, 'msg' => "Fichier invalide : JPG, PNG, GIF ou WEBP, 8 Mo max."];
        }

        $ext = $allowed[$info[2]];
        $dir = APP_ROOT . '/img/products/';
        $fname = $item['slug'] . '.' . $ext;
        $dest = $dir . $fname;

        foreach (['jpg', 'png', 'gif', 'webp'] as $oldExt) {
            $old = $dir . $item['slug'] . '.' . $oldExt;
            if ($old !== $dest && is_file($old)) {
                @unlink($old);
            }
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'msg' => "Échec de l'enregistrement du fichier (droits sur img/products/ ?)."];
        }

        $this->pdo->prepare("UPDATE items SET photo = ? WHERE id = ?")->execute([$fname, (int) $item['id']]);
        return ['ok' => true, 'msg' => "Photo mise à jour pour « " . $item['name'] . " »."];
    }

    private function uniqueSlug(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name
        ));
        $slug = trim((string) $slug, '-') ?: ('item-' . time());
        $base = $slug;
        $n = 2;
        while ($this->pdo->query("SELECT 1 FROM items WHERE slug = " . $this->pdo->quote($slug))->fetchColumn()) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
