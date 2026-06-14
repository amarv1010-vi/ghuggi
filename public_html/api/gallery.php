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

/** Latest active branding image (logo) for the header, or null. */
function branding_logo(): ?array
{
    $items = gallery_items('branding', false, 1);
    return $items[0] ?? null;
}

/** Top finished projects flagged featured, capped at 3, for the Featured block. */
function featured_projects(): array
{
    return gallery_items('finished', true, 3);
}

/** Ongoing builds for the Photos carousel. */
function ongoing_photos(): array
{
    return gallery_items('ongoing');
}

/** Hero background: first featured finished image, else first finished, else null. */
function hero_image(): ?array
{
    $f = gallery_items('finished', true, 1);
    if ($f) {
        return $f[0];
    }
    $any = gallery_items('finished', false, 1);
    return $any[0] ?? null;
}

/** Favicon source from the latest branding upload (the PNG thumb), or null. */
function favicon_image(): ?array
{
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
    $items = gallery_items('finished', false, 4);
    if (count($items) >= 2) {
        return $items[1];
    }
    return null;
}
