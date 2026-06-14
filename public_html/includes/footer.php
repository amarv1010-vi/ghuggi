<?php require_once __DIR__ . '/bootstrap.php'; ?>
<footer class="site-footer" id="footer">
  <div class="container">
    <div class="footer-grid footer">
      <div class="footer__brand">
        <span class="brand__wordmark">JSD <b>CONSTRUCTION</b></span>
        <p>End-to-end home building across South East Queensland. Founder-led, fully in-house, built for the QLD climate.</p>
        <p class="footer__licence"><?= e(fact_qbcc()) ?></p>
      </div>

      <div>
        <h4>Contact</h4>
        <ul>
          <li><a href="tel:+61424475767">+61 424 475 767</a></li>
          <li><a href="mailto:contact@jsdconstruction.com.au">contact@jsdconstruction.com.au</a></li>
          <li><a href="https://wa.me/61424475767" target="_blank" rel="noopener">WhatsApp us</a></li>
        </ul>
      </div>

      <div>
        <h4>Service Areas</h4>
        <ul>
          <li>Brisbane</li>
          <li>Gold Coast</li>
          <li>Ipswich</li>
          <li>Sunshine Coast</li>
          <li>Brisbane outskirts</li>
        </ul>
      </div>
    </div>

    <div class="footer__legal">
      <span>&copy; <?= date('Y') ?> JSD Construction Pty Ltd. Brisbane, Australia.</span>
      <span><?= e(fact_qbcc()) ?> &nbsp;|&nbsp; <?= e(fact_abn()) ?></span>
    </div>
  </div>
</footer>
