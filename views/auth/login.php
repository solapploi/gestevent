<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — GestEvent</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f4f6f9; margin:0; font-family:system-ui,sans-serif; }
        .login-box { background:#fff; padding:2.5rem; border-radius:8px; box-shadow:0 2px 12px rgba(0,0,0,.1); width:100%; max-width:380px; }
        h1 { margin:0 0 1.5rem; font-size:1.5rem; text-align:center; color:#1a1a2e; }
        label { display:block; margin-bottom:.25rem; font-size:.875rem; font-weight:500; color:#374151; }
        input[type=email], input[type=password] { width:100%; padding:.625rem .75rem; border:1px solid #d1d5db; border-radius:6px; font-size:1rem; box-sizing:border-box; margin-bottom:1rem; }
        input:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.15); }
        button { width:100%; padding:.75rem; background:#4f46e5; color:#fff; border:none; border-radius:6px; font-size:1rem; font-weight:600; cursor:pointer; }
        button:hover { background:#4338ca; }
        .alert-error { background:#fee2e2; color:#b91c1c; padding:.75rem 1rem; border-radius:6px; margin-bottom:1rem; font-size:.875rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>GestEvent</h1>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autofocus autocomplete="email">

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
