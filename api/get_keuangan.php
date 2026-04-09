<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM jurnal_keuangan ORDER BY tanggal DESC, id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}