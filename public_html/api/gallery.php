<?php
/**
 * Public media reader.
 *
 * Queries the `media` table and returns image data for the public site:
 *   - ongoing  -> Photos carousel
 *   - finished -> gallery and Featured block
 *   - branding -> header logo and favicon
 *
 * Every function degrades to an empty result if the database is unavailable so
 * the public site always renders clean empty states. Filenames are never
 * hardcoded. A cache-bust token is appended so client edits show immediately.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/folder-gallery.php';

/** Web path to the uploads directory, relative to the web root. */
function uploads_base(): string
{
    return 'uploads';
}

/** Build a public src with a cache-bust token derived from the file mtime. */
function media_src(string $category, string $filename): string
{
    $rel = uploads_base() . '/' . $category . '/' . rawurlencode($filename);
    $abs = cfg('UPLOAD_DIR') . '/' . $category . '/' . $filename;
    $v = is_file($abs) ? filemtime($abs) : 0;
    return $rel . ($v ? ('?v=' . $v) : '');
}

function thumb_src(string $thumb): string
{
    if ($thumb === '' ) {
        return '';
    }
    $rel = uploads_base() . '/thumbs/' . rawurlencode($thumb);
    $abs = cfg('UPLOAD_DIR') . '/thumbs/' . $thumb;
    $v = is_file($abs) ? filemtime($abs) : 0;
    return $rel . ($v ? ('?v=' . $v) : '');
}

/**
 * Fetch active media for a category, ordered by sort_order then newest.
 *
 * @return array<int,array> rows with computed src, thumb, caption
 */
function gallery_items(string $category, bool $featuredOnly = false, int $limit = 0): array
{
    try {
        $sql = "SELECT id, category, filename, thumb, caption, is_featured, sort_order
                FROM media
                WHERE category = :cat AND is_active = 1";
        if ($featuredOnly) {
            $sql .= " AND is_featured = 1";
        }
        $sql .= " ORDER BY sort_order ASC, created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute([':cat' => $category]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'       => (int) $r['id'],
            'src'      => media_src($r['category'], $r['filename']),
            'thumb'    => thumb_src((string) ($r['thumb'] ?? '')),
            'caption'  => (string) ($r['caption'] ?? ''),
            'alt'      => (string) ($r['caption'] ?? '') ?: 'JSD Construction project',
            'featured' => (int) $r['is_featured'] === 1,
        ];
    }
    return $out;
}

/** Logo for the header. Prefers Data/logo folder, else DB branding. */
function branding_logo(): ?array
{
    if (data_has('logo')) {
        $items = data_items('logo', 260, 1);
        return $items[0] ?? null;
    }
    $items = gallery_items('branding', false, 1);
    return $items[0] ?? null;
}

/** Completed projects for the Featured block. Prefers Data/finished folder. */
function featured_projects(): array
{
    if (data_has('finished')) {
        // Show all finished photos from the folder so nothing goes to waste.
        return data_items('finished', 1000);
    }
    return gallery_items('finished', true, 3);
}

/** Ongoing builds for the Photos carousel. Prefers Data/ongoing folder. */
function ongoing_photos(): array
{
    if (data_has('ongoing')) {
        return data_items('ongoing', 1000);
    }
    return gallery_items('ongoing');
}

/** Hero background: first finished image (Data folder preferred), else DB. */
function hero_image(): ?array
{
    if (data_has('finished')) {
        $items = data_items('finished', 1800, 1);
        return $items[0] ?? null;
    }
    $f = gallery_items('finished', true, 1);
    if ($f) {
        return $f[0];
    }
    $any = gallery_items('finished', false, 1);
    return $any[0] ?? null;
}

/** Favicon source. Prefers Data/logo, else DB branding thumb. */
function favicon_image(): ?array
{
    if (data_has('logo')) {
        $items = data_files('logo');
        if ($items) {
            return ['src' => data_url($items[0], 96)];
        }
    }
    try {
        $stmt = db()->prepare(
            'SELECT thumb FROM media WHERE category = "branding" AND is_active = 1 AND thumb <> ""
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute();
        $thumb = (string) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return null;
    }
    if ($thumb === '') {
        return null;
    }
    return ['src' => thumb_src($thumb)];
}

/** About image: a finished image that differs from the hero where possible. */
function about_image(): ?array
{
    if (data_has('finished')) {
        $items = data_items('finished', 900, 4);
        return $items[1] ?? ($items[0] ?? null);
    }
    $items = gallery_items('finished', false, 4);
    if (count($items) >= 2) {
        return $items[1];
    }
    return null;
}
