<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
try {
    $id = $_POST['id'] ?? 0;
    $title = $_POST['title'] ?? ''; $subtitle = $_POST['subtitle'] ?? ''; 
    $badge_text = $_POST['badge_text'] ?? 'Promo'; $badge_icon = $_POST['badge_icon'] ?? 'fa-fire';
    $theme_color = $_POST['theme_color'] ?? 'emerald'; $urutan = $_POST['urutan'] ?? 1; $image = $_POST['image'] ?? '';
    if ($id > 0) {
        $pdo->prepare("UPDATE pasar_slider SET title=?, subtitle=?, badge_text=?, badge_icon=?, theme_color=?, urutan=?, image=? WHERE id=?")->execute([$title, $subtitle, $badge_text, $badge_icon, $theme_color, $urutan, $image, $id]);
    } else {
        $pdo->prepare("INSERT INTO pasar_slider (title, subtitle, badge_text, badge_icon, theme_color, urutan, image) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$title, $subtitle, $badge_text, $badge_icon, $theme_color, $urutan, $image]);
    }
    echo json_encode(['status' => 'success']);
} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }