<?php
/**
 * Upload endpoint. Accepts multi-file drag-drop uploads for a category,
 * runs each file through the hardened pipeline and inserts a media row.
 * Branding is a single-slot logo: a new upload deactivates previous branding.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/media-lib.php';

require_post_auth();

$category = strtolower(trim((string) ($_POST['category'] ?? '')));
if (!in_array($category, jsd_categories(), true)) {
    json_fail('Invalid category.');
}

if (empty($_FILES['files'])) {
    json_fail('No files received.');
}

// Normalise the $_FILES structure to a list of single files.
$files = [];
$f = $_FILES['files'];
if (is_array($f['name'])) {
    foreach ($f['name'] as $i => $name) {
        $files[] = [
            'name' => $name,
            'type' => $f['type'][$i] ?? '',
            'tmp_name' => $f['tmp_name'][$i] ?? '',
            'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $f['size'][$i] ?? 0,
        ];
    }
} else {
    $files[] = $f;
}

$added = [];
$errors = [];

try {
    $pdo = db();
} catch (Throwable $e) {
    json_fail('Database unavailable.', 500);
}

foreach ($files as $file) {
    try {
        $res = process_upload($file, $category);

        // Next sort order for this category.
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM media WHERE category = :c');
        $stmt->execute([':c' => $category]);
        $sort = (int) $stmt->fetchColumn();

        $ins = $pdo->prepare(
            'INSERT INTO media (category, filename, thumb, caption, is_featured, is_active, sort_order)
             VALUES (:c, :f, :t, :cap, 0, 1, :s)'
        );
        $ins->execute([
            ':c' => $category, ':f' => $res['filename'], ':t' => $res['thumb'],
            ':cap' => '', ':s' => $sort,
        ]);
        $id = (int) $pdo->lastInsertId();

        // Branding single-slot: hide all other branding rows.
        if ($category === 'branding') {
            $pdo->prepare('UPDATE media SET is_active = 0 WHERE category = "branding" AND id <> :id')
                ->execute([':id' => $id]);
        }

        $added[] = ['id' => $id, 'name' => $file['name']];
    } catch (Throwable $e) {
        $errors[] = ($file['name'] ?? 'file') . ': ' . $e->getMessage();
    }
}

if (!$added && $errors) {
    json_fail(implode(' ', $errors), 422);
}

json_ok(['added' => $added, 'errors' => $errors]);
