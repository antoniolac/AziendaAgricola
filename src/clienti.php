<?php
session_start();
require_once __DIR__ . '/db.php';
$pageTitle = 'Clienti';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nome  = trim($_POST['nome'] ?? '');
        $nick  = trim($_POST['nickname'] ?? '');
        $tel   = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo  = $_POST['tipo_cliente'] ?? 'privato';
        if ($nome) {
            db_execute($conn,
                "INSERT INTO CLIENTE (nome,nickname,telefono,email,tipo_cliente) VALUES (?,?,?,?,?)",
                'sssss', [$nome, $nick?:null, $tel?:null, $email?:null, $tipo]);
            $_SESSION['flash_msg'] = "Cliente '$nome' aggiunto."; $_SESSION['flash_type'] = 'success';
        }
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $n  = db_query($conn, "SELECT COUNT(*) as n FROM VENDITA WHERE id_cliente=?", 'i', [$id])[0]['n'] ?? 0;
        if ($n > 0) {
            $_SESSION['flash_msg'] = "Impossibile: il cliente ha vendite associate."; $_SESSION['flash_type'] = 'error';
        } else {
            db_execute($conn, "DELETE FROM CLIENTE WHERE id_cliente=?", 'i', [$id]);
            $_SESSION['flash_msg'] = "Cliente eliminato."; $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: clienti.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$tipo_f = $_GET['tipo'] ?? '';
$where  = []; $types = ''; $params = [];
if ($search) { $where[] = "(c.nome LIKE ? OR c.nickname LIKE ? OR c.email LIKE ?)"; $types .= 'sss'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($tipo_f)  { $where[] = "c.tipo_cliente=?"; $types .= 's'; $params[] = $tipo_f; }
$wClause = $where ? 'WHERE '.implode(' AND ',$where) : '';

$clienti = db_query($conn,
    "SELECT c.*, COUNT(v.id_vendita) AS num_acquisti, COALESCE(SUM(v.totale_pagato),0) AS totale_speso
     FROM CLIENTE c LEFT JOIN VENDITA v ON v.id_cliente=c.id_cliente
     $wClause GROUP BY c.id_cliente ORDER BY c.nome",
    $types, $params);

include __DIR__ . '/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Clienti<small>Rubrica e storico acquisti</small></h1></div>
    <button class="btn btn--primary" data-modal-open="modal-add">+ Nuovo Cliente</button>
</div>

<form method="get" class="filter-bar">
    <div class="form-group"><label>Cerca</label><input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nome, nickname, email…"></div>
    <div class="form-group"><label>Tipo</label>
        <select name="tipo"><option value="">Tutti</option>
        <?php foreach (['privato','famiglia','amico','collega','rivenditore','occasionale'] as $t): ?>
        <option value="<?= $t ?>" <?= $tipo_f===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?></select>
    </div>
    <button type="submit" class="btn btn--salvia">Filtra</button>
    <a href="/clienti.php" class="btn btn--ghost">Reset</a>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>#</th><th>Nome</th><th>Nickname</th><th>Telefono</th><th>Email</th><th>Tipo</th><th>Acquisti</th><th>Tot. Speso</th><th>Azioni</th></tr></thead>
        <tbody>
        <?php if (empty($clienti)): ?>
            <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--grigio)">Nessun cliente trovato.</td></tr>
        <?php else: ?>
        <?php foreach ($clienti as $c): ?>
        <tr>
            <td><?= $c['id_cliente'] ?></td>
            <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
            <td><span style="color:var(--grigio)"><?= htmlspecialchars($c['nickname'] ?? '—') ?></span></td>
            <td><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
            <td><?= $c['email'] ? '<a href="mailto:'.htmlspecialchars($c['email']).'">'.htmlspecialchars($c['email']).'</a>' : '—' ?></td>
            <td><span class="badge badge--<?= $c['tipo_cliente'] ?>"><?= $c['tipo_cliente'] ?></span></td>
            <td><?= $c['num_acquisti'] ?></td>
            <td class="price-tag">€ <?= number_format($c['totale_speso'],2,',','.') ?></td>
            <td>
                <a href="/vendite.php?cliente=<?= $c['id_cliente'] ?>" class="btn btn--ghost btn--sm">📋</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $c['id_cliente'] ?>">
                    <button type="submit" class="btn btn--danger btn--sm" data-confirm="Eliminare '<?= htmlspecialchars($c['nome']) ?>'?">🗑</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <div class="modal-header"><h3>👤 Nuovo Cliente</h3><button class="modal-close">✕</button></div>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group form-group--full"><label>Nome *</label><input type="text" name="nome" required></div>
                    <div class="form-group"><label>Nickname</label><input type="text" name="nickname"></div>
                    <div class="form-group"><label>Tipo</label>
                        <select name="tipo_cliente">
                        <?php foreach (['privato','famiglia','amico','collega','rivenditore','occasionale'] as $t): ?>
                        <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                        <?php endforeach; ?></select>
                    </div>
                    <div class="form-group"><label>Telefono</label><input type="tel" name="telefono"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email"></div>
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