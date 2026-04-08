<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Prodotti';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nome    = trim($_POST['nome'] ?? '');
        $descr   = trim($_POST['descrizione'] ?? '');
        $tipo    = $_POST['tipo_prodotto'] ?? 'FRESCO';
        $um      = $_POST['unita_misura'] ?? 'kg';
        $vendita = $_POST['vendita'] ?? 'a_peso';
        $luogo   = trim($_POST['luogo_provenienza'] ?? '');
        $id_cat  = (int)($_POST['id_categoria'] ?? 0);
        $prezzo  = (float)($_POST['prezzo'] ?? 0);
        if ($nome && $id_cat) {
            $id_prod = db_execute($conn,
                "INSERT INTO PRODOTTO (nome,descrizione,tipo_prodotto,unita_misura,vendita,quantita_disponibile,stato,luogo_provenienza,id_categoria) VALUES (?,?,?,?,?,0,'disponibile',?,?)",
                'ssssssi', [$nome,$descr,$tipo,$um,$vendita,$luogo,$id_cat]);
            if ($id_prod && $prezzo > 0) {
                db_execute($conn, "INSERT INTO PREZZO_STORICO (prezzo_unitario,data_inizio,id_prodotto) VALUES (?,CURDATE(),?)", 'di', [$prezzo,$id_prod]);
                if ($tipo !== 'FRESCO')
                    db_execute($conn, "INSERT INTO GIACENZA (quantita_disponibile,id_prodotto) VALUES (0,?)", 'i', [$id_prod]);
            }
            $_SESSION['flash_msg'] = "Prodotto '$nome' aggiunto!"; $_SESSION['flash_type'] = 'success';
        }
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db_execute($conn, "UPDATE PRODOTTO SET stato='archiviato' WHERE id_prodotto=?", 'i', [$id]);
        $_SESSION['flash_msg'] = "Prodotto archiviato."; $_SESSION['flash_type'] = 'success';
    }
    if ($action === 'update_price') {
        $id_prod = (int)($_POST['id_prodotto'] ?? 0);
        $prezzo  = (float)($_POST['nuovo_prezzo'] ?? 0);
        if ($id_prod && $prezzo > 0) {
            db_execute($conn, "UPDATE PREZZO_STORICO SET data_fine=CURDATE() WHERE id_prodotto=? AND data_fine IS NULL", 'i', [$id_prod]);
            db_execute($conn, "INSERT INTO PREZZO_STORICO (prezzo_unitario,data_inizio,id_prodotto) VALUES (?,CURDATE(),?)", 'di', [$prezzo,$id_prod]);
            $_SESSION['flash_msg'] = "Prezzo aggiornato."; $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: prodotti.php'); exit;
}

$filtro_tipo  = $_GET['tipo']      ?? '';
$filtro_cat   = (int)($_GET['categoria'] ?? 0);
$filtro_stato = $_GET['stato']     ?? 'disponibile';
$search       = trim($_GET['q']    ?? '');

$where = ["p.stato != 'archiviato'"]; $types = ''; $params = [];
if ($filtro_tipo)  { $where[] = "p.tipo_prodotto=?"; $types .= 's'; $params[] = $filtro_tipo; }
if ($filtro_cat)   { $where[] = "p.id_categoria=?";  $types .= 'i'; $params[] = $filtro_cat; }
if ($filtro_stato) { $where[] = "p.stato=?";          $types .= 's'; $params[] = $filtro_stato; }
if ($search)       { $where[] = "p.nome LIKE ?";      $types .= 's'; $params[] = "%$search%"; }
$wClause = 'WHERE ' . implode(' AND ', $where);

$prodotti  = db_query($conn,
    "SELECT p.*, c.nome AS categoria,
            ps.prezzo_unitario AS prezzo_attuale,
            g.quantita_disponibile AS giacenza
     FROM PRODOTTO p
     JOIN CATEGORIA c ON p.id_categoria=c.id_categoria
     LEFT JOIN PREZZO_STORICO ps ON ps.id_prodotto=p.id_prodotto AND ps.data_fine IS NULL
     LEFT JOIN GIACENZA g ON g.id_prodotto=p.id_prodotto
     $wClause ORDER BY c.nome, p.nome", $types, $params);

$categorie = db_query($conn, "SELECT * FROM CATEGORIA ORDER BY nome");

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Prodotti<small>Gestione del catalogo</small></h1></div>
    <button class="btn btn--primary" data-modal-open="modal-add">+ Nuovo Prodotto</button>
</div>

<form method="get" class="filter-bar">
    <div class="form-group"><label>Cerca</label>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nome prodotto…">
    </div>
    <div class="form-group"><label>Tipo</label>
        <select name="tipo"><option value="">Tutti</option>
        <?php foreach (['FRESCO','LAVORATO','RISERVA','CONFEZIONATO'] as $t): ?>
        <option value="<?= $t ?>" <?= $filtro_tipo===$t?'selected':'' ?>><?= $t ?></option>
        <?php endforeach; ?></select>
    </div>
    <div class="form-group"><label>Categoria</label>
        <select name="categoria"><option value="">Tutte</option>
        <?php foreach ($categorie as $cat): ?>
        <option value="<?= $cat['id_categoria'] ?>" <?= $filtro_cat==$cat['id_categoria']?'selected':'' ?>><?= htmlspecialchars($cat['nome']) ?></option>
        <?php endforeach; ?></select>
    </div>
    <div class="form-group"><label>Stato</label>
        <select name="stato">
            <option value="">Tutti</option>
            <option value="disponibile" <?= $filtro_stato==='disponibile'?'selected':'' ?>>Disponibile</option>
            <option value="esaurito"    <?= $filtro_stato==='esaurito'?'selected':'' ?>>Esaurito</option>
        </select>
    </div>
    <button type="submit" class="btn btn--salvia">Filtra</button>
    <a href="/prodotti.php" class="btn btn--ghost">Reset</a>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>#</th><th>Nome</th><th>Categoria</th><th>Tipo</th><th>U.M.</th><th>Vendita</th><th>Prezzo</th><th>Giacenza</th><th>Stato</th><th>Azioni</th></tr></thead>
        <tbody>
        <?php if (empty($prodotti)): ?>
            <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--grigio)">Nessun prodotto trovato.</td></tr>
        <?php else: ?>
        <?php foreach ($prodotti as $p): ?>
        <tr>
            <td><?= $p['id_prodotto'] ?></td>
            <td><strong><?= htmlspecialchars($p['nome']) ?></strong>
                <?php if ($p['descrizione']): ?><br><small style="color:var(--grigio)"><?= htmlspecialchars(mb_substr($p['descrizione'],0,50)) ?>…</small><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['categoria']) ?></td>
            <td><span class="badge badge--<?= strtolower($p['tipo_prodotto']) ?>"><?= $p['tipo_prodotto'] ?></span></td>
            <td><?= $p['unita_misura'] ?></td>
            <td><?= $p['vendita']==='a_peso'?'⚖️ peso':'🔢 pezzo' ?></td>
            <td class="price-tag"><?= $p['prezzo_attuale'] ? '€ '.number_format($p['prezzo_attuale'],2,',','.') : '—' ?></td>
            <td><?php if ($p['tipo_prodotto']!=='FRESCO'): ?>
                    <span data-qty="<?= $p['giacenza']??0 ?>"><?= $p['giacenza']??'0' ?></span> <small><?= $p['unita_misura'] ?></small>
                <?php else: ?><small style="color:var(--grigio)">sfuso</small><?php endif; ?>
            </td>
            <td><span class="badge badge--<?= $p['stato'] ?>"><?= $p['stato'] ?></span></td>
            <td>
                <div style="display:flex;gap:.4rem">
                    <button class="btn btn--ocra btn--sm" data-modal-open="modal-price-<?= $p['id_prodotto'] ?>">💰</button>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id_prodotto'] ?>">
                        <button type="submit" class="btn btn--danger btn--sm" data-confirm="Archiviare '<?= htmlspecialchars($p['nome']) ?>'?">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <div class="modal-overlay" id="modal-price-<?= $p['id_prodotto'] ?>">
            <div class="modal">
                <div class="modal-header"><h3>💰 Prezzo — <?= htmlspecialchars($p['nome']) ?></h3><button class="modal-close">✕</button></div>
                <div class="modal-body">
                    <p style="margin-bottom:1rem">Attuale: <strong class="price-tag">€ <?= number_format($p['prezzo_attuale']??0,2,',','.') ?></strong></p>
                    <form method="post">
                        <input type="hidden" name="action" value="update_price">
                        <input type="hidden" name="id_prodotto" value="<?= $p['id_prodotto'] ?>">
                        <div class="form-group"><label>Nuovo Prezzo (€/<?= $p['unita_misura'] ?>)</label><input type="number" name="nuovo_prezzo" step="0.01" min="0" required></div>
                        <div class="form-actions"><button type="submit" class="btn btn--primary">Salva</button></div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <div class="modal-header"><h3>🌱 Nuovo Prodotto</h3><button class="modal-close">✕</button></div>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group form-group--full"><label>Nome *</label><input type="text" name="nome" required></div>
                    <div class="form-group form-group--full"><label>Descrizione</label><textarea name="descrizione"></textarea></div>
                    <div class="form-group"><label>Tipo *</label>
                        <select name="tipo_prodotto" required>
                        <?php foreach (['FRESCO','LAVORATO','RISERVA','CONFEZIONATO'] as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Categoria *</label>
                        <select name="id_categoria" required><option value="">— Seleziona —</option>
                        <?php foreach ($categorie as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?></select>
                    </div>
                    <div class="form-group"><label>Unità di Misura</label>
                        <select name="unita_misura">
                        <?php foreach (['kg','g','litro','ml','pezzo','bustina','vasetto','bottiglia'] as $u): ?>
                        <option value="<?= $u ?>"><?= $u ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Modalità Vendita</label>
                        <select name="vendita"><option value="a_peso">A Peso</option><option value="a_pezzo">A Pezzo</option></select>
                    </div>
                    <div class="form-group"><label>Prezzo (€)</label><input type="number" name="prezzo" step="0.1" min="0"></div>
                    <div class="form-group"><label>Luogo Provenienza</label><input type="text" name="luogo_provenienza"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Aggiungi</button>
                    <button type="button" class="btn btn--ghost modal-close">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>