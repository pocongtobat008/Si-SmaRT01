<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$blok_id = $_GET['blok_id'] ?? 0;

try {
    $stmtMaster = $pdo->prepare("SELECT id, nama_komponen, nominal FROM master_iuran WHERE blok_id = ?");
    $stmtMaster->execute([$blok_id]);
    $masterList = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);
    
    // Jika belum ada pengaturan khusus blok, gunakan pengaturan default (NULL)
    if (empty($masterList)) {
        $stmtMaster = $pdo->query("SELECT id, nama_komponen, nominal FROM master_iuran WHERE blok_id IS NULL");
        $masterList = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['status' => 'success', 'data' => $masterList]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}