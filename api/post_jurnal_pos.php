<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pos = $_POST['pos_anggaran'] ?? '';
    $nominal = (float)($_POST['nominal'] ?? 0);
    $bulan = $_POST['bulan'] ?? '';
    $tahun = $_POST['tahun'] ?? '';
    $nama_bulan = $_POST['nama_bulan'] ?? '';
    
    if (!$pos || $nominal <= 0 || $bulan === '' || $tahun === '') {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau filter bulan/tahun belum spesifik.']);
        exit;
    }

    try {
        // Proteksi: Cek apakah Pemasukan untuk Pos & Periode ini sudah pernah diposting sebelumnya
        $stmtCek = $pdo->prepare("SELECT id FROM jurnal_keuangan WHERE source_type = 'pos_pemasukan' AND pos_anggaran = ? AND source_bulan = ? AND source_tahun = ?");
        $stmtCek->execute([$pos, $bulan, $tahun]);
        if ($stmtCek->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "Pemasukan untuk $pos pada periode $nama_bulan $tahun sudah pernah diposting sebelumnya."]);
            exit;
        }

        // Generate Nomor Dokumen Jurnal
        $today = date('Ymd');
        $stmtLast = $pdo->prepare("SELECT doc_number FROM jurnal_keuangan WHERE doc_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute(["JRN-$today-%"]);
        $lastDoc = $stmtLast->fetchColumn();
        $newNum = $lastDoc ? ((int)explode('-', $lastDoc)[2] + 1) : 1;
        $docNumber = "JRN-$today-" . str_pad($newNum, 3, '0', STR_PAD_LEFT);

        // Format Keterangan: Pemasukan Anggaran: [Judul] - Periode [Bulan] [Tahun]
        $keterangan = "Pemasukan Anggaran: $pos - Periode $nama_bulan $tahun";

        $stmt = $pdo->prepare("INSERT INTO jurnal_keuangan (jenis, pos_anggaran, nominal, tanggal, keterangan, doc_number, source_type, source_bulan, source_tahun, created_at) VALUES ('Masuk', ?, ?, CURDATE(), ?, ?, 'pos_pemasukan', ?, ?, NOW())");
        $stmt->execute([$pos, $nominal, $keterangan, $docNumber, $bulan, $tahun]);

        echo json_encode(['status' => 'success', 'message' => "Pemasukan Pos $pos berhasil diposting ke Jurnal."]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
}