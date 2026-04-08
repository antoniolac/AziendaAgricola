<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Vendite';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_vendita') {
        $id_cliente    = (int)($_POST['id_cliente'] ?? 0);
        $data_vendita  = $_POST['data_vendita'] ?? date('Y-m-d');
        $note          = trim($_POST['note'] ?? '');
        $totale_pagato = (float)($_POST['totale_pagato'] ?? 0);
        $pids  = $_POST['prodotto_id']  ?? [];
        $qtys  = $_POST['quantita']     ?? [];
        $prs   = $_POST['prezzo_unit']  ?? [];
        $tipos = $_POST['tipo_vendita'] ?? [];

        if ($id_cliente && !empty($pids)) {
            $totale_calc = 0;
            foreach ($pids as $k => $pid)
                $totale_calc += ((float)($qtys[$k]??0)) * ((float)($prs[$k]??0));

            $id_vendita = db_execute($conn,
                "INSERT INTO VENDITA (data_vendita,totale_calcolato,totale_pagato,note,id_cliente) VALUES (?,?,?,?,?)",
                'sddsi', [$data_vendita, $totale_calc, $totale_pagato ?: $totale_calc, $note, $id_cliente]);

            foreach ($pids as $k => $pid) {
                $pid  = (int)$pid;
                $qty  = (float)($qtys[$k]  ?? 0);
                $pr   = (float)($prs[$k]   ?? 0);
                $tipo = $tipos[$k] ?? 'confezionato';
                if (!$pid || !$qty) continue;

                $ps = db_query($conn,
                    "SELECT id_prezzo FROM PREZZO_STORICO WHERE id_prodotto=? AND data_fine IS NULL ORDER BY id_prezzo DESC LIMIT 1",
                    'i', [$pid]);
                $id_prezzo = $ps[0]['id_prezzo'] ?? null;

                if ($tipo !== 'fresco_sfuso') {
                    $g = db_query($conn, "SELECT quantita_disponibile FROM GIACENZA WHERE id_prodotto=?", 'i', [$pid]);
                    if (($g[0]['quantita_disponibile'] ?? 0) < $qty) {
                        $_SESSION['flash_msg']  = "Giacenza insufficiente per prodotto #$pid";
                        $_SESSION['flash_type'] = 'error';
                        db_execute($conn, "DELETE FROM VENDITA WHERE id_vendita=?", 'i', [$id_vendita]);
                        header('Location: vendite.php'); exit;
                    }
                }

                db_execute($conn,
                    "INSERT INTO DETTAGLIO_VENDITA (quantita,prezzo_unitario,totale_riga,tipo_vendita,id_vendita,id_prodotto,id_prezzo) VALUES (?,?,?,?,?,?,?)",
                    'dddsiid', [$qty, $pr, $qty*$pr, $tipo, $id_vendita, $pid, $id_prezzo]);
            }
            $_SESSION['flash_msg']  = "Vendita #$id_vendita registrata!";
            $_SESSION['flash_type'] = 'success';
        }
    }

    if ($action === 'delete_vendita') {
        $id = (int)($_POST['id'] ?? 0);
        $dettagli = db_query($conn, "SELECT * FROM DETTAGLIO_VENDITA WHERE id_vendita=?", 'i', [$id]);
        foreach ($dettagli as $d) {
            if ($d['tipo_vendita'] !== 'fresco_sfuso') {
                db_execute($conn, "UPDATE GIACENZA SET quantita_disponibile=quantita_disponibile+? WHERE id_prodotto=?", 'di', [$d['quantita'],$d['id_prodotto']]);
                db_execute($conn, "UPDATE PRODOTTO SET quantita_disponibile=quantita_disponibile+?,stato='disponibile' WHERE id_prodotto=?", 'di', [$d['quantita'],$d['id_prodotto']]);
            }
        }
        db_execute($conn, "DELETE FROM VENDITA WHERE id_vendita=?", 'i', [$id]);
        $_SESSION['flash_msg']  = "Vendita annullata e giacenze ripristinate.";
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: vendite.php'); exit;
}

$filtro_cliente = (int)($_GET['cliente'] ?? 0);
$filtro_da      = $_GET['da'] ?? '';
$filtro_a       = $_GET['a']  ?? '';
$where = []; $types = ''; $params = [];
if ($filtro_cliente) { $where[] = "v.id_cliente=?";      $types .= 'i'; $params[] = $filtro_cliente; }
if ($filtro_da)       { $where[] = "v.data_vendita >= ?"; $types .= 's'; $params[] = $filtro_da; }
if ($filtro_a)        { $where[] = "v.data_vendita <= ?"; $types .= 's'; $params[] = $filtro_a; }
$wClause = $where ? 'WHERE '.implode(' AND ',$where) : '';

