<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'GestEvent', ENT_QUOTES, 'UTF-8') ?> — GestEvent</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/backoffice.css">
</head>
<body class="backoffice">
<nav>
    <a href="/admin" class="nav-brand">GestEvent</a>
    <ul>
        <li><a href="/admin/events">Événements</a></li>
        <?php if (($_SESSION['user_role'] ?? '') === 'super_admin'): ?>
        <li><a href="/admin/users">Utilisateurs</a></li>
        <?php endif; ?>
    </ul>
    <div class="nav-user">
        <?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        &nbsp;·&nbsp;<a href="/logout">Déconnexion</a>
    </div>
</nav>
<main>
    <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>
</body>
</html>
