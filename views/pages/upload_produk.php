<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Gagal upload foto', 'data' => []];

try {
    $uploadDir = '../../public/uploads/produk/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadedFiles = [];
    if (!empty($_FILES['fotos']['name'][0])) {
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmpName) {
            $fileName = time() . '_' . $_FILES['fotos']['name'][$key];
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedFiles[] = 'public/uploads/produk/' . $fileName;
            }
        }
    }

    if (!empty($uploadedFiles)) {
        $response = ['status' => 'success', 'message' => 'Foto berhasil diunggah', 'data' => $uploadedFiles];
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
