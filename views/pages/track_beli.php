<?php
require_once '../../config/database.php';
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $pdo->prepare("UPDATE pasar_produk SET klik_beli = klik_beli + 1 WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success']);
    } catch(Exception $e) {}
}