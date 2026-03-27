<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Report';

$da  = $_GET['da']        ?? date('Y').'-01-01';
$al  = $_GET['al']        ?? date('Y').'-12-31';
$cat = (int)($_GET['categoria'] ?? 0);

$vendite_mese = db_query($conn,
    "SELECT MONTH(data_vendita) AS mese, COUNT(*) AS num, SUM(totale_pagato) AS totale
     FROM VENDITA WHERE data_vendita BETWEEN ? AND ?
     GROUP BY MONTH(data_vendita) ORDER BY mese", 'ss', [$da,$al]);

$mesi_it = ['','Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];

$where_cat = $cat ? "AND p.id_categoria=$cat" : '';
$top = db_query($conn,
    "SELECT p.nome, p.unita_misura, SUM(dv.quantita) AS tot_q, SUM(dv.totale_riga) AS tot_e
     FROM DETTAGLIO_VENDITA dv
     JOIN PRODOTTO p ON dv.id_prodotto=p.id_prodotto
     JOIN VENDITA v ON dv.id_vendita=v.id_vendita
     WHERE v.data_vendita BETWEEN ? AND ? $where_cat
     GROUP BY dv.id_prodotto ORDER BY tot_e DESC LIMIT 10", 'ss', [$da,$al]);

$top_clienti = db_query($conn,
    "SELECT c.nome, c.tipo_cliente, COUNT(v.id_vendita) AS num_v, SUM(v.totale_pagato) AS totale
     FROM VENDITA v JOIN CLIENTE c ON v.id_cliente=c.id_cliente
     WHERE v.data_vendita BETWEEN ? AND ?
     GROUP BY v.id_cliente ORDER BY totale DESC LIMIT 8", 'ss', [$da,$al]);

$riepilogo = db_query($conn,
    "SELECT COUNT(*) AS num_vendite, SUM(totale_calcolato) AS tot_calc,
            SUM(totale_pagato) AS tot_pag, SUM(totale_calcolato-totale_pagato) AS tot_sconti
     FROM VENDITA WHERE data_vendita BETWEEN ? AND ?", 'ss', [$da,$al])[0] ?? [];

$eventi_periodo = db_query($conn,
    "SELECT tipo_evento, COUNT(*) AS n, SUM(quantita_input) AS tot_input, SUM(quantita_output) AS tot_output
     FROM EVENTO WHERE data_evento BETWEEN ? AND ?
     GROUP BY tipo_evento ORDER BY n DESC", 'ss', [$da,$al]);

$categorie = db_query($conn, "SELECT * FROM CATEGORIA ORDER BY nome");

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Report &amp; Statistiche<small>Analisi produzione, vendite e giacenze</small></h1></div>
</div>

<form method="get" class="filter-bar">
    <div class="form-group"><label>Dal</label><input type="date" name="da" value="<?= htmlspecialchars($da) ?>"></div>
    <div class="form-group"><label>Al</label><input type="date" name="al" value="<?= htmlspecialchars($al) ?>"></div>
    <div class="form-group"><label>Categoria</label>
        <select name="categoria"><option value="">Tutte</option>
        <?php foreach ($categorie as $c): ?>
        <option value="<?= $c['id_categoria'] ?>" <?= $cat==$c['id_categoria']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?></select>
    </div>
    <button type="submit" class="btn btn--salvia">Aggiorna</button>
</form>

<div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-bottom:2rem">
    <div class="stat-card"><div class="stat-icon stat-icon--ocra">🛒</div><div><div class="stat-value"><?= $riepilogo['num_vendite']??0 ?></div><div class="stat-label">Vendite</div></div></div>
    <div class="stat-card"><div class="stat-icon stat-icon--salvia">💶</div><div><div class="stat-value">€ <?= number_format($riepilogo['tot_pag']??0,0,',','.') ?></div><div class="stat-label">Incassato</div></div></div>
    <div class="stat-card"><div class="stat-icon stat-icon--terra">🎁</div><div><div class="stat-value">€ <?= number_format($riepilogo['tot_sconti']??0,0,',','.') ?></div><div class="stat-label">Sconti/Omaggi</div></div></div>
    <div class="stat-card"><div class="stat-icon stat-icon--rosso">⚙️</div><div><div class="stat-value"><?= array_sum(array_column($eventi_periodo,'n')) ?></div><div class="stat-label">Lavorazioni</div></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem">
    <div class="card">
        <h2>📅 Vendite per Mese</h2>
        <div class="table-wrap" style="margin-top:1rem">
            <table>
                <thead><tr><th>Mese</th><th># Vendite</th><th>Incassato</th></tr></thead>
                <tbody>
                <?php if (empty($vendite_mese)): ?>
                    <tr><td colspan="3" style="text-align:center;padding:1rem;color:var(--grigio)">Nessun dato.</td></tr>
                <?php else: ?>
                <?php foreach ($vendite_mese as $vm): ?>
                <tr>
                    <td><?= $mesi_it[(int)$vm['mese']] ?></td>
                    <td><?= $vm['num'] ?></td>
                    <td class="price-tag">€ <?= number_format($vm['totale'],2,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h2>🏆 Top Clienti</h2>
        <div class="table-wrap" style="margin-top:1rem">
            <table>
                <thead><tr><th>Cliente</th><th>Tipo</th><th>Acquisti</th><th>Totale</th></tr></thead>
                <tbody>
                <?php if (empty($top_clienti)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:1rem;color:var(--grigio)">Nessun dato.</td></tr>
                <?php else: ?>
                <?php foreach ($top_clienti as $tc): ?>
                <tr>
                    <td><?= htmlspecialchars($tc['nome']) ?></td>
                    <td><span class="badge badge--<?= $tc['tipo_cliente'] ?>"><?= $tc['tipo_cliente'] ?></span></td>
                    <td><?= $tc['num_v'] ?></td>
                    <td class="price-tag">€ <?= number_format($tc['totale'],2,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:2rem">
    <h2>🌿 Top Prodotti nel Periodo</h2>
    <div class="table-wrap" style="margin-top:1rem">
        <table>
            <thead><tr><th>Prodotto</th><th>Quantità</th><th>Fatturato</th></tr></thead>
            <tbody>
            <?php if (empty($top)): ?>
                <tr><td colspan="3" style="text-align:center;padding:1rem;color:var(--grigio)">Nessun dato.</td></tr>
            <?php else: ?>
            <?php foreach ($top as $tp): ?>
            <tr>
                <td><?= htmlspecialchars($tp['nome']) ?></td>
                <td><?= number_format($tp['tot_q'],2,',','.') ?> <?= $tp['unita_misura'] ?></td>
                <td class="price-tag">€ <?= number_format($tp['tot_e'],2,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>⚙️ Lavorazioni nel Periodo</h2>
    <div class="table-wrap" style="margin-top:1rem">
        <table>
            <thead><tr><th>Tipo</th><th># Eventi</th><th>Tot. Input</th><th>Tot. Output</th><th>Resa %</th></tr></thead>
            <tbody>
            <?php if (empty($eventi_periodo)): ?>
                <tr><td colspan="5" style="text-align:center;padding:1rem;color:var(--grigio)">Nessuna lavorazione nel periodo.</td></tr>
            <?php else: ?>
            <?php foreach ($eventi_periodo as $ep):
                $resa = ($ep['tot_input']>0) ? round(($ep['tot_output']/$ep['tot_input'])*100,1) : '—';
            ?>
            <tr>
                <td><span class="badge badge--lavorato"><?= str_replace('_',' ',$ep['tipo_evento']) ?></span></td>
                <td><?= $ep['n'] ?></td>
                <td><?= $ep['tot_input']  ? number_format($ep['tot_input'],2,',','.') : '—' ?></td>
                <td><?= $ep['tot_output'] ? number_format($ep['tot_output'],2,',','.') : '—' ?></td>
                <td><?= $resa !== '—' ? "$resa%" : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>