<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Lavorazioni';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_evento  = $_POST['data_evento']      ?? date('Y-m-d');
    $tipo_evento  = $_POST['tipo_evento']      ?? 'lavorazione_generica';
    $qty_in       = (float)($_POST['quantita_input']  ?? 0);
    $qty_out      = (float)($_POST['quantita_output'] ?? 0);
    $luogo        = trim($_POST['luogo_provenienza']  ?? '');
    $note         = trim($_POST['note']               ?? '');
    $input_prods  = $_POST['input_prodotti']   ?? [];
    $output_prods = $_POST['output_prodotti']  ?? [];

    $id_ev = db_execute($conn,
        "INSERT INTO EVENTO (data_evento,tipo_evento,quantita_input,quantita_output,luogo_provenienza,note) VALUES (?,?,?,?,?,?)",
        'ssddss', [$data_evento,$tipo_evento,$qty_in,$qty_out,$luogo,$note]);

    foreach ($input_prods  as $pid) { $pid=(int)$pid; if($pid) db_execute($conn,"INSERT IGNORE INTO E_INPUT_DI (id_evento,id_prodotto) VALUES (?,?)",'ii',[$id_ev,$pid]); }
    foreach ($output_prods as $pid) { $pid=(int)$pid; if($pid) db_execute($conn,"INSERT IGNORE INTO E_OUTPUT_DI (id_evento,id_prodotto) VALUES (?,?)",'ii',[$id_ev,$pid]); }

    $_SESSION['flash_msg']  = "Evento registrato.";
    $_SESSION['flash_type'] = 'success';
    header('Location: eventi.php'); exit;
}

$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_da   = $_GET['da']   ?? '';
$filtro_a    = $_GET['a']    ?? '';
$where = []; $types = ''; $params = [];
if ($filtro_tipo) { $where[] = "e.tipo_evento=?";     $types .= 's'; $params[] = $filtro_tipo; }
if ($filtro_da)   { $where[] = "e.data_evento >= ?";  $types .= 's'; $params[] = $filtro_da; }
if ($filtro_a)    { $where[] = "e.data_evento <= ?";  $types .= 's'; $params[] = $filtro_a; }
$wClause = $where ? 'WHERE '.implode(' AND ',$where) : '';

$eventi       = db_query($conn, "SELECT * FROM EVENTO e $wClause ORDER BY e.data_evento DESC", $types, $params);
$prodotti_all = db_query($conn, "SELECT id_prodotto, nome, tipo_prodotto FROM PRODOTTO WHERE stato != 'archiviato' ORDER BY nome");
$tipi_evento  = ['raccolta','smielatura','distillazione','smallatura','sgusciatura','essiccazione','confezionamento','lavorazione_generica'];

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Lavorazioni &amp; Eventi<small>Tracciabilità della filiera produttiva</small></h1></div>
    <button class="btn btn--primary" data-modal-open="modal-add">+ Nuovo Evento</button>
</div>

