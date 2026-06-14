<?php
/**
 * Shared media helpers for the admin: directory handling and the hardened
 * upload pipeline (validate, re-encode to strip EXIF/payloads, generate an
 * optimised WebP plus a thumbnail, randomise the stored filename).
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

/** Valid media categories. */
function jsd_categories(): array
{
    return ['ongoing', 'finished', 'branding'];
}

/** Absolute path to the uploads root. */
function uploads_root(): string
{
    return rtrim((string) cfg('UPLOAD_DIR'), '/');
}

/** Ensure category and thumbs directories exist and are writable. */
function ensure_upload_dirs(): void
{
    $root = uploads_root();
    foreach (array_merge(jsd_categories(), ['thumbs']) as $sub) {
        $dir = $root . '/' . $sub;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

/** Allowed mime types mapped to a normalising loader. */
function jsd_allowed_mimes(): array
{
    return [
        'image/jpeg' => 'jpeg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
}

/**
 * Process one uploaded file into an optimised WebP plus thumbnail.
 *
 * @param array  $file     one entry from $_FILES
 * @param string $category one of jsd_categories()
 * @return array{filename:string, thumb:string}
 * @throws RuntimeException on any validation or processing failure
 */
function process_upload(array $file, string $category): array
{
    if (!in_array($category, jsd_categories(), true)) {
        throw new RuntimeException('Invalid category.');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (code ' . ($file['error'] ?? '?') . ').');
    }

    $maxBytes = (int) cfg('UPLOAD_MAX_BYTES', 8 * 1024 * 1024);
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) {
        throw new RuntimeException('File too large. Max ' . round($maxBytes / 1048576) . 'MB.');
    }

    $tmp = $file['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        // is_uploaded_file is false in CLI tests; allow a readable temp file there.
        if (!(PHP_SAPI === 'cli' && is_file($tmp))) {
            throw new RuntimeException('Invalid upload.');
        }
    }

    // Verify it is a real image by content, not by extension.
    $info = @getimagesize($tmp);
    if ($info === false || empty($info['mime'])) {
        throw new RuntimeException('File is not a valid image.');
    }
    $mime = strtolower($info['mime']);
    $allowed = jsd_allowed_mimes();
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Unsupported image type. Use JPG, PNG or WebP.');
    }

    // Guard against decompression bombs.
    $w = (int) $info[0];
    $h = (int) $info[1];
    if ($w < 1 || $h < 1 || ($w * $h) > 40_000_000) {
        throw new RuntimeException('Image dimensions are out of range.');
    }

    $src = jsd_load_image($tmp, $allowed[$mime]);
    if (!$src) {
        throw new RuntimeException('Could not read the image.');
    }
    $src = jsd_fix_orientation($src, $tmp, $allowed[$mime]);

    ensure_upload_dirs();
    $base = bin2hex(random_bytes(16));
    $isBranding = ($category === 'branding');

    // Main image: cap the long edge to keep files lean.
    $maxEdge = $isBranding ? 900 : 2000;
    $main = jsd_resize_within($src, $maxEdge);
    $mainName = $base . '.webp';
    $mainPath = uploads_root() . '/' . $category . '/' . $mainName;
    if (!imagewebp($main, $mainPath, 82)) {
        imagedestroy($src);
        imagedestroy($main);
        throw new RuntimeException('Could not write the optimised image.');
    }

    // Thumbnail. Branding stores a PNG favicon-style thumb; others a small WebP.
    if ($isBranding) {
        $thumbName = $base . '.png';
        $thumb = jsd_resize_within($src, 180);
        imagepng($thumb, uploads_root() . '/thumbs/' . $thumbName, 6);
    } else {
        $thumbName = $base . '_t.webp';
        $thumb = jsd_resize_within($src, 480);
        imagewebp($thumb, uploads_root() . '/thumbs/' . $thumbName, 78);
    }

    imagedestroy($src);
    imagedestroy($main);
    if (isset($thumb)) {
        imagedestroy($thumb);
    }

    return ['filename' => $mainName, 'thumb' => $thumbName];
}

/** Load an image resource from a path for a known type. */
function jsd_load_image(string $path, string $type)
{
    switch ($type) {
        case 'jpeg': $img = @imagecreatefromjpeg($path); break;
        case 'png':  $img = @imagecreatefrompng($path);  break;
        case 'webp': $img = @imagecreatefromwebp($path); break;
        default:     return null;
    }
    return $img ?: null;
}

/** Apply EXIF orientation for JPEGs so rotated photos display upright. */
function jsd_fix_orientation($img, string $path, string $type)
{
    if ($type !== 'jpeg' || !function_exists('exif_read_data')) {
        return $img;
    }
    $exif = @exif_read_data($path);
    $orientation = $exif['Orientation'] ?? 0;
    if ($orientation === 3) {
        return imagerotate($img, 180, 0);
    }
    if ($orientation === 6) {
        return imagerotate($img, -90, 0);
    }
    if ($orientation === 8) {
        return imagerotate($img, 90, 0);
    }
    return $img;
}

/** Resize within a max edge, preserving aspect ratio and alpha. Never upscales. */
function jsd_resize_within($src, int $maxEdge)
{
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1.0, $maxEdge / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $dst;
}
