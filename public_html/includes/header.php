<?php
require_once __DIR__ . '/bootstrap.php';

/*
 * Header. Renders the styled text wordmark until the client uploads a logo in
 * the admin Branding tab. The logo wiring is added in Phase 4 via gallery.php;
 * if that helper exists we use it, otherwise we fall back to the wordmark so
 * the site never looks broken.
 */
$logo = function_exists('branding_logo') ? branding_logo() : null;

$navLinks = [
    '#services' => 'Services',
    '#featured' => 'Featured',
    '#photos'   => 'Photos',
    '#about'    => 'About',
    '#process'  => 'Process',
    '#areas'    => 'Service Areas',
];
?>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="#top" aria-label="JSD Construction home">
      <?php if ($logo): ?>
        <img class="brand__logo" src="<?= e($logo['src']) ?>" alt="<?= e($logo['alt'] ?: 'JSD Construction') ?>" width="160" height="44">
      <?php else: ?>
        <span class="brand__text">
          <span class="brand__wordmark">JSD <b>CONSTRUCTION</b></span>
          <span class="brand__tag">Luxury Built in Australia</span>
        </span>
      <?php endif; ?>
    </a>

    <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Open menu">
      <span></span>
    </button>

    <nav class="nav" id="primary-nav" aria-label="Primary">
      <ul class="nav__list">
        <?php foreach ($navLinks as $href => $label): ?>
          <li><a class="nav__link" href="<?= e($href) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn--gold" href="#contact">Get a Quote</a>
    </nav>
  </div>
</header>