<form method="get" class="filter-bar">
    <div class="form-group"><label>Tipo Evento</label>
        <select name="tipo"><option value="">Tutti</option>
        <?php foreach ($tipi_evento as $t): ?>
        <option value="<?= $t ?>" <?= $filtro_tipo===$t?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$t)) ?></option>
        <?php endforeach; ?></select>
    </div>
    <div class="form-group"><label>Dal</label><input type="date" name="da" value="<?= htmlspecialchars($filtro_da) ?>"></div>
    <div class="form-group"><label>Al</label><input type="date" name="a" value="<?= htmlspecialchars($filtro_a) ?>"></div>
    <button type="submit" class="btn btn--salvia">Filtra</button>
    <a href="/eventi.php" class="btn btn--ghost">Reset</a>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>#</th><th>Data</th><th>Tipo</th><th>Input</th><th>Output</th><th>Luogo</th><th>Prodotti IN</th><th>Prodotti OUT</th><th>Note</th></tr></thead>
        <tbody>
        <?php if (empty($eventi)): ?>
            <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--grigio)">Nessuna lavorazione registrata.</td></tr>
        <?php else: ?>
        <?php foreach ($eventi as $ev):
            $inputs  = db_query($conn, "SELECT p.nome FROM E_INPUT_DI  ei JOIN PRODOTTO p ON p.id_prodotto=ei.id_prodotto WHERE ei.id_evento=?", 'i', [$ev['id_evento']]);
            $outputs = db_query($conn, "SELECT p.nome FROM E_OUTPUT_DI eo JOIN PRODOTTO p ON p.id_prodotto=eo.id_prodotto WHERE eo.id_evento=?", 'i', [$ev['id_evento']]);
        ?>
        <tr>
            <td><?= $ev['id_evento'] ?></td>
            <td><?= date('d/m/Y',strtotime($ev['data_evento'])) ?></td>
            <td><span class="badge badge--lavorato"><?= str_replace('_',' ',$ev['tipo_evento']) ?></span></td>
            <td><?= $ev['quantita_input']  ? number_format($ev['quantita_input'],2,',','.') : '—' ?></td>
            <td><?= $ev['quantita_output'] ? number_format($ev['quantita_output'],2,',','.') : '—' ?></td>
            <td style="font-size:.85rem"><?= htmlspecialchars($ev['luogo_provenienza'] ?? '—') ?></td>
            <td style="font-size:.82rem;color:var(--grigio)"><?= implode(', ', array_map(fn($r)=>htmlspecialchars($r['nome']),$inputs))  ?: '—' ?></td>
            <td style="font-size:.82rem;color:var(--salvia-dark)"><?= implode(', ', array_map(fn($r)=>htmlspecialchars($r['nome']),$outputs)) ?: '—' ?></td>
            <td style="font-size:.82rem;color:var(--grigio)"><?= htmlspecialchars($ev['note'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modal-add">
    <div class="modal" style="max-width:720px">
        <div class="modal-header"><h3>⚙️ Nuovo Evento di Lavorazione</h3><button class="modal-close">✕</button></div>
        <div class="modal-body">
            <form method="post">
                <div class="form-grid">
                    <div class="form-group"><label>Data *</label><input type="date" name="data_evento" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group"><label>Tipo *</label>
                        <select name="tipo_evento" required>
                        <?php foreach ($tipi_evento as $t): ?>
                        <option value="<?= $t ?>"><?= ucfirst(str_replace('_',' ',$t)) ?></option>
                        <?php endforeach; ?></select>
                    </div>
                    <div class="form-group"><label>Quantità Input</label><input type="number" name="quantita_input"  step="0.001" min="0"></div>
                    <div class="form-group"><label>Quantità Output</label><input type="number" name="quantita_output" step="0.001" min="0"></div>
                    <div class="form-group"><label>Luogo</label><input type="text" name="luogo_provenienza"></div>
                    <div class="form-group form-group--full"><label>Prodotti INPUT (materie prime) — CTRL per multipli</label>
                        <select name="input_prodotti[]" multiple size="5">
                        <?php foreach ($prodotti_all as $p): ?>
                        <option value="<?= $p['id_prodotto'] ?>">[<?= $p['tipo_prodotto'] ?>] <?= htmlspecialchars($p['nome']) ?></option>
                        <?php endforeach; ?></select>
                    </div>
                    <div class="form-group form-group--full"><label>Prodotti OUTPUT (risultato) — CTRL per multipli</label>
                        <select name="output_prodotti[]" multiple size="5">
                        <?php foreach ($prodotti_all as $p): ?>
                        <option value="<?= $p['id_prodotto'] ?>">[<?= $p['tipo_prodotto'] ?>] <?= htmlspecialchars($p['nome']) ?></option>
                        <?php endforeach; ?></select>
                    </div>
                    <div class="form-group form-group--full"><label>Note</label><textarea name="note"></textarea></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Registra Evento</button>
                    <button type="button" class="btn btn--ghost modal-close">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>