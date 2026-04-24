<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Файл не загружен']);
    exit;
}

$file = $_FILES['photo'];

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Только изображения (jpg, png, gif, webp)']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Файл слишком большой (макс. 5 МБ)']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'food_' . uniqid() . '.' . $ext;
$dest     = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => true, 'url' => 'uploads/' . $filename]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Не удалось сохранить файл']);
}
