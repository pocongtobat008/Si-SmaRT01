<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$bulan = $_GET['bulan'] ?? 'all';
$tahun = $_GET['tahun'] ?? 'all';

try {
    // Filter Periode
    $where = "1=1";
    $where_before = "1=0"; // Default false
    $params = [];
    $params_before = [];

    if ($tahun !== 'all') {
        if ($bulan !== 'all') {
            $where .= " AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
            $params[] = (int)$bulan + 1;
            $params[] = $tahun;
            
            // Kondisi untuk mencari Saldo Awal (sebelum bulan ini)
            $m = str_pad((int)$bulan + 1, 2, '0', STR_PAD_LEFT);
            $where_before = "tanggal < ?";
            $params_before[] = "$tahun-$m-01";
        } else {
            $where .= " AND YEAR(tanggal) = ?";
            $params[] = $tahun;
            
            // Kondisi untuk mencari Saldo Awal (sebelum tahun ini)
            $where_before = "tanggal < ?";
            $params_before[] = "$tahun-01-01";
        }
    }

    // 1. Saldo Global (Seluruh Waktu) untuk Kartu Net Saldo
    $stmtGlobal = $pdo->query("SELECT SUM(CASE WHEN jenis='Masuk' THEN nominal ELSE 0 END) - SUM(CASE WHEN jenis='Keluar' THEN nominal ELSE 0 END) as net_saldo FROM jurnal_keuangan");
    $global_saldo = (float)$stmtGlobal->fetchColumn();
    
    // 2. Saldo Awal (Sebelum periode yang difilter)
    $stmtAwal = $pdo->prepare("SELECT SUM(CASE WHEN jenis='Masuk' THEN nominal ELSE -nominal END) FROM jurnal_keuangan WHERE $where_before");
    $stmtAwal->execute($params_before);
    $saldo_awal = (float)$stmtAwal->fetchColumn();

    // 3. Total Debit & Kredit di Periode Terpilih
    $stmtTotal = $pdo->prepare("SELECT SUM(CASE WHEN jenis='Masuk' THEN nominal ELSE 0 END) as total_debit, SUM(CASE WHEN jenis='Keluar' THEN nominal ELSE 0 END) as total_kredit FROM jurnal_keuangan WHERE $where");
    $stmtTotal->execute($params);
    $totals = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
    $debit = (float)$totals['total_debit'];
    $kredit = (float)$totals['total_kredit'];

    // 4. Laporan Pos Anggaran (Untuk Laporan Warga)
    // Diambil murni dari rekap Jurnal Keuangan (Pemasukan hasil Posting Pos vs Pengeluaran riil)
    // Tanpa filter waktu agar mencerminkan saldo saat ini (All-time)
    
    // Ambil daftar Master Iuran agar semua kategori pos selalu muncul (meskipun 0)
    $masters = [];
    try { $masters = $pdo->query("SELECT DISTINCT nama_komponen FROM master_iuran")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e){}
    
    $posMap = [];
    $posMap['Kas Induk (Iuran Global)'] = ['pemasukan' => 0, 'pengeluaran' => 0];
    foreach($masters as $m) {
        if (trim($m) !== '') $posMap[trim($m)] = ['pemasukan' => 0, 'pengeluaran' => 0];
    }

    $stmtPos = $pdo->prepare("
        SELECT pos_anggaran, 
               SUM(CASE WHEN jenis='Masuk' THEN nominal ELSE 0 END) as debit, 
               SUM(CASE WHEN jenis='Keluar' THEN nominal ELSE 0 END) as kredit 
        FROM jurnal_keuangan 
        GROUP BY pos_anggaran
    ");
    $stmtPos->execute();
    $jurnalPosRaw = $stmtPos->fetchAll(PDO::FETCH_ASSOC);

    foreach($jurnalPosRaw as $row) {
        $name = $row['pos_anggaran'] ? trim($row['pos_anggaran']) : 'Kas Induk (Iuran Global)';
        if (!isset($posMap[$name])) $posMap[$name] = ['pemasukan' => 0, 'pengeluaran' => 0];
        $posMap[$name]['pemasukan'] += (float)$row['debit'];
        $posMap[$name]['pengeluaran'] += (float)$row['kredit'];
    }

    $posEstimasi = [];
    $total_estimasi_sisa = 0;
    foreach ($posMap as $nama_pos => $data) {
        // Abaikan Kas Induk agar tidak muncul di Laporan Pos Anggaran
        if ($nama_pos === 'Kas Induk (Iuran Global)') continue;
        
        $sisa = $data['pemasukan'] - $data['pengeluaran'];
        $total_estimasi_sisa += $sisa;
        $posEstimasi[] = [
            'nama_pos' => $nama_pos,
            'pemasukan' => $data['pemasukan'],
            'pengeluaran' => $data['pengeluaran'],
            'sisa' => $sisa
        ];
    }

    // Urutkan tabel sesuai abjad
    usort($posEstimasi, function($a, $b) {
        return strcmp($a['nama_pos'], $b['nama_pos']);
    });
    
    // 5. Rincian Transaksi Baris per Baris (Buku Besar / General Ledger)
    $stmtTrans = $pdo->prepare("SELECT id, tanggal, keterangan, pos_anggaran as raw_pos_anggaran, IFNULL(pos_anggaran, 'Kas Induk') as pos_anggaran, jenis, nominal, doc_number FROM jurnal_keuangan WHERE $where ORDER BY tanggal ASC, id ASC");
    $stmtTrans->execute($params);
    $transactions = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success', 
        'summary' => ['debit' => $debit, 'kredit' => $kredit], 
        'global_saldo' => $global_saldo, 
        'saldo_awal' => $saldo_awal, 
        'pos_estimasi' => $posEstimasi,
        'total_estimasi_sisa' => $total_estimasi_sisa,
        'transactions' => $transactions
    ]);
} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }