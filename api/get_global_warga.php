<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT w.*, b.nama_blok FROM warga w LEFT JOIN blok b ON w.blok_id = b.id ORDER BY w.nama_lengkap ASC");
    $warga = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $warga]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}