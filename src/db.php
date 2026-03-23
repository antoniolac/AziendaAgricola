<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'Azienda_db';
$db_user = getenv('DB_USER') ?: 'admin';
$db_pass = getenv('DB_PASS') ?: '';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    die('Connessione DB fallita: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

function db_query(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    if ($types && $params) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }
    if (!$result) return [];
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function db_execute(mysqli $conn, string $sql, string $types = '', array $params = []): int|bool {
    if ($types && $params) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $ok ? ($id ?: true) : false;
    }
    $ok = $conn->query($sql);
    return $ok ? ($conn->insert_id ?: true) : false;
}