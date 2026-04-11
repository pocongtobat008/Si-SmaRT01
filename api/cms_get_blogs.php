<?php
require_once '../config/database.php';
header('Content-Type: application/json');
try {
    // Auto-migration check: tambahkan kolom jika belum ada
    $cols = $pdo->query("SHOW COLUMNS FROM web_blogs")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('thumbnail', $cols)) $pdo->exec("ALTER TABLE web_blogs ADD COLUMN thumbnail VARCHAR(255) DEFAULT NULL AFTER status");
    if (!in_array('video_url', $cols)) $pdo->exec("ALTER TABLE web_blogs ADD COLUMN video_url VARCHAR(255) DEFAULT NULL AFTER thumbnail");
    if (!in_array('youtube_url', $cols)) $pdo->exec("ALTER TABLE web_blogs ADD COLUMN youtube_url VARCHAR(255) DEFAULT NULL AFTER video_url");

    $stmt = $pdo->query("SELECT id, judul, konten, status, thumbnail, video_url, youtube_url, DATE_FORMAT(created_at, '%d %b %Y') as created_at FROM web_blogs ORDER BY created_at DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }