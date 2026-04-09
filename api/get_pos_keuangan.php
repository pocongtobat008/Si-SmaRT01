<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$bulan = $_GET['bulan'] ?? 'all';
$tahun = $_GET['tahun'] ?? 'all';

try {
    // 1. Dapatkan Semua Blok beserta Warga Lunas (yang sudah diposting)
    $stmtBlok = $pdo->query("SELECT id FROM blok");
    $blocks = $stmtBlok->fetchAll(PDO::FETCH_ASSOC);

    $whereClause = "p.status = 'LUNAS' AND p.tanggal_posting IS NOT NULL";
    $params = [];
    if ($bulan !== 'all' && $bulan !== '') { $whereClause .= " AND p.bulan = ?"; $params[] = $bulan; }
    if ($tahun !== 'all' && $tahun !== '') { $whereClause .= " AND p.tahun = ?"; $params[] = $tahun; }

    $stmtLunas = $pdo->prepare("SELECT w.blok_id, COUNT(p.id) as lunas_count, SUM(p.total_tagihan) as real_total FROM pembayaran_iuran p JOIN warga w ON p.warga_id = w.id WHERE $whereClause GROUP BY w.blok_id");
    $stmtLunas->execute($params);
    $lunasMap = [];
    foreach ($stmtLunas->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $lunasMap[$row['blok_id']] = ['count' => (int)$row['lunas_count'], 'total' => (float)$row['real_total']];
    }

    // 2. Dapatkan Master Komponen
    $masters = [];
    try { $masters = $pdo->query("SELECT blok_id, nama_komponen, nominal FROM master_iuran")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}
    
    $defaultMaster = []; $blokMaster = []; $allComponents = [];
    foreach ($masters as $m) {
        $comp = trim($m['nama_komponen']);
        if (!in_array($comp, $allComponents)) $allComponents[] = $comp;
        if (array_key_exists('blok_id', $m) && $m['blok_id'] === null) $defaultMaster[$comp] = (float)$m['nominal'];
        else if (array_key_exists('blok_id', $m)) $blokMaster[$m['blok_id']][$comp] = (float)$m['nominal'];
        else $defaultMaster[$comp] = (float)$m['nominal'];
    }
    if (empty($allComponents)) $allComponents[] = 'Iuran Pokok / Bulanan';

    // 3. Kalkulasi Total Pemasukan per Komponen/Pos
    $posPemasukan = array_fill_keys($allComponents, 0);
    foreach ($blocks as $b) {
        $bId = $b['id'];
        $lunas = $lunasMap[$bId]['count'] ?? 0;
        $realTotal = $lunasMap[$bId]['total'] ?? 0;
        $myMaster = $blokMaster[$bId] ?? $defaultMaster;
        
        $calcTotal = 0;
        foreach ($allComponents as $comp) {
            if (empty($myMaster) && $comp === 'Iuran Pokok / Bulanan') {
                $posPemasukan[$comp] += $realTotal; $calcTotal += $realTotal;
            } else {
                $nom = $myMaster[$comp] ?? 0; $subtotal = $nom * $lunas;
                $posPemasukan[$comp] += $subtotal; $calcTotal += $subtotal;
            }
        }
    }

    // 4. Ambil Total Pengeluaran per Pos dari Jurnal Keuangan
    $whereJurnal = "jenis = 'Keluar' AND pos_anggaran IS NOT NULL";
    $paramsJurnal = [];
    if ($bulan !== 'all' && $bulan !== '') { $whereJurnal .= " AND MONTH(tanggal) = ?"; $paramsJurnal[] = (int)$bulan + 1; }
    if ($tahun !== 'all' && $tahun !== '') { $whereJurnal .= " AND YEAR(tanggal) = ?"; $paramsJurnal[] = $tahun; }
    
    $stmtJurnal = $pdo->prepare("SELECT pos_anggaran, SUM(nominal) as total_keluar FROM jurnal_keuangan WHERE $whereJurnal GROUP BY pos_anggaran");
    $stmtJurnal->execute($paramsJurnal);
    $pengeluaranMap = [];
    foreach ($stmtJurnal->fetchAll(PDO::FETCH_ASSOC) as $row) { $pengeluaranMap[$row['pos_anggaran']] = (float)$row['total_keluar']; }
    
    // 5. Ambil History Pengeluaran
    $stmtHistory = $pdo->prepare("SELECT id, tanggal, keterangan, nominal, pos_anggaran, doc_number FROM jurnal_keuangan WHERE $whereJurnal ORDER BY tanggal DESC, id DESC");
    $stmtHistory->execute($paramsJurnal);
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    // Satukan data akhir
    $posData = [];
    foreach ($allComponents as $comp) { $masuk = $posPemasukan[$comp] ?? 0; $keluar = $pengeluaranMap[$comp] ?? 0; $posData[] = [ 'pos' => $comp, 'pemasukan' => $masuk, 'pengeluaran' => $keluar, 'sisa' => $masuk - $keluar ]; }

    echo json_encode(['status' => 'success', 'pos_data' => $posData, 'history' => $history]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}