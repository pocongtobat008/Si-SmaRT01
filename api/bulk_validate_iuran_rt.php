<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bulan = $_POST['bulan'] ?? '';
    $tahun = $_POST['tahun'] ?? '';
    $blok_id = $_POST['blok_id'] ?? 'all';
    try {
        $sql = "SELECT p.*, w.nama_lengkap, b.nama_blok FROM pembayaran_iuran p JOIN warga w ON p.warga_id = w.id JOIN blok b ON w.blok_id = b.id WHERE p.bulan = ? AND p.tahun = ? AND p.status = 'LUNAS' AND p.tanggal_setor IS NOT NULL AND p.tanggal_validasi_rt IS NULL";
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
                $ids[] = $i['id'];
            }

            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE pembayaran_iuran SET tanggal_validasi_rt = NOW() WHERE id IN ($inQuery)")->execute($ids);

            echo json_encode(['status' => 'success', 'message' => count($iurans) . ' data berhasil divalidasi.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada setoran baru yang perlu divalidasi. Pastikan Bendahara Blok telah melakukan "Setor ke RT Pusat" terlebih dahulu.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}