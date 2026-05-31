<?php /** @var string $pageTitle */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? cfg('site_title')) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👶</text></svg>">
</head>
<body>
<header class="topbar">
    <div class="wrap">
        <a class="brand" href="index.php">👶 <?= e(cfg('site_title')) ?></a>
        <nav>
            <?php if (is_guest()): ?>
                <a href="logout.php">Se déconnecter</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="wrap">
