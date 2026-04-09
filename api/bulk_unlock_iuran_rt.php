<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bulan = $_POST['bulan'] ?? '';
    $tahun = $_POST['tahun'] ?? '';
    $blok_id = $_POST['blok_id'] ?? 'all';
    try {
        $sql = "SELECT p.*, w.nama_lengkap, b.nama_blok FROM pembayaran_iuran p JOIN warga w ON p.warga_id = w.id JOIN blok b ON w.blok_id = b.id WHERE p.bulan = ? AND p.tahun = ? AND p.tanggal_validasi_rt IS NOT NULL";
        $params = [$bulan, $tahun];
        if ($blok_id !== 'all') {
            $sql .= " AND w.blok_id = ?";
            $params[] = $blok_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $iurans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($iurans) > 0) {
            $ids = [];
            foreach ($iurans as $i) {
                // Jangan tarik validasi jika sudah diposting
                if ($i['tanggal_posting'] !== null) continue;
                $ids[] = $i['id'];
            }

            if (count($ids) === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Semua data telah diposting secara permanen dan tidak bisa ditarik validasinya.']);
                exit;
            }

            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE pembayaran_iuran SET tanggal_validasi_rt = NULL WHERE id IN ($inQuery)")->execute($ids);

            echo json_encode(['status' => 'success', 'message' => count($ids) . ' validasi berhasil ditarik.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada data validasi pada bulan ini yang bisa ditarik.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}