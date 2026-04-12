<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT * FROM pasar_penjual ORDER BY created_at DESC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    // Mengirim array kosong jika tabel belum ada agar tidak error JSON
    echo json_encode(['status' => 'success', 'data' => []]);
}