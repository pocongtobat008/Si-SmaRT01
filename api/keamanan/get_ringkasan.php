<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    // 1. Total Personel Aktif (Abaikan error jika tabel satpam belum Anda buat)
    $satpam_aktif = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM km_satpam WHERE status = 'Aktif'");
        $satpam_aktif = $stmt->fetchColumn();
    } catch(Exception $e) { }

    // 2. Laporan Baru
    $laporan_baru = 0;
    try {
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM laporan_keamanan WHERE status = 'Baru'");
        $laporan_baru = $stmt2->fetchColumn();
    } catch(Exception $e) { }

    // 3. Aktivitas Terbaru (3 Laporan terakhir)
    $aktifitas = [];
    try {
        $stmt3 = $pdo->query("SELECT judul, DATE_FORMAT(waktu_kejadian, '%d %b %Y, %H:%i') as waktu FROM laporan_keamanan ORDER BY waktu_kejadian DESC LIMIT 3");
        $aktifitas = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) { }

    echo json_encode([
        'status' => 'success', 
        'data' => [
            'satpam_aktif' => $satpam_aktif,
            'laporan_baru' => $laporan_baru,
            'aktifitas' => $aktifitas
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}