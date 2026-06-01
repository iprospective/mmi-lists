<?php
/** @var string $adminPage */
$tabs = [
    'items'        => ['admin',              '📷 Articles'],
    'categories'   => ['admin/categories',   '🗂️ Catégories'],
    'reservations' => ['admin/reservations', '🎁 Réservations'],
    'people'       => ['admin/people',        '🧑‍🤝‍🧑 Personnes'],
    'settings'     => ['admin/settings',      '⚙️ Paramètres'],
];
?>
<nav class="admin-nav">
    <div class="admin-nav-tabs">
        <?php foreach ($tabs as $key => [$path, $label]): ?>
            <a href="<?= e(url($path)) ?>" class="<?= (($adminPage ?? '') === $key) ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
    <div class="admin-nav-right">
        <a href="<?= e(url('')) ?>">↗ Voir le site</a>
        <a href="<?= e(url('admin/logout')) ?>">Déconnexion</a>
    </div>
</nav>
