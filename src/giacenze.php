<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Giacenze';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prod  = (int)($_POST['id_prodotto'] ?? 0);
    $tipo_mov = $_POST['tipo_movimento'] ?? 'carico';
    $qty      = (float)($_POST['quantita'] ?? 0);
    $note     = trim($_POST['note'] ?? '');
    if ($id_prod && $qty > 0) {
        if ($tipo_mov === 'scarico') {
            $g = db_query($conn, "SELECT quantita_disponibile FROM GIACENZA WHERE id_prodotto=?", 'i', [$id_prod]);
            if (($g[0]['quantita_disponibile'] ?? 0) < $qty) {
                $_SESSION['flash_msg'] = "Giacenza insufficiente!"; $_SESSION['flash_type'] = 'error';
                header('Location: giacenze.php'); exit;
            }
        }
        db_execute($conn, "INSERT INTO MOV_MAGAZZINO (tipo_movimento,quantita,note,id_prodotto) VALUES (?,?,?,?)", 'sdsi', [$tipo_mov,$qty,$note,$id_prod]);
        $_SESSION['flash_msg'] = "Movimento registrato."; $_SESSION['flash_type'] = 'success';
    }
    header('Location: giacenze.php'); exit;
}

$giacenze = db_query($conn,
    "SELECT p.id_prodotto, p.nome, p.tipo_prodotto, p.unita_misura, c.nome AS categoria,
            COALESCE(g.quantita_disponibile,0) AS giacenza,
            g.data_aggiornamento, ps.prezzo_unitario
     FROM PRODOTTO p
     JOIN CATEGORIA c ON p.id_categoria=c.id_categoria
     LEFT JOIN GIACENZA g ON g.id_prodotto=p.id_prodotto
     LEFT JOIN PREZZO_STORICO ps ON ps.id_prodotto=p.id_prodotto AND ps.data_fine IS NULL
     WHERE p.stato != 'archiviato' AND p.tipo_prodotto != 'FRESCO'
     ORDER BY g.quantita_disponibile ASC, p.nome");

$movimenti = db_query($conn,
    "SELECT m.*, p.nome AS prodotto, p.unita_misura
     FROM MOV_MAGAZZINO m JOIN PRODOTTO p ON m.id_prodotto=p.id_prodotto
     ORDER BY m.data_movimento DESC LIMIT 20");

$prodotti_all = db_query($conn,
    "SELECT id_prodotto, nome, unita_misura FROM PRODOTTO
     WHERE stato='disponibile' AND tipo_prodotto != 'FRESCO' ORDER BY nome");

$max_qty = max(array_column($giacenze, 'giacenza') ?: [1]);

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Giacenze<small>Situazione scorte in tempo reale</small></h1></div>
    <button class="btn btn--primary" data-modal-open="modal-movimento">+ Movimento Manuale</button>
</div>

<div class="table-wrap" style="margin-bottom:1.5rem">
    <table>
        <thead><tr><th>Prodotto</th><th>Categoria</th><th>Tipo</th><th>Giacenza</th><th>Scorte</th><th>Prezzo</th><th>Aggiornato</th></tr></thead>
        <tbody>
        <?php foreach ($giacenze as $g):
            $pct = $max_qty > 0 ? min(100, ($g['giacenza']/$max_qty)*100) : 0;
            $bc  = $g['giacenza'] <= 0 ? 'empty' : ($g['giacenza'] < 5 ? 'low' : '');
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($g['nome']) ?></strong></td>
            <td><?= htmlspecialchars($g['categoria']) ?></td>
            <td><span class="badge badge--<?= strtolower($g['tipo_prodotto']) ?>"><?= $g['tipo_prodotto'] ?></span></td>
            <td><span data-qty="<?= $g['giacenza'] ?>" style="font-weight:600"><?= number_format($g['giacenza'],2,',','.') ?></span> <small><?= $g['unita_misura'] ?></small></td>
            <td style="min-width:100px"><div class="giacenza-bar"><div class="giacenza-bar__fill giacenza-bar__fill--<?= $bc ?>" style="width:<?= $pct ?>%"></div></div></td>
            <td><?= $g['prezzo_unitario'] ? '€ '.number_format($g['prezzo_unitario'],2,',','.') : '—' ?></td>
            <td style="font-size:.8rem;color:var(--grigio)"><?= $g['data_aggiornamento'] ? date('d/m/Y H:i',strtotime($g['data_aggiornamento'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>📋 Ultimi Movimenti</h2>
    <div class="table-wrap" style="margin-top:1rem">
        <table>
            <thead><tr><th>Data</th><th>Prodotto</th><th>Tipo</th><th>Q.tà</th><th>Note</th></tr></thead>
            <tbody>
            <?php foreach ($movimenti as $m): ?>
            <tr>
                <td><?= date('d/m/Y H:i',strtotime($m['data_movimento'])) ?></td>
                <td><?= htmlspecialchars($m['prodotto']) ?></td>
                <td><span class="badge badge--<?= $m['tipo_movimento']==='carico'?'disponibile':'esaurito' ?>"><?= $m['tipo_movimento']==='carico'?'▲ Carico':'▼ '.$m['tipo_movimento'] ?></span></td>
                <td><?= number_format($m['quantita'],2,',','.') ?> <?= $m['unita_misura'] ?></td>
                <td style="font-size:.85rem;color:var(--grigio)"><?= htmlspecialchars($m['note'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modal-movimento">
    <div class="modal">
        <div class="modal-header"><h3>📦 Movimento Manuale</h3><button class="modal-close">✕</button></div>
        <div class="modal-body">
            <form method="post">
                <div class="form-grid">
                    <div class="form-group form-group--full"><label>Prodotto *</label>
                        <select name="id_prodotto" required><option value="">— Seleziona —</option>
                        <?php foreach ($prodotti_all as $p): ?>
                        <option value="<?= $p['id_prodotto'] ?>"><?= htmlspecialchars($p['nome']) ?> (<?= $p['unita_misura'] ?>)</option>
                        <?php endforeach; ?></select>
                    </div>
                    <div class="form-group"><label>Tipo</label>
                        <select name="tipo_movimento">
                            <option value="carico">▲ Carico</option>
                            <option value="scarico">▼ Scarico</option>
                            <option value="rettifica">⚙ Rettifica</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Quantità *</label><input type="number" name="quantita" step="1" min="0.001" required></div>
                    <div class="form-group form-group--full"><label>Note</label><textarea name="note"></textarea></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Registra</button>
                    <button type="button" class="btn btn--ghost modal-close">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>