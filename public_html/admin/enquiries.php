<?php
/**
 * Enquiries dashboard. Lists every contact-form submission and offers a CSV
 * download (opens in Excel / Google Sheets).
 */

require_once __DIR__ . '/layout.php';
require_login();

try {
    $rows = db()->query(
        'SELECT id, created_at, name, email, phone, department, message
         FROM enquiries ORDER BY created_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}

admin_head('Enquiries');
?>
<header class="admin-bar">
  <div class="admin-bar__brand">JSD <b>CONSTRUCTION</b><span>Admin</span></div>
  <div class="admin-bar__right">
    <a class="admin-bar__view" href="index.php">Photos</a>
    <span class="admin-bar__user">Hi, <?= e($_SESSION['admin_username'] ?? 'admin') ?></span>
    <a class="btn btn--ghost btn--sm" href="logout.php">Log out</a>
  </div>
</header>

<main class="admin-main">
  <div class="enq-head">
    <div>
      <h1 style="font-size:var(--step-2)">Enquiries</h1>
      <p class="admin-panel__hint"><?= count($rows) ?> total. Newest first.</p>
    </div>
    <a class="btn btn--gold" href="export.php">Download spreadsheet (CSV)</a>
  </div>

  <?php if (!$rows): ?>
    <p class="media-empty">No enquiries yet. Submissions from the website contact form will appear here.</p>
  <?php else: ?>
    <div class="enq-table-wrap">
      <table class="enq-table">
        <thead>
          <tr><th>Date</th><th>Name</th><th>Email</th><th>Phone</th><th>Dept</th><th>Message</th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
              <td><?= e($r['name']) ?></td>
              <td><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></td>
              <td><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $r['phone'])) ?>"><?= e($r['phone']) ?></a></td>
              <td><?= e($r['department']) ?></td>
              <td class="enq-msg"><?= nl2br(e($r['message'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php admin_foot(); ?>
