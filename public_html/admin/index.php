<?php
/**
 * Admin dashboard. Folder tabs for Ongoing, Finished and Branding, each backed
 * by a server directory and media rows. Upload, caption, reorder, visibility,
 * delete and the featured toggle are wired through admin.js to the JSON
 * endpoints (upload.php, media-crud.php).
 */

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/media-lib.php';
require_once dirname(__DIR__) . '/api/gallery.php';

require_login();

/** All media for a category, active and hidden, in display order. */
function admin_media(string $category): array
{
    try {
        $stmt = db()->prepare(
            'SELECT * FROM media WHERE category = :c ORDER BY sort_order ASC, created_at DESC'
        );
        $stmt->execute([':c' => $category]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

$tabs = [
    'ongoing'  => ['label' => 'Ongoing', 'hint' => 'In-progress build photos. Feeds the Photos carousel.'],
    'finished' => ['label' => 'Finished', 'hint' => 'Completed projects. Feeds the gallery and Featured block (up to 3).'],
    'branding' => ['label' => 'Branding', 'hint' => 'Your logo. The most recent upload shows in the site header.'],
];

$featuredCount = 0;
try {
    $featuredCount = (int) db()->query('SELECT COUNT(*) FROM media WHERE category = "finished" AND is_featured = 1')->fetchColumn();
} catch (Throwable $e) {
}

admin_head('Dashboard', true);
?>
<header class="admin-bar">
  <div class="admin-bar__brand">JSD <b>CONSTRUCTION</b><span>Admin</span></div>
  <div class="admin-bar__right">
    <a class="admin-bar__view" href="../index.php" target="_blank" rel="noopener">View site</a>
    <span class="admin-bar__user">Hi, <?= e($_SESSION['admin_username'] ?? 'admin') ?></span>
    <a class="btn btn--ghost btn--sm" href="logout.php">Log out</a>
  </div>
</header>

<main class="admin-main" data-featured-count="<?= (int) $featuredCount ?>">
  <div class="admin-tabs" role="tablist" aria-label="Media folders">
    <?php $first = true; foreach ($tabs as $key => $t): ?>
      <button class="admin-tab<?= $first ? ' is-active' : '' ?>" role="tab"
              id="tab-<?= e($key) ?>" aria-controls="panel-<?= e($key) ?>"
              aria-selected="<?= $first ? 'true' : 'false' ?>" data-tab="<?= e($key) ?>">
        <?= e($t['label']) ?>
      </button>
    <?php $first = false; endforeach; ?>
  </div>

  <?php $first = true; foreach ($tabs as $key => $t): $items = admin_media($key); ?>
    <section class="admin-panel<?= $first ? ' is-active' : '' ?>" id="panel-<?= e($key) ?>"
             role="tabpanel" aria-labelledby="tab-<?= e($key) ?>" data-category="<?= e($key) ?>" <?= $first ? '' : 'hidden' ?>>
      <p class="admin-panel__hint"><?= e($t['hint']) ?></p>

      <form class="dropzone" data-dropzone data-category="<?= e($key) ?>">
        <input type="file" class="dropzone__input" accept="image/jpeg,image/png,image/webp" multiple data-file-input>
        <div class="dropzone__inner">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
          <strong>Drag photos here or click to browse</strong>
          <span>JPG, PNG or WebP. Up to <?= (int) round(((int) cfg('UPLOAD_MAX_BYTES')) / 1048576) ?>MB each.</span>
        </div>
      </form>
      <div class="upload-feedback" data-upload-feedback hidden></div>

      <div class="media-grid" data-media-grid data-category="<?= e($key) ?>">
        <?php if (!$items): ?>
          <p class="media-empty" data-empty>No images yet. Upload your first <?= e(strtolower($t['label'])) ?> photo above.</p>
        <?php endif; ?>
        <?php foreach ($items as $m):
            $thumb = !empty($m['thumb']) ? thumb_src($m['thumb']) : media_src($m['category'], $m['filename']);
        ?>
          <article class="media-card<?= (int) $m['is_active'] === 0 ? ' is-hidden' : '' ?>" data-id="<?= (int) $m['id'] ?>" draggable="true">
            <div class="media-card__handle" title="Drag to reorder" aria-hidden="true">⋮⋮</div>
            <div class="media-card__thumb">
              <img src="<?= e($thumb) ?>" alt="<?= e($m['caption'] ?: 'Project image') ?>" loading="lazy" draggable="false">
              <?php if ($key === 'finished'): ?>
                <button type="button" class="media-card__feature<?= (int) $m['is_featured'] === 1 ? ' is-on' : '' ?>"
                        data-action="feature" title="Feature on homepage" aria-pressed="<?= (int) $m['is_featured'] === 1 ? 'true' : 'false' ?>">★</button>
              <?php endif; ?>
            </div>
            <input type="text" class="media-card__caption" value="<?= e($m['caption']) ?>" placeholder="Add a caption" maxlength="200" data-action="caption">
            <div class="media-card__actions">
              <button type="button" class="chip" data-action="toggle" aria-pressed="<?= (int) $m['is_active'] === 1 ? 'true' : 'false' ?>">
                <?= (int) $m['is_active'] === 1 ? 'Visible' : 'Hidden' ?>
              </button>
              <button type="button" class="chip chip--danger" data-action="delete">Delete</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php $first = false; endforeach; ?>
</main>
<?php admin_foot(true); ?>
