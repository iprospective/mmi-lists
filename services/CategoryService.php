<?php
declare(strict_types=1);

namespace Services;

use PDO;

// Gestion des catégories (la colonne items.category référence categories.name).
final class CategoryService
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
    }

    // Nombre d'articles par catégorie, indexé par nom.
    public function counts(): array
    {
        $out = [];
        foreach ($this->pdo->query("SELECT category, COUNT(*) AS n FROM items GROUP BY category") as $r) {
            $out[$r['category']] = (int) $r['n'];
        }
        return $out;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function nameExists(string $name, int $exceptId = 0): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM categories WHERE name = ? AND id <> ?");
        $stmt->execute([$name, $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $name, string $icon): void
    {
        $maxOrder = (int) $this->pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM categories")->fetchColumn();
        $this->pdo->prepare("INSERT INTO categories (name, icon, sort_order) VALUES (?, ?, ?)")
            ->execute([$name, $icon, $maxOrder]);
    }

    // Met à jour la catégorie et répercute un éventuel renommage sur les articles.
    public function update(int $id, string $name, string $icon, string $oldName): void
    {
        $this->pdo->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?")
            ->execute([$name, $icon, $id]);
        if ($oldName !== '' && $oldName !== $name) {
            $this->pdo->prepare("UPDATE items SET category = ? WHERE category = ?")
                ->execute([$name, $oldName]);
        }
    }

    public function move(int $id, string $dir): void
    {
        $cats = $this->all();
        $ids = array_column($cats, 'id');
        $pos = array_search($id, array_map('intval', $ids), true);
        if ($pos === false) {
            return;
        }
        $swap = $dir === 'up' ? $pos - 1 : $pos + 1;
        if ($swap < 0 || $swap >= count($cats)) {
            return;
        }
        $a = $cats[$pos];
        $b = $cats[$swap];
        $upd = $this->pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
        $upd->execute([(int) $b['sort_order'], (int) $a['id']]);
        $upd->execute([(int) $a['sort_order'], (int) $b['id']]);
    }

    public function usageCount(string $name): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM items WHERE category = ?");
        $stmt->execute([$name]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    }
}
