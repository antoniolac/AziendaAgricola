<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Dashboard';

$tot_prodotti = db_query($conn, "SELECT COUNT(*) as n FROM PRODOTTO WHERE stato != 'archiviato'")[0]['n'] ?? 0;
$tot_clienti  = db_query($conn, "SELECT COUNT(*) as n FROM CLIENTE")[0]['n'] ?? 0;
$tot_vendite  = db_query($conn, "SELECT COUNT(*) as n FROM VENDITA")[0]['n'] ?? 0;
$ricavi_mese  = db_query($conn, "SELECT COALESCE(SUM(totale_pagato),0) as s FROM VENDITA WHERE MONTH(data_vendita)=MONTH(CURDATE()) AND YEAR(data_vendita)=YEAR(CURDATE())")[0]['s'] ?? 0;
$esauriti     = db_query($conn, "SELECT COUNT(*) as n FROM PRODOTTO WHERE stato='esaurito'")[0]['n'] ?? 0;

$ultime_vendite = db_query($conn,
    "SELECT v.id_vendita, v.data_vendita, v.totale_pagato, c.nome AS cliente
     FROM VENDITA v JOIN CLIENTE c ON v.id_cliente=c.id_cliente
     ORDER BY v.data_vendita DESC LIMIT 6");

$avvisi = db_query($conn,
    "SELECT p.nome, p.tipo_prodotto, g.quantita_disponibile
     FROM PRODOTTO p
     LEFT JOIN GIACENZA g ON p.id_prodotto=g.id_prodotto
     WHERE p.stato != 'archiviato' AND p.tipo_prodotto != 'FRESCO'
       AND (g.quantita_disponibile IS NULL OR g.quantita_disponibile < 5)
     ORDER BY g.quantita_disponibile ASC LIMIT 8");

$top_prodotti = db_query($conn,
    "SELECT p.nome, SUM(dv.quantita) as tot_q, SUM(dv.totale_riga) as tot_e
     FROM DETTAGLIO_VENDITA dv JOIN PRODOTTO p ON dv.id_prodotto=p.id_prodotto
     GROUP BY dv.id_prodotto ORDER BY tot_e DESC LIMIT 6");

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Benvenuto 🌿</h1>
        <p>Panoramica dell'azienda — <?= date('d/m/Y') ?></p>
    </div>
</div>

<div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));margin-bottom:2rem">
    <div class="stat-card">
        <div class="stat-icon stat-icon--terra">🧺</div>
        <div><div class="stat-value"><?= $tot_prodotti ?></div><div class="stat-label">Prodotti Attivi</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--salvia">👥</div>
        <div><div class="stat-value"><?= $tot_clienti ?></div><div class="stat-label">Clienti</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--ocra">🛒</div>
        <div><div class="stat-value"><?= $tot_vendite ?></div><div class="stat-label">Vendite Totali</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--salvia">💶</div>
        <div><div class="stat-value">€ <?= number_format($ricavi_mese,2,',','.') ?></div><div class="stat-label">Ricavi Questo Mese</div></div>
    </div>
    <?php if ($esauriti > 0): ?>
    <div class="stat-card">
        <div class="stat-icon stat-icon--rosso">⚠️</div>
        <div><div class="stat-value"><?= $esauriti ?></div><div class="stat-label">Prodotti Esauriti</div></div>
    </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem">
    <div class="card">
        <div class="section-header">
            <h2>Ultime Vendite</h2>
            <a href="/vendite.php" class="btn btn--ghost btn--sm">Vedi tutte →</a>
        </div>
        <?php if (empty($ultime_vendite)): ?>
            <div class="empty-state"><div class="empty-state__icon">🛒</div><p>Nessuna vendita ancora.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Data</th><th>Cliente</th><th>Pagato</th></tr></thead>
                <tbody>
                <?php foreach ($ultime_vendite as $v): ?>
                <tr>
                    <td>#<?= $v['id_vendita'] ?></td>
                    <td><?= date('d/m/Y', strtotime($v['data_vendita'])) ?></td>
                    <td><?= htmlspecialchars($v['cliente']) ?></td>
                    <td class="price-tag">€ <?= number_format($v['totale_pagato'],2,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="section-header">
            <h2>⚠️ Scorte Basse</h2>
            <a href="/giacenze.php" class="btn btn--ghost btn--sm">Gestisci →</a>
        </div>
        <?php if (empty($avvisi)): ?>
            <div class="alert alert--success">Tutte le giacenze sono nella norma!</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Prodotto</th><th>Tipo</th><th>Giacenza</th></tr></thead>
                <tbody>
                <?php foreach ($avvisi as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nome']) ?></td>
                    <td><span class="badge badge--<?= strtolower($a['tipo_prodotto']) ?>"><?= $a['tipo_prodotto'] ?></span></td>
                    <td><span data-qty="<?= $a['quantita_disponibile'] ?? 0 ?>"><?= $a['quantita_disponibile'] ?? '0' ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="section-header"><h2>🏆 Top Prodotti Venduti</h2></div>
    <?php if (empty($top_prodotti)): ?>
        <div class="empty-state"><p>Nessun dato disponibile.</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Prodotto</th><th>Quantità</th><th>Fatturato</th></tr></thead>
            <tbody>
            <?php foreach ($top_prodotti as $tp): ?>
            <tr>
                <td><?= htmlspecialchars($tp['nome']) ?></td>
                <td><?= number_format($tp['tot_q'],2,',','.') ?></td>
                <td class="price-tag">€ <?= number_format($tp['tot_e'],2,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>