<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Storico Prezzi';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prod = (int)($_POST['id_prodotto'] ?? 0);
    $prezzo  = (float)($_POST['nuovo_prezzo'] ?? 0);
    if ($id_prod && $prezzo > 0) {
        db_execute($conn, "UPDATE PREZZO_STORICO SET data_fine=CURDATE() WHERE id_prodotto=? AND data_fine IS NULL", 'i', [$id_prod]);
        db_execute($conn, "INSERT INTO PREZZO_STORICO (prezzo_unitario,data_inizio,id_prodotto) VALUES (?,CURDATE(),?)", 'di', [$prezzo,$id_prod]);
        $_SESSION['flash_msg']  = "Prezzo aggiornato."; $_SESSION['flash_type'] = 'success';
    }
    header('Location: prezzi.php'); exit;
}

$filtro_prod = (int)($_GET['prodotto'] ?? 0);
$where = []; $types = ''; $params = [];
if ($filtro_prod) { $where[] = "ps.id_prodotto=?"; $types .= 'i'; $params[] = $filtro_prod; }
$wClause = $where ? 'WHERE '.implode(' AND ',$where) : '';

$storico  = db_query($conn,
    "SELECT ps.*, p.nome AS prodotto, p.unita_misura, c.nome AS categoria
     FROM PREZZO_STORICO ps
     JOIN PRODOTTO p ON p.id_prodotto=ps.id_prodotto
     JOIN CATEGORIA c ON c.id_categoria=p.id_categoria
     $wClause ORDER BY p.nome, ps.data_inizio DESC",
    $types, $params);

$prodotti = db_query($conn, "SELECT id_prodotto, nome FROM PRODOTTO WHERE stato != 'archiviato' ORDER BY nome");

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Storico Prezzi<small>Variazioni di prezzo nel tempo</small></h1></div>
</div>

<form method="get" class="filter-bar">
    <div class="form-group"><label>Filtra per Prodotto</label>
        <select name="prodotto"><option value="">Tutti</option>
        <?php foreach ($prodotti as $p): ?>
        <option value="<?= $p['id_prodotto'] ?>" <?= $filtro_prod==$p['id_prodotto']?'selected':'' ?>><?= htmlspecialchars($p['nome']) ?></option>
        <?php endforeach; ?></select>
    </div>
    <button type="submit" class="btn btn--salvia">Filtra</button>
    <a href="/prezzi.php" class="btn btn--ghost">Reset</a>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>#</th><th>Prodotto</th><th>Categoria</th><th>Prezzo</th><th>Dal</th><th>Al</th><th>Stato</th><th>Azione</th></tr></thead>
        <tbody>
        <?php if (empty($storico)): ?>
            <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--grigio)">Nessun dato.</td></tr>
        <?php else: ?>
        <?php foreach ($storico as $ps): ?>
        <tr>
            <td><?= $ps['id_prezzo'] ?></td>
            <td><strong><?= htmlspecialchars($ps['prodotto']) ?></strong></td>
            <td><?= htmlspecialchars($ps['categoria']) ?></td>
            <td class="price-tag">€ <?= number_format($ps['prezzo_unitario'],2,',','.') ?> / <?= $ps['unita_misura'] ?></td>
            <td><?= date('d/m/Y',strtotime($ps['data_inizio'])) ?></td>
            <td><?= $ps['data_fine'] ? date('d/m/Y',strtotime($ps['data_fine'])) : '<span class="badge badge--disponibile">In corso</span>' ?></td>
            <td><?= !$ps['data_fine'] ? '<span class="badge badge--disponibile">Attivo</span>' : '<span class="badge badge--archiviato">Storico</span>' ?></td>
            <td>
                <?php if (!$ps['data_fine']): ?>
                <button class="btn btn--ocra btn--sm" data-modal-open="modal-price-<?= $ps['id_prodotto'] ?>">Aggiorna</button>
                <div class="modal-overlay" id="modal-price-<?= $ps['id_prodotto'] ?>">
                    <div class="modal">
                        <div class="modal-header"><h3>💰 Aggiorna — <?= htmlspecialchars($ps['prodotto']) ?></h3><button class="modal-close">✕</button></div>
                        <div class="modal-body">
                            <p style="margin-bottom:1rem">Attuale: <strong class="price-tag">€ <?= number_format($ps['prezzo_unitario'],2,',','.') ?></strong></p>
                            <form method="post">
                                <input type="hidden" name="id_prodotto" value="<?= $ps['id_prodotto'] ?>">
                                <div class="form-group"><label>Nuovo Prezzo (€/<?= $ps['unita_misura'] ?>)</label><input type="number" name="nuovo_prezzo" step="0.01" min="0" required></div>
                                <div class="form-actions"><button type="submit" class="btn btn--primary">Salva</button><button type="button" class="btn btn--ghost modal-close">Annulla</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/footer.php'; ?>