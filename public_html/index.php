<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/api/gallery.php';

$featured = featured_projects();
$ongoing  = ongoing_photos();
?>
<!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>JSD Construction | Luxury Home Builder in Brisbane &amp; South East QLD</title>
  <meta name="description" content="JSD Construction Pty Ltd builds luxury high-set, low-set and split-level homes plus low-rise commercial across Brisbane, Gold Coast, Ipswich and the Sunshine Coast. Founder-led and fully in-house.">
  <link rel="canonical" href="<?= e(cfg('SITE_URL')) ?>/">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="JSD Construction | Luxury Built in Australia">
  <meta property="og:description" content="Founder-led luxury home building across South East Queensland.">
  <meta property="og:url" content="<?= e(cfg('SITE_URL')) ?>/">
  <meta property="og:locale" content="en_AU">

  <!-- Preload self-hosted fonts -->
  <link rel="preload" href="assets/fonts/playfair-latin-var.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/inter-latin-var.woff2" as="font" type="font/woff2" crossorigin>

  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body id="top">

<?php require __DIR__ . '/includes/header.php'; ?>

<main id="main">

  <!-- ===================== Hero ===================== -->
  <section class="hero" aria-label="Introduction">
    <?php $heroImg = function_exists('hero_image') ? hero_image() : null; ?>
    <?php if ($heroImg): ?>
      <div class="hero__bg"><img src="<?= e($heroImg['src']) ?>" alt="<?= e($heroImg['alt'] ?: 'JSD Construction home build') ?>" fetchpriority="high"></div>
    <?php else: ?>
      <div class="hero__placeholder" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="hero__scrim" aria-hidden="true"></div>
    <div class="container">
      <div class="hero__inner">
        <span class="eyebrow">Brisbane &middot; South East Queensland</span>
        <h1 class="text-grad">Luxury Built in Australia</h1>
        <p class="lead">Founder-led home building, fully managed in-house from the first site assessment to the day we hand you the keys.</p>
        <div class="cta-row">
          <a class="btn btn--gold btn--lg" href="#contact">Get a Quote</a>
          <a class="btn btn--ghost btn--lg" href="#featured">See Our Work</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== Trust strip ===================== -->
  <section class="trust" aria-label="Why choose JSD Construction">
    <div class="container">
      <div class="trust__grid">
        <div class="trust__item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
          <div><strong><?= e(fact_qbcc()) ?></strong><span>Licensed Queensland builder</span></div>
        </div>
        <div class="trust__item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
          <div><strong>Founder-led</strong><span>Built and managed by Jagdeep Singh</span></div>
        </div>
        <div class="trust__item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 21s-7-5.2-7-11a7 7 0 0114 0c0 5.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
          <div><strong>South East QLD</strong><span>Brisbane, Gold Coast, Ipswich &amp; Sunshine Coast</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== Services ===================== -->
  <section class="section" id="services" aria-labelledby="services-h">
    <div class="container">
      <div class="section__head">
        <span class="eyebrow">What we build</span>
        <h2 id="services-h">Homes built end-to-end, in-house</h2>
        <p>From high-set family homes to low-rise commercial, we manage every stage ourselves. No subcontracted chaos, just premium timber framing, energy-efficient design and finishing built for the Queensland climate.</p>
      </div>
      <div class="cards cards--4">
        <article class="card">
          <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></div>
          <h3>High-set Houses</h3>
          <p>Elevated designs that capture breezes and views, ideal for sloping Queensland blocks and flood-aware builds.</p>
        </article>
        <article class="card">
          <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 12l9-6 9 6"/><path d="M5 11v9h14v-9"/></svg></div>
          <h3>Low-set Houses</h3>
          <p>Single-level living with seamless indoor-outdoor flow, accessible layouts and a refined modern finish.</p>
        </article>
        <article class="card">
          <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 20h18"/><path d="M5 20v-7l5-4v11"/><path d="M14 20V6l5 3v11"/></svg></div>
          <h3>Split-level Homes</h3>
          <p>Architectural multi-level homes that work with your land, turning a tricky gradient into a striking feature.</p>
        </article>
        <article class="card">
          <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M7 8h2M7 12h2M7 16h2M15 8h2M15 12h2M15 16h2"/></svg></div>
          <h3>Low-rise Commercial</h3>
          <p>Functional, durable commercial spaces delivered with the same in-house discipline as our homes.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ===================== Featured (wired in Phase 4) ===================== -->
  <section class="section section--alt" id="featured" aria-labelledby="featured-h">
    <div class="container">
      <div class="section__head">
        <span class="eyebrow">Featured projects</span>
        <h2 id="featured-h">A selection of finished homes</h2>
        <p>Our proudest completed builds across South East Queensland.</p>
      </div>
<?php if ($featured): ?>
        <div class="featured-grid">
          <?php foreach ($featured as $item): ?>
            <figure class="featured-card">
              <img src="<?= e($item['src']) ?>" alt="<?= e($item['alt']) ?>" loading="lazy">
              <?php if ($item['caption'] !== ''): ?>
                <figcaption><?= e($item['caption']) ?></figcaption>
              <?php endif; ?>
            </figure>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state" data-featured-empty>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 17l-5-5L5 21"/></svg>
          <strong>Featured projects coming soon</strong>
          <p>Our finished homes will appear here once the gallery is published.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===================== Photos carousel (wired in Phase 4) ===================== -->
  <section class="section" id="photos" aria-labelledby="photos-h">
    <div class="container">
      <div class="section__head">
        <span class="eyebrow">On site now</span>
        <h2 id="photos-h">Ongoing builds</h2>
        <p>A look at the homes we are building right now. Drag, swipe or use the arrow keys to browse.</p>
      </div>
