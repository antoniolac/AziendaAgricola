<?php
$page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azienda Agricola — <?= htmlspecialchars($pageTitle ?? 'Gestione') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-brand">
        <span class="brand-icon">🌿</span>
        <div>
            <h1 class="brand-name">Azienda Agricola</h1>
            <span class="brand-sub">Gestione Produzioni &amp; Vendite</span>
        </div>
    </div>
    <nav class="main-nav">
        <a href="/index.php"      class="nav-link <?= $page==='index'      ?'active':'' ?>">Dashboard</a>
        <a href="/prodotti.php"   class="nav-link <?= $page==='prodotti'   ?'active':'' ?>">Prodotti</a>
        <a href="/giacenze.php"   class="nav-link <?= $page==='giacenze'   ?'active':'' ?>">Giacenze</a>
        <a href="/eventi.php"     class="nav-link <?= $page==='eventi'     ?'active':'' ?>">Lavorazioni</a>
        <a href="/clienti.php"    class="nav-link <?= $page==='clienti'    ?'active':'' ?>">Clienti</a>
        <a href="/vendite.php"    class="nav-link <?= $page==='vendite'    ?'active':'' ?>">Vendite</a>
        <a href="/categorie.php"  class="nav-link <?= $page==='categorie'  ?'active':'' ?>">Categorie</a>
        <a href="/luoghi.php"     class="nav-link <?= $page==='luoghi'     ?'active':'' ?>">Luoghi</a>
        <a href="/prezzi.php"     class="nav-link <?= $page==='prezzi'     ?'active':'' ?>">Prezzi</a>
        <a href="/report.php"     class="nav-link <?= $page==='report'     ?'active':'' ?>">Report</a>
    </nav>
</header>
<main class="main-content">
<?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert--<?= $_SESSION['flash_type'] ?? 'info' ?>">
        <?= htmlspecialchars($_SESSION['flash_msg']) ?>
    </div>
    <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<?php endif; ?>