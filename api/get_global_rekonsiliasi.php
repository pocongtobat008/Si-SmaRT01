<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$blok_id = isset($_GET['blok_id']) && $_GET['blok_id'] !== 'all' ? (int)$_GET['blok_id'] : null;

try {
    // 1. Ambil List Warga (Filter Blok jika ada)
    $sqlWarga = "SELECT w.id, w.nama_lengkap, w.nomor_rumah, w.no_wa, w.blok_id, b.nama_blok 
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

    // 2. Ambil List Pembayaran untuk tahun tsb
    $sqlBayar = "SELECT warga_id, bulan, status, total_tagihan, tanggal_validasi_rt 
                 FROM pembayaran_iuran 
                 WHERE tahun = ? AND status = 'LUNAS'";
    $stmtBayar = $pdo->prepare($sqlBayar);
    $stmtBayar->execute([$tahun]);
    $pembayaran = $stmtBayar->fetchAll(PDO::FETCH_ASSOC);

    // Grouping pembayaran by warga_id & bulan untuk akses cepat
    $payMap = [];
    foreach ($pembayaran as $p) {
        $payMap[$p['warga_id']][$p['bulan']] = [
            'status' => $p['status'],
            'validated' => $p['tanggal_validasi_rt'] !== null
        ];
    }

    // 3. Ambil Master Iuran (Default atau per Blok) untuk estimasi tunggakan
    $stmtMaster = $pdo->query("SELECT blok_id, SUM(nominal) as total FROM master_iuran GROUP BY blok_id");
    $masters = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);
    $masterMap = [];
    $defaultMaster = 0;
    foreach ($masters as $m) {
        if ($m['blok_id'] === null) $defaultMaster = $m['total'];
        else $masterMap[$m['blok_id']] = $m['total'];
    }

    // 4. Proses Rekonsiliasi per Warga
    $result = [];
    $summary = [
        'total_warga' => count($warga),
        'total_lunas_full' => 0,
        'total_menunggak' => 0,
        'estimasi_piutang' => 0
    ];

    $currentMonth = (int)date('n') - 1; // 0-indexed bulan ini
    $currentYear = (int)date('Y');

    foreach ($warga as $w) {
        $months = [];
        $lunasCount = 0;
        $tunggakanCount = 0;
        
        // Loop 12 Bulan (0-11)
        for ($m = 0; $m < 12; $m++) {
            $isPaid = isset($payMap[$w['id']][$m]);
            $isPastOrPresent = ($tahun < $currentYear) || ($tahun == $currentYear && $m <= $currentMonth);
            
            $status = 'EMPTY';
            if ($isPaid) {
                $status = 'LUNAS';
                $lunasCount++;
            } else if ($isPastOrPresent) {
                $status = 'MENUNGGAK';
                $tunggakanCount++;
            }

            $months[] = [
                'bulan' => $m,
                'status' => $status,
                'validated' => $isPaid ? $payMap[$w['id']][$m]['validated'] : false
            ];
        }

        $nominalPerBulan = isset($masterMap[$w['blok_id']]) ? $masterMap[$w['blok_id']] : $defaultMaster;
        $totalHutang = $tunggakanCount * $nominalPerBulan;

        if ($lunasCount == 12) $summary['total_lunas_full']++;
        if ($tunggakanCount > 0) {
            $summary['total_menunggak']++;
            $summary['estimasi_piutang'] += $totalHutang;
        }

        $w['history'] = $months;
        $w['total_lunas'] = $lunasCount;
        $w['total_menunggak'] = $tunggakanCount;
        $w['estimasi_hutang'] = $totalHutang;
        
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
