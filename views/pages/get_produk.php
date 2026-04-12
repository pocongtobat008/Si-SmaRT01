<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Hitung total dulu
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM pasar_produk");
    $total = $stmtTotal->fetchColumn();

    // Ambil data dengan limit dan offset
    $stmt = $pdo->prepare("SELECT * FROM pasar_produk ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
        echo json_encode(['status' => 'success', 'data' => [], 'total' => 0]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}