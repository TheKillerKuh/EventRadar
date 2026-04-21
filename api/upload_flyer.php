<?php
// Simple upload endpoint that stores flyer and generates a thumbnail (GD required)
// Usage: multipart/form-data field name 'flyer'
header('Content-Type: application/json; charset=utf-8');

$uploadsDir = __DIR__ . '/../public/uploads';
if (!is_dir($uploadsDir)) { mkdir($uploadsDir, 0755, true); }

if (empty($_FILES['flyer']) || $_FILES['flyer']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['flyer'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','webp'];
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported file type']);
    exit;
}

$basename = bin2hex(random_bytes(8));
$target = $uploadsDir . '/' . $basename . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not move uploaded file']);
    exit;
}

$thumbPath = $uploadsDir . '/thumb_' . $basename . '.jpg';
// build public URLs (include project path so URLs work when project is not at webroot)
$baseWebDir = dirname(dirname($_SERVER['SCRIPT_NAME'])); // e.g. /EventRadar
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$publicPath = $scheme . '://' . $host . $baseWebDir . '/public/uploads/' . basename($target);
$thumbPublic = null;
$gdAvailable = extension_loaded('gd');

// attempt thumbnail creation using GD with robust checks
if (extension_loaded('gd')) {
    $info = @getimagesize($target);
    if ($info && !empty($info[0]) && !empty($info[1])) {
        $w = (int)$info[0];
        $h = (int)$info[1];
        $mime = $info['mime'] ?? '';
        $src = false;
        if ($mime === 'image/png' && function_exists('imagecreatefrompng')) $src = @imagecreatefrompng($target);
        elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($target);
        elseif (($mime === 'image/jpeg' || $mime === 'image/jpg') && function_exists('imagecreatefromjpeg')) $src = @imagecreatefromjpeg($target);
        // fallback based on extension if getimagesize didn't help
        if ($src === false) {
            if ($ext === 'png' && function_exists('imagecreatefrompng')) $src = @imagecreatefrompng($target);
            elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($target);
            elseif (function_exists('imagecreatefromjpeg')) $src = @imagecreatefromjpeg($target);
        }

        if ($src !== false) {
            $nw = 320;
            $nh = max(1, (int)($h * ($nw / max(1, $w))));
            $dst = imagecreatetruecolor($nw, $nh);
            if ($dst !== false) {
                imagecopyresampled($dst, $src, 0,0,0,0, $nw, $nh, $w, $h);
                // write jpeg thumbnail
                @imagejpeg($dst, $thumbPath, 85);
                if (file_exists($thumbPath) && filesize($thumbPath) > 0) {
                                $thumbPublic = $scheme . '://' . $host . $baseWebDir . '/public/uploads/' . basename($thumbPath);
                }
                imagedestroy($dst);
            }
            imagedestroy($src);
        }
    }
}

// If thumbnail wasn't created, fallback to original file for preview
if ($thumbPublic === null) {
    $thumbPublic = $publicPath;
}

$thumbCreated = false;
$thumbFilesize = null;
if (file_exists($thumbPath) && filesize($thumbPath) > 0) {
    $thumbCreated = true;
    $thumbFilesize = filesize($thumbPath);
}

echo json_encode([
    'ok' => true,
    'file' => $publicPath,
    'thumbnail' => $thumbPublic,
    'debug' => [ 'gd' => $gdAvailable, 'thumb_created' => $thumbCreated, 'thumb_filesize' => $thumbFilesize, 'public_base' => $baseWebDir ]
]);
?>
