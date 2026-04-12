<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? 0;
    $nama = $_POST['nama_produk'] ?? '';
    $desc = $_POST['deskripsi'] ?? '';
    $harga = $_POST['harga'] ?? 0;
    $kategori = $_POST['kategori'] ?? 'Umum';
    $penjual = $_POST['penjual_nama'] ?? '';
    $wa = $_POST['no_wa'] ?? '';
    $status = $_POST['status'] ?? 'Tersedia';
    
    // Foto bisa berupa JSON array string dari JS
    $foto = $_POST['foto'] ?? '';

    if ($id > 0) {
        if ($foto) {
            $pdo->prepare("UPDATE pasar_produk SET nama_produk=?, deskripsi=?, harga=?, kategori=?, penjual_nama=?, no_wa=?, status=?, foto=? WHERE id=?")->execute([$nama, $desc, $harga, $kategori, $penjual, $wa, $status, $foto, $id]);
        } else {
            $pdo->prepare("UPDATE pasar_produk SET nama_produk=?, deskripsi=?, harga=?, kategori=?, penjual_nama=?, no_wa=?, status=? WHERE id=?")->execute([$nama, $desc, $harga, $kategori, $penjual, $wa, $status, $id]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Dagangan diperbarui.']);
    } else {
        $pdo->prepare("INSERT INTO pasar_produk (nama_produk, deskripsi, harga, kategori, penjual_nama, no_wa, status, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([$nama, $desc, $harga, $kategori, $penjual, $wa, $status, $foto]);
        echo json_encode(['status' => 'success', 'message' => 'Dagangan ditambahkan.']);
    }
} catch (Exception $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}