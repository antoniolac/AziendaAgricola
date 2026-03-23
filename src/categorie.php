<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Categorie';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nome  = trim($_POST['nome'] ?? '');
        $descr = trim($_POST['descrizione'] ?? '');
        if ($nome) {
            $exists = db_query($conn, "SELECT id_categoria FROM CATEGORIA WHERE nome=?", 's', [$nome]);
            if ($exists) {
                $_SESSION['flash_msg']  = "Categoria '$nome' già esistente."; $_SESSION['flash_type'] = 'warning';
            } else {
                db_execute($conn, "INSERT INTO CATEGORIA (nome,descrizione) VALUES (?,?)", 'ss', [$nome,$descr]);
                $_SESSION['flash_msg']  = "Categoria '$nome' aggiunta."; $_SESSION['flash_type'] = 'success';
            }
        }
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $n  = db_query($conn, "SELECT COUNT(*) as n FROM PRODOTTO WHERE id_categoria=?", 'i', [$id])[0]['n'] ?? 0;
        if ($n > 0) {
            $_SESSION['flash_msg']  = "Impossibile: ci sono prodotti in questa categoria."; $_SESSION['flash_type'] = 'error';
        } else {
            db_execute($conn, "DELETE FROM CATEGORIA WHERE id_categoria=?", 'i', [$id]);
            $_SESSION['flash_msg']  = "Categoria eliminata."; $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: categorie.php'); exit;
}

$categorie = db_query($conn,
    "SELECT c.*, COUNT(p.id_prodotto) AS num_prodotti
     FROM CATEGORIA c LEFT JOIN PRODOTTO p ON p.id_categoria=c.id_categoria AND p.stato != 'archiviato'
     GROUP BY c.id_categoria ORDER BY c.nome");

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Categorie<small>Organizzazione del catalogo</small></h1></div>
    <button class="btn btn--primary" data-modal-open="modal-add">+ Nuova Categoria</button>
</div>

<div class="card-grid">
<?php foreach ($categorie as $cat): ?>
<div class="card card--accent">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
        <h3><?= htmlspecialchars($cat['nome']) ?></h3>
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $cat['id_categoria'] ?>">
            <button type="submit" class="btn btn--danger btn--sm" data-confirm="Eliminare '<?= htmlspecialchars($cat['nome']) ?>'?">🗑</button>
        </form>
    </div>
    <p style="font-size:.85rem;margin-bottom:.75rem"><?= htmlspecialchars($cat['descrizione'] ?? 'Nessuna descrizione.') ?></p>
    <div style="display:flex;align-items:center;gap:.5rem">
        <span class="badge badge--disponibile"><?= $cat['num_prodotti'] ?> prodotti</span>
        <a href="/prodotti.php?categoria=<?= $cat['id_categoria'] ?>" class="btn btn--ghost btn--sm">Vedi →</a>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <div class="modal-header"><h3>🏷️ Nuova Categoria</h3><button class="modal-close">✕</button></div>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group form-group--full"><label>Nome *</label><input type="text" name="nome" required></div>
                    <div class="form-group form-group--full"><label>Descrizione</label><textarea name="descrizione"></textarea></div>
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