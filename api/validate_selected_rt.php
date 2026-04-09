<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = json_decode($_POST['ids'] ?? '[]');
    if (empty($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Pilih data yang akan divalidasi.']);
        exit;
    }

    try {
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        
        // Cek data yang bener-bener belum divalidasi
        $stmt = $pdo->prepare("SELECT id FROM pembayaran_iuran WHERE id IN ($inQuery) AND tanggal_validasi_rt IS NULL");
        $stmt->execute($ids);
        $validIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($validIds) > 0) {
            $inValidQuery = implode(',', array_fill(0, count($validIds), '?'));
            $pdo->prepare("UPDATE pembayaran_iuran SET tanggal_validasi_rt = NOW() WHERE id IN ($inValidQuery)")->execute($validIds);

            echo json_encode(['status' => 'success', 'message' => count($validIds) . ' data berhasil divalidasi.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data terpilih mungkin sudah divalidasi sebelumnya.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
