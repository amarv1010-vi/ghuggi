<?php
/**
 * Media CRUD endpoint. Handles caption edits, reordering, visibility toggle,
 * delete and the featured toggle (finished only, capped at 3).
 * All actions require login and a valid CSRF token.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/media-lib.php';

require_post_auth();

$action = (string) ($_POST['action'] ?? '');

try {
    $pdo = db();
} catch (Throwable $e) {
    json_fail('Database unavailable.', 500);
}

/** Load a media row or fail. */
function media_row(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_fail('Item not found.', 404);
    }
    return $row;
}

switch ($action) {

    case 'caption':
        $id = (int) ($_POST['id'] ?? 0);
        $caption = trim((string) ($_POST['caption'] ?? ''));
        if (mb_strlen($caption) > 200) {
            $caption = mb_substr($caption, 0, 200);
        }
        media_row($pdo, $id);
        $pdo->prepare('UPDATE media SET caption = :c WHERE id = :id')
            ->execute([':c' => $caption, ':id' => $id]);
        json_ok(['caption' => $caption]);

    case 'toggle_active':
        $id = (int) ($_POST['id'] ?? 0);
        $row = media_row($pdo, $id);
        $new = (int) $row['is_active'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE media SET is_active = :a WHERE id = :id')
            ->execute([':a' => $new, ':id' => $id]);
        json_ok(['is_active' => $new]);

    case 'toggle_featured':
        $id = (int) ($_POST['id'] ?? 0);
        $row = media_row($pdo, $id);
        if ($row['category'] !== 'finished') {
            json_fail('Only finished projects can be featured.', 422);
        }
        $new = (int) $row['is_featured'] === 1 ? 0 : 1;
        if ($new === 1) {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM media WHERE category = "finished" AND is_featured = 1')->fetchColumn();
            if ($count >= 3) {
                json_fail('You can feature up to 3 projects. Unfeature one first.', 422);
            }
        }
        $pdo->prepare('UPDATE media SET is_featured = :f WHERE id = :id')
            ->execute([':f' => $new, ':id' => $id]);
        json_ok(['is_featured' => $new]);

    case 'reorder':
        $ids = $_POST['order'] ?? [];
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }
        if (!is_array($ids) || !$ids) {
            json_fail('No order supplied.', 422);
        }
        $category = strtolower((string) ($_POST['category'] ?? ''));
        if (!in_array($category, jsd_categories(), true)) {
            json_fail('Invalid category.', 422);
        }
        $pdo->beginTransaction();
        $upd = $pdo->prepare('UPDATE media SET sort_order = :s WHERE id = :id AND category = :c');
        $i = 1;
        foreach ($ids as $id) {
            $upd->execute([':s' => $i++, ':id' => (int) $id, ':c' => $category]);
        }
        $pdo->commit();
        json_ok(['count' => count($ids)]);

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        $row = media_row($pdo, $id);
        // Remove files from disk, then the row.
        $main = uploads_root() . '/' . $row['category'] . '/' . $row['filename'];
        $thumb = uploads_root() . '/thumbs/' . $row['thumb'];
        if ($row['filename'] !== '' && is_file($main)) {
            @unlink($main);
        }
        if (!empty($row['thumb']) && is_file($thumb)) {
            @unlink($thumb);
        }
        $pdo->prepare('DELETE FROM media WHERE id = :id')->execute([':id' => $id]);
        json_ok(['deleted' => $id]);

    default:
        json_fail('Unknown action.', 400);
}
