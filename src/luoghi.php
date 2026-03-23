<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Luoghi';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nome  = trim($_POST['nome'] ?? '');
        $descr = trim($_POST['descrizione'] ?? '');
        if ($nome) {
            $exists = db_query($conn, "SELECT id_luogo FROM LUOGO WHERE nome=?", 's', [$nome]);
            if ($exists) {
                $_SESSION['flash_msg']  = "Luogo '$nome' già esistente."; $_SESSION['flash_type'] = 'warning';
            } else {
                db_execute($conn, "INSERT INTO LUOGO (nome,descrizione) VALUES (?,?)", 'ss', [$nome,$descr]);
                $_SESSION['flash_msg']  = "Luogo aggiunto."; $_SESSION['flash_type'] = 'success';
            }
        }
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db_execute($conn, "DELETE FROM LUOGO WHERE id_luogo=?", 'i', [$id]);
        $_SESSION['flash_msg']  = "Luogo eliminato."; $_SESSION['flash_type'] = 'success';
    }
    header('Location: luoghi.php'); exit;
}

$luoghi = db_query($conn, "SELECT * FROM LUOGO ORDER BY nome");

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Luoghi<small>Sedi, magazzini e aree dell'azienda</small></h1></div>
    <button class="btn btn--primary" data-modal-open="modal-add">+ Nuovo Luogo</button>
</div>

<div class="card-grid">
<?php if (empty($luoghi)): ?>
    <div class="empty-state" style="grid-column:1/-1"><div class="empty-state__icon">📍</div><p>Nessun luogo registrato.</p></div>
<?php else: ?>
<?php foreach ($luoghi as $l): ?>
<div class="card" style="display:flex;flex-direction:column;gap:.75rem">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
            <h3>📍 <?= htmlspecialchars($l['nome']) ?></h3>
            <p style="font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars($l['descrizione'] ?? 'Nessuna descrizione.') ?></p>
        </div>
        <form method="post" style="display:inline;flex-shrink:0">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $l['id_luogo'] ?>">
            <button type="submit" class="btn btn--danger btn--sm" data-confirm="Eliminare '<?= htmlspecialchars($l['nome']) ?>'?">🗑</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <div class="modal-header"><h3>📍 Nuovo Luogo</h3><button class="modal-close">✕</button></div>
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