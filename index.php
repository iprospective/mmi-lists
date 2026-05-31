<?php
require __DIR__ . '/lib/bootstrap.php';

$error = null;

// --- Traitement de la connexion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (!check_csrf()) {
        $error = "Session expirée, merci de réessayer.";
    } elseif (check_password((string) ($_POST['password'] ?? ''), (string) cfg('guest_password'))) {
        session_regenerate_id(true);
        $_SESSION['guest'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Mot de passe incorrect.";
    }
}

// --- Page de connexion ---
if (!is_guest()) {
    $pageTitle = cfg('site_title');
    require __DIR__ . '/lib/header.php';
    ?>
    <section class="login-box">
        <h1>👶 <?= e(cfg('site_title')) ?></h1>
        <p class="muted">Cette liste est privée. Entrez le mot de passe qui vous a été communiqué.</p>
        <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>Mot de passe
                <input type="password" name="password" autofocus required>
            </label>
            <button type="submit">Entrer</button>
        </form>
    </section>
    <?php
    require __DIR__ . '/lib/footer.php';
    exit;
}

// --- Liste (visiteur connecté) ---
$items = load_items($pdo);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$myTokens = my_tokens();

// Regroupe par catégorie en conservant l'ordre
$byCat = [];
foreach ($items as $it) {
    $byCat[$it['category']][] = $it;
}

$catIcons = [
    'Soin & Hygiène' => '🧴',
    'Allaitement & Alimentation' => '🍼',
    'Vêtements' => '👕',
    'Meubles & Mobilier' => '🛏️',
    'Sécurité' => '🛡️',
    'Jouets' => '🧸',
    'Voyage & Transport' => '🚲',
    'Autres (coups de cœur)' => '💛',
];

$pageTitle = cfg('site_title');
require __DIR__ . '/lib/header.php';
?>
<section class="intro">
    <h1><?= e(cfg('site_title')) ?></h1>
    <p><?= e(cfg('intro')) ?></p>
</section>

<?php if ($flash): ?>
    <p class="alert <?= e($flash['type'] ?? 'ok') ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php foreach ($byCat as $cat => $catItems): ?>
    <h2 class="cat-title"><?= e($catIcons[$cat] ?? '🎁') ?> <?= e($cat) ?></h2>
    <div class="grid">
        <?php foreach ($catItems as $it):
            $unlimited = $it['qty_needed'] === null;
            $complete  = $it['complete'];
            $remaining = $it['remaining'];
        ?>
            <article class="card <?= $complete ? 'is-complete' : '' ?>">
                <div class="card-photo">
                    <img src="<?= e(photo_url($it)) ?>" alt="<?= e($it['name']) ?>" loading="lazy">
                    <?php if ($complete): ?><span class="ribbon">Déjà offert 🎉</span><?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?= e($it['name']) ?></h3>
                    <p class="desc"><?= e($it['description']) ?></p>

                    <?php if ($unlimited): ?>
                        <p class="qty muted">Plusieurs personnes peuvent participer 🤗
                            <?php if ($it['reserved'] > 0): ?>(<?= (int) $it['reserved'] ?> déjà proposé<?= $it['reserved'] > 1 ? 's' : '' ?>)<?php endif; ?>
                        </p>
                    <?php else: ?>
                        <?php $pct = (int) $it['qty_needed'] > 0 ? min(100, round(100 * $it['reserved'] / (int) $it['qty_needed'])) : 0; ?>
                        <p class="qty"><strong><?= (int) $it['reserved'] ?></strong> / <?= (int) $it['qty_needed'] ?> réservé<?= $it['reserved'] > 1 ? 's' : '' ?></p>
                        <div class="bar"><span style="width: <?= $pct ?>%"></span></div>
                    <?php endif; ?>

                    <?php if (!empty($it['reservations'])): ?>
                        <ul class="reservers">
                            <?php foreach ($it['reservations'] as $r): ?>
                                <li>
                                    <?= e($r['guest_name']) ?>
                                    <?php if ((int) $r['quantity'] > 1): ?><span class="qbadge">×<?= (int) $r['quantity'] ?></span><?php endif; ?>
                                    <?php if (in_array($r['token'], $myTokens, true)): ?>
                                        <form method="post" action="cancel.php" class="inline-cancel" onsubmit="return confirm('Annuler votre réservation ?');">
                                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="token" value="<?= e($r['token']) ?>">
                                            <button type="submit" class="link-btn" title="Annuler ma réservation">annuler</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!$complete): ?>
                        <details class="reserve">
                            <summary>🎁 Je veux offrir ça</summary>
                            <form method="post" action="reserve.php" class="stack">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                                <label>Votre prénom (ou nom)
                                    <input type="text" name="name" required maxlength="60">
                                </label>
                                <label>Votre email <span class="muted">(privé, non affiché)</span>
                                    <input type="email" name="email" maxlength="120">
                                </label>
                                <?php if (!$unlimited && (int) $it['qty_needed'] > 1): ?>
                                    <label>Combien souhaitez-vous en offrir ?
                                        <input type="number" name="quantity" value="1" min="1" max="<?= (int) $remaining ?>">
                                        <span class="muted">(il en reste <?= (int) $remaining ?>)</span>
                                    </label>
                                <?php else: ?>
                                    <input type="hidden" name="quantity" value="1">
                                <?php endif; ?>
                                <button type="submit">Réserver ce cadeau</button>
                            </form>
                        </details>
                    <?php endif; ?>

                    <?php if (!empty($it['search'])): ?>
                        <p class="search-links">
                            Le trouver d'occasion :
                            <a href="<?= e(leboncoin_url($it['search'])) ?>" target="_blank" rel="noopener">Leboncoin</a>
                            ·
                            <a href="<?= e(vinted_url($it['search'])) ?>" target="_blank" rel="noopener">Vinted</a>
                        </p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/lib/footer.php'; ?>
