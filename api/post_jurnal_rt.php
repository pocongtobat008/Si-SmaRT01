<?php
require_once '../config/database.php';
header('Content-Type: application/json');
ob_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = json_decode($_POST['ids'] ?? '[]');
    if (empty($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Pilih data yang akan diposting terlebih dahulu.']);
        exit;
    }

    try {
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        
        // Hanya ambil data yang sudah divalidasi RT, TAPI BELUM diposting
        $stmt = $pdo->prepare("SELECT p.*, b.nama_blok FROM pembayaran_iuran p JOIN warga w ON p.warga_id = w.id JOIN blok b ON w.blok_id = b.id WHERE p.id IN ($inQuery) AND p.tanggal_validasi_rt IS NOT NULL AND p.tanggal_posting IS NULL");
        $stmt->execute($ids);
        $iurans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($iurans) > 0) {
            $blocksData = []; $validIds = [];
            foreach ($iurans as $i) {
                $validIds[] = $i['id'];
                $bId = $i['blok_id'];
                $bulan = $i['bulan'];
                $tahun = $i['tahun'];
                $key = "{$bId}_{$bulan}_{$tahun}";
                
                if (!isset($blocksData[$key])) {
                    $blocksData[$key] = [
                        'blok_id' => $bId, 'nama_blok' => $i['nama_blok'], 'total_nominal' => 0, 'count' => 0, 'bulan' => $bulan, 'tahun' => $tahun
                    ];
                }
                $blocksData[$key]['total_nominal'] += $i['total_tagihan'];
                $blocksData[$key]['count']++;
            }

            $pdo->beginTransaction();
            $inValidQuery = implode(',', array_fill(0, count($validIds), '?'));
            $pdo->prepare("UPDATE pembayaran_iuran SET tanggal_posting = NOW() WHERE id IN ($inValidQuery)")->execute($validIds);

            $bulanArr = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            $today = date('Ymd');
            $stmtLast = $pdo->prepare("SELECT doc_number FROM jurnal_keuangan WHERE doc_number LIKE ? ORDER BY id DESC LIMIT 1");
            $stmtLast->execute(["JRN-$today-%"]);
            $lastDoc = $stmtLast->fetchColumn();
            $newNum = $lastDoc ? ((int)explode('-', $lastDoc)[2] + 1) : 1;
            
            $stmtJurnal = $pdo->prepare("INSERT INTO jurnal_keuangan (jenis, nominal, tanggal, keterangan, doc_number, source_type, source_id_blok, source_bulan, source_tahun, created_at) VALUES ('Masuk', ?, ?, ?, ?, 'iuran_warga', ?, ?, ?, NOW())");
            foreach ($blocksData as $key => $bData) {
                $keterangan = "Jurnal pembukuan kas iuran Blok {$bData['nama_blok']}, periode {$bulanArr[(int)$bData['bulan']]} {$bData['tahun']} sebanyak {$bData['count']} warga";
                $stmtJurnal->execute([
                    $bData['total_nominal'], 
                    date('Y-m-d'), 
                    $keterangan, 
                    "JRN-$today-" . str_pad($newNum++, 3, '0', STR_PAD_LEFT),
                    $bData['blok_id'],
                    $bData['bulan'],
                    $bData['tahun']
                ]);
            }

            $pdo->commit();
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => count($validIds) . ' setoran warga berhasil diposting ke Jurnal.']);
        } else {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Data terpilih mungkin belum divalidasi atau sudah pernah diposting.']);
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}