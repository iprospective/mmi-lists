<?php
// Barre de navigation de l'administration. Attend $adminPage (clé de l'onglet actif).
$tabs = [
    'items'        => ['admin.php',              '📷 Articles'],
    'categories'   => ['admin_categories.php',   '🗂️ Catégories'],
    'reservations' => ['admin_reservations.php', '🎁 Réservations'],
    'people'       => ['admin_people.php',        '🧑‍🤝‍🧑 Personnes'],
    'settings'     => ['admin_settings.php',      '⚙️ Paramètres'],
];
?>
<nav class="admin-nav">
    <div class="admin-nav-tabs">
        <?php foreach ($tabs as $key => [$url, $label]): ?>
            <a href="<?= e($url) ?>" class="<?= (($adminPage ?? '') === $key) ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
    <div class="admin-nav-right">
        <a href="index.php">↗ Voir le site</a>
        <a href="admin.php?logout=1">Déconnexion</a>
    </div>
</nav>
