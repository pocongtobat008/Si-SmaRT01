<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pos = $_POST['pos_anggaran'] ?? '';
    $nominal = (float)($_POST['nominal'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    
    if (!$pos || $nominal <= 0 || !$tanggal || !$keterangan) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']); exit;
    }

    try {
        $today = date('Ymd');
        $stmtLast = $pdo->prepare("SELECT doc_number FROM jurnal_keuangan WHERE doc_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute(["JRN-$today-%"]);
        $lastDoc = $stmtLast->fetchColumn();
        $newNum = $lastDoc ? ((int)explode('-', $lastDoc)[2] + 1) : 1;
        $docNumber = "JRN-$today-" . str_pad($newNum, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO jurnal_keuangan (jenis, pos_anggaran, nominal, tanggal, keterangan, doc_number, created_at) VALUES ('Keluar', ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$pos, $nominal, $tanggal, $keterangan, $docNumber]);

        echo json_encode(['status' => 'success', 'message' => "Pengeluaran Rp " . number_format($nominal, 0, ',', '.') . " untuk pos $pos berhasil dicatat."]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
}