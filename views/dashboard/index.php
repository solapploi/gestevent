<?php
$pageTitle = 'Tableau de bord';
ob_start();
?>
<section>
    <h2>Tableau de bord</h2>
    <p>Bienvenue, <strong><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></strong>
       <em>(<?= htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8') ?>)</em></p>
    <p><a href="/admin/events">Voir les événements →</a></p>
</section>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layout/backoffice.php';
