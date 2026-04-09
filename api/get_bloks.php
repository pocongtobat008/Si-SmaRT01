<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, nama_blok FROM blok ORDER BY nama_blok ASC");
    $bloks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $bloks]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}