<?php if ($ongoing): ?>
        <div class="carousel" data-carousel>
          <button class="carousel__btn carousel__btn--prev" type="button" aria-label="Previous photos" data-carousel-prev hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <ul class="carousel__track" data-carousel-track tabindex="0" role="list" aria-label="Ongoing build photos, scrollable">
            <?php foreach ($ongoing as $item): ?>
              <li class="carousel__item" role="listitem">
                <figure>
                  <img src="<?= e($item['src']) ?>" alt="<?= e($item['alt']) ?>" loading="lazy" draggable="false">
                  <?php if ($item['caption'] !== ''): ?>
                    <figcaption><?= e($item['caption']) ?></figcaption>
                  <?php endif; ?>
                </figure>
              </li>
            <?php endforeach; ?>
          </ul>
          <button class="carousel__btn carousel__btn--next" type="button" aria-label="Next photos" data-carousel-next hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
          </button>
        </div>
      <?php else: ?>
        <div class="empty-state" data-photos-empty>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 17l-5-5L5 21"/></svg>
          <strong>Build photos coming soon</strong>
          <p>Current site progress will appear here as our team uploads it.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===================== About ===================== -->
  <section class="section section--alt" id="about" aria-labelledby="about-h">
    <div class="container">
      <div class="about__grid">
        <div>
          <span class="eyebrow">Our story</span>
          <h2 id="about-h">Founder-led, fully in-house</h2>
          <div class="about">
            <p>JSD Construction was founded by Jagdeep Singh on a simple belief. A home is the biggest thing most families ever build so it deserves a builder who stays hands-on from start to finish.</p>
            <p>We keep the whole journey in-house. Design, approvals, framing, carpentry, joinery and finishing all run through one accountable team. That means tighter quality control, clearer communication and a result that feels crafted, not assembled.</p>
            <p>Every home is designed for the Queensland climate with energy-efficient principles, premium timber framing and finishes built to last.</p>
          </div>
          <p class="signature">Jagdeep Singh, Founder</p>
        </div>
        <div class="about__media">
          <?php $aboutImg = function_exists('about_image') ? about_image() : null; ?>
          <?php if ($aboutImg): ?>
            <img src="<?= e($aboutImg['src']) ?>" alt="<?= e($aboutImg['alt'] ?: 'JSD Construction team on site') ?>" loading="lazy">
          <?php else: ?>
            <span class="badge">Project imagery coming soon</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== Process ===================== -->
  <section class="section" id="process" aria-labelledby="process-h">
    <div class="container">
      <div class="section__head">
        <span class="eyebrow">How we build</span>
        <h2 id="process-h">From first site visit to handover</h2>
        <p>A clear six-stage process so you always know what comes next.</p>
      </div>
      <div class="process__list">
        <article class="process__step"><h3>Site Assessment</h3><p>We walk your block, study orientation, slope and soil, and shape a design that suits the land and your budget.</p></article>
        <article class="process__step"><h3>Approvals</h3><p>We prepare and manage the plans, certification and council approvals so the paperwork never stalls your build.</p></article>
        <article class="process__step"><h3>Build</h3><p>Foundations, premium timber framing and lock-up, all run by our own in-house crew to a tight schedule.</p></article>
        <article class="process__step"><h3>Carpentry &amp; Joinery</h3><p>Our carpenters handle the detail work, from structural carpentry to custom joinery and cabinetry.</p></article>
        <article class="process__step"><h3>Finishing</h3><p>Paint, fixtures, tiling and final trades brought together with a careful eye for a premium finish.</p></article>
        <article class="process__step"><h3>Handover</h3><p>A full walkthrough, quality check and clean handover so your new home is ready to live in.</p></article>
      </div>
    </div>
  </section>

  <!-- ===================== Service areas ===================== -->
  <section class="section section--alt" id="areas" aria-labelledby="areas-h">
    <div class="container">
      <div class="section__head">
        <span class="eyebrow">Where we build</span>
        <h2 id="areas-h">Serving South East Queensland</h2>
        <p>Based in Brisbane and building across the wider region.</p>
      </div>
      <div class="areas__grid">
        <div class="area">Brisbane<span>and outskirts</span></div>
        <div class="area">Gold Coast</div>
        <div class="area">Ipswich</div>
        <div class="area">Sunshine Coast</div>
        <div class="area">Surrounds<span>by arrangement</span></div>
      </div>
    </div>
  </section>

  <!-- ===================== Contact (built in Phase 5) ===================== -->
  <section class="section" id="contact" aria-labelledby="contact-h">
    <div class="container">
      <div class="section__head">
        <span class="eyebrow">Get in touch</span>
        <h2 id="contact-h">Start your build</h2>
        <p>Tell us about your project and the right team member will be in touch.</p>
      </div>
      <p class="lead">Contact form coming in the next stage. In the meantime call <a href="tel:+61424475767">+61 424 475 767</a> or message us on <a href="https://wa.me/61424475767" target="_blank" rel="noopener">WhatsApp</a>.</p>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script src="assets/js/main.js" defer></script>
<script src="assets/js/carousel.js" defer></script>
</body>
</html>
