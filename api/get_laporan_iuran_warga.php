<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$blok_id = isset($_GET['blok_id']) && $_GET['blok_id'] !== 'all' ? (int)$_GET['blok_id'] : null;

try {
    // 1. Ambil List Warga (Filter Blok jika ada)
    $sqlWarga = "SELECT w.id, w.nama_lengkap, w.nomor_rumah, w.blok_id, b.nama_blok, b.periode_mulai_bulan, b.periode_mulai_tahun 
                 FROM warga w 
                 JOIN blok b ON w.blok_id = b.id";
    $paramsWarga = [];
    if ($blok_id) {
        $sqlWarga .= " WHERE w.blok_id = ?";
        $paramsWarga[] = $blok_id;
    }
    $sqlWarga .= " ORDER BY b.nama_blok ASC, w.nomor_rumah ASC";
    
    $stmtWarga = $pdo->prepare($sqlWarga);
    $stmtWarga->execute($paramsWarga);
    $warga = $stmtWarga->fetchAll(PDO::FETCH_ASSOC);

    // 2. Ambil List Pembayaran untuk tahun tsb (Termasuk yang telat dibayar tahun depan atau dibayar tahun tsb untuk tahun lalu)
    // Tapi user minta "Tahun Buku", jadi kita fokus pada tagihan di tahun tersebut
    $sqlBayar = "SELECT warga_id, bulan, status, tanggal_bayar, MONTH(tanggal_bayar) as bulan_bayar, YEAR(tanggal_bayar) as tahun_bayar 
                 FROM pembayaran_iuran 
                 WHERE tahun = ? AND status = 'LUNAS'";
    $stmtBayar = $pdo->prepare($sqlBayar);
    $stmtBayar->execute([$tahun]);
    $pembayaran = $stmtBayar->fetchAll(PDO::FETCH_ASSOC);

    // Grouping pembayaran by warga_id & bulan (tagihan)
    $payMap = [];
    foreach ($pembayaran as $p) {
        $payMap[$p['warga_id']][$p['bulan']] = [
            'status' => $p['status'],
            'tanggal_bayar' => $p['tanggal_bayar'],
            'bulan_bayar' => (int)$p['bulan_bayar'] - 1, // 0-indexed
            'tahun_bayar' => (int)$p['tahun_bayar']
        ];
    }

    // 3. Proses Laporan per Warga
    $result = [];
    $summary = [
        'total_warga' => count($warga),
        'total_lunas_full' => 0,
        'total_menunggak' => 0
    ];

    $currentMonth = (int)date('n') - 1;
    $currentYear = (int)date('Y');

    foreach ($warga as $w) {
        $months = [];
        $lunasCount = 0;
        $tunggakanCount = 0;
        $expectedLunas = 0;
        
        $startMonth = $w['periode_mulai_bulan'] !== null ? (int)$w['periode_mulai_bulan'] : 0;
        $startYear = $w['periode_mulai_tahun'] !== null ? (int)$w['periode_mulai_tahun'] : 2000;
        
        for ($m = 0; $m < 12; $m++) {
            $p = $payMap[$w['id']][$m] ?? null;
            $isPaid = ($p !== null);
            $isPastOrPresent = ($tahun < $currentYear) || ($tahun == $currentYear && $m <= $currentMonth);
            $isAfterStart = ($tahun > $startYear) || ($tahun == $startYear && $m >= $startMonth);
            
            $status = 'EMPTY';
            $relasiBulan = null;
            $relasiTahun = null;
            
            if ($isAfterStart) $expectedLunas++;

            if ($isPaid) {
                $status = 'LUNAS';
                $lunasCount++;
                // Jika dibayar di bulan yang berbeda (Telat)
                if ($p['bulan_bayar'] != $m || $p['tahun_bayar'] != $tahun) {
                    $relasiBulan = $p['bulan_bayar'];
                    $relasiTahun = $p['tahun_bayar'];
                }
            } else if ($isPastOrPresent && $isAfterStart) {
                $status = 'MENUNGGAK';
                $tunggakanCount++;
            } else if (!$isAfterStart) {
                $status = 'SEBELUM_MULAI';
            }

            $months[] = [
                'bulan' => $m,
                'status' => $status,
                'relasi_bulan' => $relasiBulan,
                'relasi_tahun' => $relasiTahun,
                'db_tanggal_bayar' => $isPaid ? $p['tanggal_bayar'] : null
            ];
        }

        if ($expectedLunas > 0 && $lunasCount == $expectedLunas) $summary['total_lunas_full']++;
        if ($tunggakanCount > 0) $summary['total_menunggak']++;

        $w['history'] = $months;
        $result[] = $w;
    }

    echo json_encode([
        'status' => 'success',
        'tahun' => $tahun,
        'data' => $result,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
