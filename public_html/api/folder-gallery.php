<?php
/**
 * Folder-based gallery. Reads HD photos straight from public_html/Data/<folder>
 * so the client can bulk-upload via File Manager with no admin step. Images are
 * served through media.php which makes optimised, cached versions on the fly,
 * so the originals can stay full HD.
 *
 * Folder layout the client creates in File Manager:
 *   public_html/Data/logo/        (logo image)
 *   public_html/Data/finished/    (completed projects)
 *   public_html/Data/ongoing/     (live / in-progress builds)
 *   public_html/Data/videos/      (optional, future)
 *
 * Files are shown in filename order, so prefix names like 01_, 02_ to control
 * the order. The caption is derived from the filename.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

/** Absolute path to the Data root. */
function data_root(): string
{
    return dirname(__DIR__) . '/Data';
}

/** True if a Data folder exists and holds at least one image. */
function data_has(string $folder): bool
{
    return count(data_files($folder)) > 0;
}

/** List image files in a Data subfolder, sorted by name. Returns relative paths. */
function data_files(string $folder): array
{
    $folder = trim($folder, '/');
    $dir = data_root() . '/' . $folder;
    if (!is_dir($dir)) {
        return [];
    }
    $out = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..' || $name[0] === '.') {
            continue;
        }
        if (preg_match('/\.(jpe?g|png|webp)$/i', $name)) {
            $out[] = $folder . '/' . $name;
        }
    }
    natcasesort($out);
    return array_values($out);
}

/** Turn a filename into a human caption: "03_riverside-home.jpg" -> "Riverside home". */
function data_caption(string $relpath): string
{
    $name = pathinfo($relpath, PATHINFO_FILENAME);
    $name = preg_replace('/^\d+[\s_\-]*/', '', $name);      // strip leading order digits
    $name = str_replace(['_', '-'], ' ', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return $name !== '' ? ucfirst($name) : '';
}

/** Public URL that serves an optimised, cached version at the given width. */
function data_url(string $relpath, int $width): string
{
    return 'media.php?f=' . rawurlencode($relpath) . '&w=' . $width;
}

/**
 * Build gallery items from a Data folder.
 *
 * @return array<int,array{src:string,thumb:string,caption:string,alt:string,featured:bool}>
 */
function data_items(string $folder, int $width = 1000, int $limit = 0): array
{
    $files = data_files($folder);
    if ($limit > 0) {
        $files = array_slice($files, 0, $limit);
    }
    $items = [];
    foreach ($files as $f) {
        $cap = data_caption($f);
        $items[] = [
            'src'      => data_url($f, $width),
            'thumb'    => data_url($f, 520),
            'caption'  => $cap,
            'alt'      => $cap !== '' ? $cap : 'JSD Construction project',
            'featured' => false,
        ];
    }
    return $items;
}
