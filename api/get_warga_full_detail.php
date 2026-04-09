<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Data Warga
    $stmt = $pdo->prepare("SELECT w.*, b.nama_blok, b.periode_mulai_bulan, b.periode_mulai_tahun FROM warga w JOIN blok b ON w.blok_id = b.id WHERE w.id = ?");
    $stmt->execute([$id]);
    $warga = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$warga) {
        echo json_encode(['status' => 'error', 'message' => 'Data warga tidak ditemukan.']);
        exit;
    }

    // Riwayat Iuran (Tahun ini)
    $tahun = date('Y');
    $stmtIuran = $pdo->prepare("SELECT * FROM pembayaran_iuran WHERE warga_id = ? AND tahun = ? ORDER BY bulan ASC");
    $stmtIuran->execute([$id, $tahun]);
    $iuran = $stmtIuran->fetchAll(PDO::FETCH_ASSOC);

    $startMonth = $warga['periode_mulai_bulan'] !== null ? (int)$warga['periode_mulai_bulan'] : 0;
    $startYear = $warga['periode_mulai_tahun'] !== null ? (int)$warga['periode_mulai_tahun'] : 2000;

    // Hitung tunggakan total (Semua tahun lalu sampai bulan berjalan tahun ini)
    $stmtTunggakan = $pdo->prepare("
        SELECT COUNT(*) as bulan_tunggak, SUM(total_tagihan) as total_tunggakan 
        FROM pembayaran_iuran 
        WHERE warga_id = ? AND status = 'MENUNGGAK' 
        AND (tahun < ? OR (tahun = ? AND bulan <= ?))
        AND (tahun > ? OR (tahun = ? AND bulan >= ?))
    ");
    $currentMonth = date('n') - 1; // 0-indexed month
    $stmtTunggakan->execute([$id, $tahun, $tahun, $currentMonth, $startYear, $startYear, $startMonth]);
    $tunggakan = $stmtTunggakan->fetch(PDO::FETCH_ASSOC);

    // Data Tambahan (Keluarga, Penghuni Lain, Kendaraan, Dokumen)
    $stmtPasangan = $pdo->prepare("SELECT * FROM warga_pasangan WHERE warga_id = ?");
    $stmtPasangan->execute([$id]);
    $pasangan = $stmtPasangan->fetch(PDO::FETCH_ASSOC);

    $stmtAnak = $pdo->prepare("SELECT * FROM warga_anak WHERE warga_id = ?");
    $stmtAnak->execute([$id]);
    $anak = $stmtAnak->fetchAll(PDO::FETCH_ASSOC);

    $stmtOrangLain = $pdo->prepare("SELECT * FROM warga_orang_lain WHERE warga_id = ?");
    $stmtOrangLain->execute([$id]);
    $orang_lain = $stmtOrangLain->fetchAll(PDO::FETCH_ASSOC);

    $stmtKendaraan = $pdo->prepare("SELECT * FROM warga_kendaraan WHERE warga_id = ?");
    $stmtKendaraan->execute([$id]);
    $kendaraan = $stmtKendaraan->fetchAll(PDO::FETCH_ASSOC);

    $stmtDokumen = $pdo->prepare("SELECT * FROM warga_dokumen WHERE warga_id = ?");
    $stmtDokumen->execute([$id]);
    $dokumen = $stmtDokumen->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $warga,
        'iuran_tahun_ini' => $iuran,
        'tunggakan' => $tunggakan,
        'pasangan' => $pasangan ?: null,
        'anak' => $anak,
        'orang_lain' => $orang_lain,
        'kendaraan' => $kendaraan,
        'dokumen' => $dokumen
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}