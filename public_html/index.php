<?php require_once __DIR__ . '/includes/bootstrap.php'; ?>
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

  <!-- Sections are built in Phase 3 (Hero, Trust, Services, About, Process,
       Areas), Phase 4 (Featured, Photos) and Phase 5 (Contact). -->
  <section class="section" aria-label="Introduction">
    <div class="container">
      <span class="eyebrow">Brisbane &middot; South East Queensland</span>
      <h1 class="text-grad">Luxury Built in Australia</h1>
      <p class="lead mt-2">Founder-led home building, fully managed in-house from site assessment to handover.</p>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script src="assets/js/main.js" defer></script>
</body>
</html>
