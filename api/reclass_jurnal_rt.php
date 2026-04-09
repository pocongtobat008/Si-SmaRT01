<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID jurnal tidak valid.']);
        exit;
    }

    try {
        // 1. Ambil data jurnal untuk mendapatkan metadata source
        $stmt = $pdo->prepare("SELECT * FROM jurnal_keuangan WHERE id = ?");
        $stmt->execute([$id]);
        $jurnal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$jurnal) {
            echo json_encode(['status' => 'error', 'message' => 'Data jurnal tidak ditemukan.']);
            exit;
        }

        if ($jurnal['source_type'] !== 'iuran_warga') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya transaksi dari iuran warga yang dapat di-reclass secara otomatis.']);
            exit;
        }

        $pdo->beginTransaction();

        // 2. Update status pembayaran_iuran kembali ke Belum Validasi & Belum Posting
        $source_bulan = isset($jurnal['source_bulan']) && $jurnal['source_bulan'] !== '' ? (int)$jurnal['source_bulan'] : null;
        $source_tahun = !empty($jurnal['source_tahun']) ? (int)$jurnal['source_tahun'] : null;
        $source_id_blok = !empty($jurnal['source_id_blok']) ? (int)$jurnal['source_id_blok'] : null;

        // Fallback jika metadata kosong (misal dari data lama atau bulk action yang belum terupdate)
        if ($source_bulan === null || $source_tahun === null || $source_id_blok === null) {
            $nama_blok = null;
            $nama_bulan = null;
            $tahun = null;

            // Deteksi berbagai macam format Keterangan Jurnal yang mungkin ada di Database
            if (preg_match('/\[(.*?)\] - Periode ([A-Za-z]+) ([0-9]{4})/', $jurnal['keterangan'], $matches) || preg_match('/Blok (.*?), periode ([A-Za-z]+) ([0-9]{4})/', $jurnal['keterangan'], $matches)) {
                $nama_blok = $matches[1];
                $nama_bulan = $matches[2];
                $tahun = $matches[3];

                $bulanArr = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $bulan_index = array_search($nama_bulan, $bulanArr);

                if ($bulan_index !== false) {
                    $stmtBlok = $pdo->prepare("SELECT id FROM blok WHERE nama_blok = ?");
                    $stmtBlok->execute([$nama_blok]);
                    $blok_id = $stmtBlok->fetchColumn();

                    if ($blok_id) {
                        $source_bulan = $bulan_index;
                        $source_tahun = $tahun;
                        $source_id_blok = $blok_id;
                    }
                }
            }
        }

        if ($source_bulan === null || $source_tahun === null || $source_id_blok === null) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengidentifikasi sumber periode iuran dari jurnal ini. Pastikan jurnal memiliki keterangan format sistem.']);
            exit;
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE pembayaran_iuran 
            SET tanggal_posting = NULL 
            WHERE bulan = ? 
              AND tahun = ? 
              AND tanggal_posting IS NOT NULL
              AND warga_id IN (SELECT id FROM warga WHERE blok_id = ?)
        ");
        $stmtUpdate->execute([$source_bulan, $source_tahun, $source_id_blok]);
        $affected_rows = $stmtUpdate->rowCount();

        // 3. Hapus data jurnal keuangan tersebut
        $stmtDelete = $pdo->prepare("DELETE FROM jurnal_keuangan WHERE id = ?");
        $stmtDelete->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => "Transaksi berhasil di-reclass. $affected_rows data iuran warga telah dikembalikan ke Tervalidasi (Belum Posting)."]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
