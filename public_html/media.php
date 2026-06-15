<?php
/**
 * On-the-fly image optimiser for the folder gallery.
 *
 * Usage: media.php?f=finished/01_home.jpg&w=1000
 * Reads an original HD image from public_html/Data, returns a resized,
 * compressed WebP, and caches it in Data/.cache so later requests are instant.
 * Originals are never modified.
 *
 * Hardened: path is sanitised and confined to the Data directory, only real
 * images are processed, output is a safe re-encoded WebP.
 */

$DATA = __DIR__ . '/Data';

function fail(int $code): void
{
    http_response_code($code);
    header('Content-Type: text/plain');
    exit($code === 404 ? 'Not found' : 'Bad request');
}

$f = isset($_GET['f']) ? (string) $_GET['f'] : '';
$w = isset($_GET['w']) ? (int) $_GET['w'] : 1000;
$w = max(64, min(2400, $w));

// Sanitise: no null bytes, no traversal, safe characters only.
if ($f === '' || strpos($f, "\0") !== false || strpos($f, '..') !== false) {
    fail(400);
}
if (!preg_match('#^[A-Za-z0-9 _\-./]+\.(jpe?g|png|webp)$#i', $f)) {
    fail(400);
}

$src = realpath($DATA . '/' . $f);
$baseReal = realpath($DATA);
if ($src === false || $baseReal === false || strpos($src, $baseReal) !== 0 || !is_file($src)) {
    fail(404);
}

// Cache key from path, width and source modified time.
$cacheDir = $DATA . '/.cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$key = sha1($f . '|' . $w . '|' . filemtime($src)) . '.webp';
$cacheFile = $cacheDir . '/' . $key;

$send = function (string $file) {
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=2592000, immutable');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
};

if (is_file($cacheFile)) {
    $send($cacheFile);
}

// Generate. Cap memory for large HD files where allowed.
@ini_set('memory_limit', '512M');

$info = @getimagesize($src);
if ($info === false) {
    fail(404);
}
switch ($info['mime']) {
    case 'image/jpeg': $img = @imagecreatefromjpeg($src); break;
    case 'image/png':  $img = @imagecreatefrompng($src);  break;
    case 'image/webp': $img = @imagecreatefromwebp($src); break;
    default: fail(404);
}
if (!$img) {
    fail(404);
}

$sw = imagesx($img);
$sh = imagesy($img);
$scale = min(1.0, $w / $sw);
$nw = max(1, (int) round($sw * $scale));
$nh = max(1, (int) round($sh * $scale));

$dst = imagecreatetruecolor($nw, $nh);
imagealphablending($dst, false);
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $sw, $sh);

imagewebp($dst, $cacheFile, 82);
imagedestroy($img);
imagedestroy($dst);

if (is_file($cacheFile)) {
    $send($cacheFile);
}
fail(500);
