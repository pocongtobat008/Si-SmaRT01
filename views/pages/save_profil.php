<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
try {
    $nama = $_POST['nama_toko'] ?? '';
    $wa = $_POST['no_wa'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $desc = $_POST['deskripsi'] ?? '';

    // Check if profile exists
    $stmt = $pdo->query("SELECT id FROM pasar_profil LIMIT 1");
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $pdo->prepare("UPDATE pasar_profil SET nama_toko=?, no_wa=?, alamat=?, deskripsi=? WHERE id=?")->execute([$nama, $wa, $alamat, $desc, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO pasar_profil (nama_toko, no_wa, alamat, deskripsi) VALUES (?, ?, ?, ?)")->execute([$nama, $wa, $alamat, $desc]);
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Profil toko diperbarui.']);
} catch (Exception $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
