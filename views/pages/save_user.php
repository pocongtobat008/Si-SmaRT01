<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
try {
    $id = $_POST['id'] ?? 0;
    $nama = $_POST['nama_lengkap'] ?? ''; $user = $_POST['username'] ?? ''; $role = $_POST['role'] ?? 'Admin';
    if ($id > 0) {
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE web_users SET nama_lengkap=?, username=?, role=?, password=? WHERE id=?")->execute([$nama, $user, $role, $pass, $id]);
        } else {
            $pdo->prepare("UPDATE web_users SET nama_lengkap=?, username=?, role=? WHERE id=?")->execute([$nama, $user, $role, $id]);
        }
    } else {
        $pdo->prepare("INSERT INTO web_users (nama_lengkap, username, role, password) VALUES (?, ?, ?, ?)")->execute([$nama, $user, $role, password_hash($_POST['password'], PASSWORD_DEFAULT)]);
    }
    echo json_encode(['status' => 'success']);
} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }