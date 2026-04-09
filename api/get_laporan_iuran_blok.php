<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n') - 1; // 0-11
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

    // Query untuk rincian warga yang sudah setor (termasuk No WA untuk icon)
    $stmt = $pdo->prepare("
        SELECT 
            p.id as iuran_id,
            w.nama_lengkap,
            w.nomor_rumah,
            w.no_wa,
            w.blok_id,
            b.nama_blok,
            p.total_tagihan,
            p.tanggal_bayar,
            p.tanggal_setor,
            p.metode_pembayaran,
            p.tanggal_validasi_rt,
            p.tanggal_posting
        FROM pembayaran_iuran p
        JOIN warga w ON p.warga_id = w.id
        JOIN blok b ON w.blok_id = b.id
        WHERE p.bulan = ? AND p.tahun = ? AND p.status = 'LUNAS' 
          AND (p.tanggal_setor IS NOT NULL OR p.tanggal_validasi_rt IS NOT NULL)
        ORDER BY p.tanggal_setor DESC, w.nama_lengkap ASC
    ");
    $stmt->execute([$bulan, $tahun]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil list blok untuk filter di frontend
    $blok_stmt = $pdo->query("SELECT id, nama_blok FROM blok ORDER BY nama_blok ASC");
    $blocks = $blok_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary global
    $summary_stmt = $pdo->prepare("
        SELECT 
            SUM(p.total_tagihan) as global_total_setoran,
            COUNT(DISTINCT w.blok_id) as global_total_blok_setor,
            COUNT(p.id) as global_total_warga_bayar
        FROM pembayaran_iuran p
        JOIN warga w ON p.warga_id = w.id
        WHERE p.bulan = ? AND p.tahun = ? AND p.status = 'LUNAS' 
          AND (p.tanggal_setor IS NOT NULL OR p.tanggal_validasi_rt IS NOT NULL)
    ");
    $summary_stmt->execute([$bulan, $tahun]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'blocks' => $blocks,
        'summary' => $summary,
        'filters' => [
            'bulan' => $bulan,
            'tahun' => $tahun
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
