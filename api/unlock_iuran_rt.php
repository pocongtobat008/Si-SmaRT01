<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $iuran_id = $_POST['iuran_id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT p.*, w.nama_lengkap, b.nama_blok FROM pembayaran_iuran p JOIN warga w ON p.warga_id = w.id JOIN blok b ON w.blok_id = b.id WHERE p.id = ?");
        $stmt->execute([$iuran_id]);
        $iuran = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($iuran && $iuran['tanggal_validasi_rt']) {
            if ($iuran['tanggal_posting']) {
                echo json_encode(['status' => 'error', 'message' => 'Data sudah diposting ke Jurnal, tidak dapat ditarik validasinya.']);
                exit;
            }

            // 1. Tarik Kunci Iuran
            $pdo->prepare("UPDATE pembayaran_iuran SET tanggal_validasi_rt = NULL WHERE id = ?")->execute([$iuran_id]);

            echo json_encode(['status' => 'success', 'message' => 'Validasi berhasil ditarik.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau belum divalidasi.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}