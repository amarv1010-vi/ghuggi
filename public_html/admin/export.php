<?php
/**
 * Export enquiries as a CSV spreadsheet (opens in Excel / Google Sheets).
 * Login required. Read-only.
 */

require_once __DIR__ . '/auth.php';
require_login();

try {
    $rows = db()->query(
        'SELECT id, created_at, name, email, phone, department, message, source_ip
         FROM enquiries ORDER BY created_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Could not read enquiries.');
}

$filename = 'jsd-enquiries-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
// BOM so Excel reads UTF-8 correctly.
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['ID', 'Date', 'Name', 'Email', 'Phone', 'Department', 'Message', 'IP']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'], $r['created_at'], $r['name'], $r['email'],
        $r['phone'], $r['department'], $r['message'], $r['source_ip'],
    ]);
}
fclose($out);
exit;