$vendite  = db_query($conn,
    "SELECT v.*, c.nome AS cliente_nome FROM VENDITA v
     JOIN CLIENTE c ON v.id_cliente=c.id_cliente
     $wClause ORDER BY v.data_vendita DESC, v.id_vendita DESC",
    $types, $params);

$clienti  = db_query($conn, "SELECT id_cliente, nome, tipo_cliente FROM CLIENTE ORDER BY nome");
$prodotti = db_query($conn,
    "SELECT p.id_prodotto, p.nome, p.tipo_prodotto, p.unita_misura, ps.prezzo_unitario
     FROM PRODOTTO p
     LEFT JOIN PREZZO_STORICO ps ON ps.id_prodotto=p.id_prodotto AND ps.data_fine IS NULL
     WHERE p.stato='disponibile' ORDER BY p.nome");

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Vendite<small>Registro vendite e ricavi</small></h1></div>
    <button class="btn btn--primary" data-modal-open="modal-nuova-vendita">+ Nuova Vendita</button>
</div>

<form method="get" class="filter-bar">
    <div class="form-group"><label>Cliente</label>
        <select name="cliente"><option value="">Tutti</option>
        <?php foreach ($clienti as $c): ?>
        <option value="<?= $c['id_cliente'] ?>" <?= $filtro_cliente==$c['id_cliente']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?></select>
    </div>
    <div class="form-group"><label>Dal</label><input type="date" name="da" value="<?= htmlspecialchars($filtro_da) ?>"></div>
    <div class="form-group"><label>Al</label><input type="date" name="a" value="<?= htmlspecialchars($filtro_a) ?>"></div>
    <button type="submit" class="btn btn--salvia">Filtra</button>
    <a href="/vendite.php" class="btn btn--ghost">Reset</a>
</form>

<?php if ($filtro_cliente || $filtro_da || $filtro_a): ?>
<div class="alert alert--info">
    <?= count($vendite) ?> vendite — Totale: <strong>€ <?= number_format(array_sum(array_column($vendite,'totale_pagato')),2,',','.') ?></strong>
</div>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead><tr><th>#</th><th>Data</th><th>Cliente</th><th>Calcolato</th><th>Pagato</th><th>Note</th><th>Dettaglio</th><th>Azioni</th></tr></thead>
        <tbody>
        <?php if (empty($vendite)): ?>
            <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--grigio)">Nessuna vendita.</td></tr>
        <?php else: ?>
        <?php foreach ($vendite as $v):
            $sconto   = $v['totale_calcolato'] - $v['totale_pagato'];
            $dettagli = db_query($conn,
                "SELECT dv.*, p.nome AS prodotto, p.unita_misura
                 FROM DETTAGLIO_VENDITA dv JOIN PRODOTTO p ON dv.id_prodotto=p.id_prodotto
                 WHERE dv.id_vendita=?", 'i', [$v['id_vendita']]);
        ?>
        <tr>
            <td><strong>#<?= $v['id_vendita'] ?></strong></td>
            <td><?= date('d/m/Y',strtotime($v['data_vendita'])) ?></td>
            <td><?= htmlspecialchars($v['cliente_nome']) ?></td>
            <td>€ <?= number_format($v['totale_calcolato'],2,',','.') ?></td>
            <td class="price-tag">€ <?= number_format($v['totale_pagato'],2,',','.') ?>
                <?php if ($sconto > 0.005): ?><br><small style="color:var(--ocra)">(-€<?= number_format($sconto,2,',','.') ?>)</small><?php endif; ?>
            </td>
            <td style="font-size:.85rem;color:var(--grigio)"><?= htmlspecialchars($v['note'] ?? '—') ?></td>
            <td><button class="btn btn--ghost btn--sm" data-modal-open="modal-detail-<?= $v['id_vendita'] ?>">📋</button></td>
            <td>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="delete_vendita">
                    <input type="hidden" name="id" value="<?= $v['id_vendita'] ?>">
                    <button type="submit" class="btn btn--danger btn--sm" data-confirm="Annullare la vendita #<?= $v['id_vendita'] ?>?">🗑</button>
                </form>
            </td>
        </tr>
        <div class="modal-overlay" id="modal-detail-<?= $v['id_vendita'] ?>">
            <div class="modal">
                <div class="modal-header"><h3>📋 Vendita #<?= $v['id_vendita'] ?> — <?= htmlspecialchars($v['cliente_nome']) ?></h3><button class="modal-close">✕</button></div>
                <div class="modal-body">
                    <p style="margin-bottom:1rem;color:var(--grigio)">📅 <?= date('d/m/Y',strtotime($v['data_vendita'])) ?></p>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Prodotto</th><th>Tipo</th><th>Q.tà</th><th>Prezzo</th><th>Totale</th></tr></thead>
                            <tbody>
                            <?php foreach ($dettagli as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['prodotto']) ?></td>
                                <td><span class="badge badge--confezionato"><?= $d['tipo_vendita'] ?></span></td>
                                <td><?= number_format($d['quantita'],2,',','.') ?> <?= $d['unita_misura'] ?></td>
                                <td>€ <?= number_format($d['prezzo_unitario'],2,',','.') ?></td>
                                <td class="price-tag">€ <?= number_format($d['totale_riga'],2,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:var(--crema)"><td colspan="4" style="text-align:right;font-weight:600;padding:.75rem 1rem">Calcolato:</td><td class="price-tag">€ <?= number_format($v['totale_calcolato'],2,',','.') ?></td></tr>
                                <tr style="background:var(--crema)"><td colspan="4" style="text-align:right;font-weight:700;padding:.5rem 1rem;color:var(--terra)">Pagato:</td><td class="price-tag" style="font-size:1.2rem">€ <?= number_format($v['totale_pagato'],2,',','.') ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php if ($v['note']): ?><div class="alert alert--info" style="margin-top:1rem">📝 <?= htmlspecialchars($v['note']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modal-nuova-vendita">
    <div class="modal" style="max-width:820px">
        <div class="modal-header"><h3>🛒 Nuova Vendita</h3><button class="modal-close">✕</button></div>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="action" value="add_vendita">
                <div class="form-grid">
                    <div class="form-group"><label>Cliente *</label>
                        <select name="id_cliente" required><option value="">— Seleziona —</option>
                        <?php foreach ($clienti as $c): ?>
                        <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo_cliente'] ?>)</option>
                        <?php endforeach; ?></select>
                    </div>
                    <div class="form-group"><label>Data</label><input type="date" name="data_vendita" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group form-group--full"><label>Note</label><input type="text" name="note" placeholder="Sconti, omaggi…"></div>
                </div>
                <hr class="divider">
                <h3 style="margin-bottom:1rem">Prodotti</h3>
                <div id="righe-vendita">
                    <div class="sale-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:.75rem;margin-bottom:.75rem;align-items:end">
                        <div class="form-group" style="margin:0"><label>Prodotto</label>
                            <select name="prodotto_id[]" required><option value="">— Seleziona —</option>
                            <?php foreach ($prodotti as $p): ?>
                            <option value="<?= $p['id_prodotto'] ?>" data-price="<?= $p['prezzo_unitario'] ?>"><?= htmlspecialchars($p['nome']) ?> (<?= $p['unita_misura'] ?>)</option>
                            <?php endforeach; ?></select>
                        </div>
                        <div class="form-group" style="margin:0"><label>Tipo</label>
                            <select name="tipo_vendita[]">
                                <option value="confezionato">Confezionato</option>
                                <option value="fresco_sfuso">Fresco sfuso</option>
                                <option value="riserva_sfuso">Riserva sfuso</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0"><label>Q.tà</label><input type="number" name="quantita[]" class="qty" step="1" min="1" value="1" required></div>
                        <div class="form-group" style="margin:0"><label>Prezzo €</label><input type="number" name="prezzo_unit[]" class="price" step="0.1" min="0" value="0" required></div>
                        <div style="padding-bottom:.1rem"><label style="visibility:hidden">X</label><span class="row-total" style="font-weight:700;color:var(--terra);font-family:'Playfair Display',serif">€ 0.00</span></div>
                    </div>
                </div>
                <button type="button" id="btn-add-row" class="btn btn--ghost btn--sm" style="margin-bottom:1.5rem">+ Aggiungi prodotto</button>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;padding:.75rem 1rem;background:var(--crema);border-radius:var(--radius)">
                    <span style="font-weight:600">Totale Calcolato:</span>
                    <span id="grand-total" style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:var(--terra)">€ 0.00</span>
                </div>
                <div class="form-group" style="max-width:220px"><label>Totale Pagato (se diverso)</label><input type="number" name="totale_pagato" step="0.1" min="0" placeholder="Lascia vuoto"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary btn--lg">💾 Salva Vendita</button>
                    <button type="button" class="btn btn--ghost modal-close">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>