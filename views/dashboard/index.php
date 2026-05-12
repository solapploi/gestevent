<?php
$pageTitle = 'Tableau de bord';
ob_start();
?>
<div class="page-header">
    <h1>Tableau de bord</h1>
    <a href="/admin/events" class="btn btn-primary">+ Nouvel événement</a>
</div>

<div class="card">
    <p>Bienvenue, <strong><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></strong>
    <span class="badge badge-indigo"><?= htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8') ?></span></p>
    <p class="text-muted text-sm">Utilisez le menu ci-dessus pour gérer vos événements et invités.</p>
    <a href="/admin/events" class="btn btn-secondary mt-1">Voir les événements →</a>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layout/backoffice.php';
