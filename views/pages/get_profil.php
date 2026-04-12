<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT * FROM pasar_profil LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$data) {
        $data = ['nama_toko' => '', 'no_wa' => '', 'alamat' => '', 'deskripsi' => ''];
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
