<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bulan = $_POST['bulan'] ?? '';
    $tahun = $_POST['tahun'] ?? '';
    $blok_id = $_POST['blok_id'] ?? 'all';
    
    $pdo->beginTransaction();
    try {
        $sql = "
            SELECT p.id, p.total_tagihan, p.bulan, p.tahun, w.blok_id, b.nama_blok
            FROM pembayaran_iuran p
            JOIN warga w ON p.warga_id = w.id
            JOIN blok b ON w.blok_id = b.id
            WHERE p.bulan = ? AND p.tahun = ? AND p.tanggal_validasi_rt IS NOT NULL AND p.tanggal_posting IS NULL
        ";
        $params = [$bulan, $tahun];
        if ($blok_id !== 'all') {
            $sql .= " AND w.blok_id = ?";
            $params[] = $blok_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $iuran_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($iuran_list)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada data validasi baru yang siap diposting.']);
            $pdo->rollBack();
            exit;
        }

        $grouped_by_blok = [];
        foreach ($iuran_list as $iuran) {
            $bId = $iuran['blok_id'];
            $bulan = $iuran['bulan'];
            $tahun = $iuran['tahun'];
            $key = "{$bId}_{$bulan}_{$tahun}";
            
            if (!isset($grouped_by_blok[$key])) {
                $grouped_by_blok[$key] = [
                    'blok_id' => $bId, 'nama_blok' => $iuran['nama_blok'], 'total_nominal' => 0, 'count' => 0, 'bulan' => $bulan, 'tahun' => $tahun
                ];
            }
            $grouped_by_blok[$key]['total_nominal'] += $iuran['total_tagihan'];
            $grouped_by_blok[$key]['count']++;
        }

        $bulanArr = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $today = date('Ymd');
        
        $stmtLast = $pdo->prepare("SELECT doc_number FROM jurnal_keuangan WHERE doc_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute(["JRN-$today-%"]);
        $lastDoc = $stmtLast->fetchColumn();
        $newNum = $lastDoc ? ((int)explode('-', $lastDoc)[2] + 1) : 1;

        $stmtJurnal = $pdo->prepare("INSERT INTO jurnal_keuangan (jenis, nominal, tanggal, keterangan, doc_number, source_type, source_id_blok, source_bulan, source_tahun, created_at) VALUES ('Masuk', ?, ?, ?, ?, 'iuran_warga', ?, ?, ?, NOW())");
        
        foreach ($grouped_by_blok as $key => $bData) {
            $keterangan = "Jurnal pembukuan kas iuran Blok {$bData['nama_blok']}, periode {$bulanArr[(int)$bData['bulan']]} {$bData['tahun']} sebanyak {$bData['count']} warga";
            $stmtJurnal->execute([
                $bData['total_nominal'], date('Y-m-d'), $keterangan, 
                "JRN-$today-" . str_pad($newNum++, 3, '0', STR_PAD_LEFT),
                $bData['blok_id'], $bData['bulan'], $bData['tahun']
            ]);
        }

        $processed_ids = array_column($iuran_list, 'id');
        $inValidQuery = implode(',', array_fill(0, count($processed_ids), '?'));
        $pdo->prepare("UPDATE pembayaran_iuran SET tanggal_posting = NOW() WHERE id IN ($inValidQuery)")->execute($processed_ids);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => count($processed_ids) . ' setoran berhasil diposting dalam ' . count($grouped_by_blok) . ' jurnal.']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}