<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
try {
    $settings = [];
    foreach ($_POST as $key => $val) {
        $settings[$key] = $val;
    }

    // Setup Direktori Upload Khusus CMS
    $uploadDir = '../../public/uploads/cms/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // Fungsi Handler File Tunggal (Logo, Favicon, Banner, Dokumen)
    function handleUpload($fileArray, $prefix, $uploadDir) {
        if (isset($fileArray) && $fileArray['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
            $filename = $prefix . '_' . time() . '.' . $ext;
            if (move_uploaded_file($fileArray['tmp_name'], $uploadDir . $filename)) {
                return 'public/uploads/cms/' . $filename;
            }
        }
        return null;
    }

    $fileKeyLogo = isset($_FILES['web_logo']) ? 'web_logo' : 'web_logo_file';
    if ($logoPath = handleUpload($_FILES[$fileKeyLogo] ?? null, 'logo', $uploadDir)) $settings['web_logo'] = $logoPath;
    
    $fileKeyFav = isset($_FILES['web_favicon']) ? 'web_favicon' : 'web_favicon_file';
    if ($faviconPath = handleUpload($_FILES[$fileKeyFav] ?? null, 'favicon', $uploadDir)) $settings['web_favicon'] = $faviconPath;
    
    $fileKeyHero = isset($_FILES['web_hero_image']) ? 'web_hero_image' : 'web_hero_image_file';
    if ($heroPath = handleUpload($_FILES[$fileKeyHero] ?? null, 'hero', $uploadDir)) $settings['web_hero_image'] = $heroPath;
    
    $fileKeyTrans = isset($_FILES['web_transparansi_file']) ? 'web_transparansi_file' : 'web_transparansi_file_input';
    if ($transPath = handleUpload($_FILES[$fileKeyTrans] ?? null, 'transparansi', $uploadDir)) $settings['web_transparansi_file'] = $transPath;

    for ($i = 1; $i <= 3; $i++) {
        $fileKey = isset($_FILES["web_slider_{$i}_image"]) ? "web_slider_{$i}_image" : "web_slider_{$i}_image_file";
        if ($slidePath = handleUpload($_FILES[$fileKey] ?? null, "slide_$i", $uploadDir)) {
            $settings["web_slider_{$i}_image"] = $slidePath;
        }
    }

    for ($i = 1; $i <= 2; $i++) {
        $fileKey = isset($_FILES["web_wisata_{$i}_image"]) ? "web_wisata_{$i}_image" : "web_wisata_{$i}_image_file";
        if ($wisataPath = handleUpload($_FILES[$fileKey] ?? null, "wisata_$i", $uploadDir)) {
            $settings["web_wisata_{$i}_image"] = $wisataPath;
        }
    }

    // Fungsi Handler Multi-file (Slider Carousel)
    $multiFileKey = isset($_FILES['web_slider_images']) ? 'web_slider_images' : 'web_slider_images_files';
    if (isset($_FILES[$multiFileKey])) {
        $sliderPaths = [];
        $count = count($_FILES[$multiFileKey]['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES[$multiFileKey]['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$multiFileKey]['name'][$i], PATHINFO_EXTENSION);
                $filename = 'slider_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($_FILES[$multiFileKey]['tmp_name'][$i], $uploadDir . $filename)) $sliderPaths[] = 'public/uploads/cms/' . $filename;
            }
        }
        if (!empty($sliderPaths)) $settings['web_slider_images'] = json_encode($sliderPaths);
    }
    if (!empty($settings)) {
        $stmt = $pdo->prepare("INSERT INTO web_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach($settings as $key => $val) $stmt->execute([$key, $val]);
    }
    echo json_encode(['status' => 'success']);
} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }