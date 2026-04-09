<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$bulan = $_GET['bulan'] ?? date('n') - 1;
$tahun = $_GET['tahun'] ?? date('Y');

try {
    // 1. Dapatkan daftar blok
    $stmtBlok = $pdo->query("SELECT id, nama_blok FROM blok ORDER BY nama_blok ASC");
    $blocks = $stmtBlok->fetchAll(PDO::FETCH_ASSOC);

    // Ambil total warga per blok (Dipisah agar aman dari ONLY_FULL_GROUP_BY error)
    $stmtWarga = $pdo->query("SELECT blok_id, COUNT(id) as total_warga FROM warga GROUP BY blok_id");
    $wargaMap = [];
    foreach ($stmtWarga->fetchAll(PDO::FETCH_ASSOC) as $w) {
        $wargaMap[$w['blok_id']] = (int)$w['total_warga'];
    }

    // 2. Dapatkan jumlah warga yang "LUNAS" per blok untuk periode yang dipilih
    // HANYA ambil data yang sudah DIPOSTING ke Jurnal Keuangan (tanggal_posting IS NOT NULL)
    $whereClause = "p.status = 'LUNAS' AND p.tanggal_posting IS NOT NULL";
    $params = [];

    if ($bulan !== 'all' && $bulan !== '') {
        $whereClause .= " AND p.bulan = ?";
        $params[] = $bulan;
    }
    
    if ($tahun !== 'all' && $tahun !== '') {
        $whereClause .= " AND p.tahun = ?";
        $params[] = $tahun;
    }

    $stmtLunas = $pdo->prepare("
        SELECT w.blok_id, COUNT(p.id) as lunas_count, SUM(p.total_tagihan) as real_total
        FROM pembayaran_iuran p 
        JOIN warga w ON p.warga_id = w.id 
        WHERE $whereClause
        GROUP BY w.blok_id
    ");
    $stmtLunas->execute($params);
    $lunasMap = [];
    foreach ($stmtLunas->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $lunasMap[$row['blok_id']] = [
            'count' => (int)$row['lunas_count'],
            'total' => (float)$row['real_total']
        ];
    }

    // 3. Dapatkan Master Iuran (Komponen Pembayaran)
    $masters = [];
    try {
        $stmtMaster = $pdo->query("SELECT blok_id, nama_komponen, nominal FROM master_iuran");
        $masters = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $em) {
        // Fallback jika tabel master_iuran belum diupdate dengan kolom blok_id
        try {
            $stmtMaster = $pdo->query("SELECT NULL as blok_id, nama_komponen, nominal FROM master_iuran");
            $masters = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            // Abaikan jika tabel master_iuran benar-benar tidak ada
        }
    }
    
    $defaultMaster = [];
    $blokMaster = [];
    $allComponents = [];

    foreach ($masters as $m) {
        $comp = trim($m['nama_komponen']);
        if (!in_array($comp, $allComponents)) {
            $allComponents[] = $comp;
        }
        if (array_key_exists('blok_id', $m) && $m['blok_id'] === null) {
            $defaultMaster[$comp] = (float)$m['nominal'];
        } else if (array_key_exists('blok_id', $m)) {
            $blokMaster[$m['blok_id']][$comp] = (float)$m['nominal'];
        } else {
            $defaultMaster[$comp] = (float)$m['nominal'];
        }
    }
    
    // Fallback cerdas: Jika belum ada konfigurasi master iuran, paksa buat kolom default
    if (empty($allComponents)) {
        $allComponents[] = 'Iuran Pokok / Bulanan';
    }

    // 4. Kalkulasi Data Tabel
    $result = [];
    $grandTotal = array_fill_keys($allComponents, 0);
    $grandTotal['Total Lunas'] = 0;
    $grandTotal['Total Warga'] = 0;
    $grandTotal['Total Nominal'] = 0;

    foreach ($blocks as $b) {
        $bId = $b['id'];
        $lunas = isset($lunasMap[$bId]) ? $lunasMap[$bId]['count'] : 0;
        $realTotal = isset($lunasMap[$bId]) ? $lunasMap[$bId]['total'] : 0;
        $warga = $wargaMap[$bId] ?? 0;
        $myMaster = isset($blokMaster[$bId]) ? $blokMaster[$bId] : $defaultMaster;
        
        $row = ['blok_id' => $bId, 'nama_blok' => $b['nama_blok'], 'total_warga' => $warga, 'lunas_count' => $lunas, 'komponen' => [], 'total_nominal' => 0];
        
        $grandTotal['Total Lunas'] += $lunas;
        $grandTotal['Total Warga'] += $warga;

        $calcTotal = 0;
        foreach ($allComponents as $comp) {
            if (empty($myMaster) && $comp === 'Iuran Pokok / Bulanan') {
                $subtotal = $realTotal;
            } else {
                $nom = isset($myMaster[$comp]) ? $myMaster[$comp] : 0;
                $subtotal = $nom * $lunas; // Dikalikan dengan yang lunas saja
            }
            $row['komponen'][$comp] = $subtotal;
            $calcTotal += $subtotal;
            $grandTotal[$comp] += $subtotal;
        }
        
        $row['total_nominal'] = $calcTotal;
        
        $grandTotal['Total Nominal'] += $row['total_nominal'];
        $result[] = $row;
    }
    echo json_encode(['status' => 'success', 'komponen_headers' => $allComponents, 'data' => $result, 'grand_total' => $grandTotal]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}