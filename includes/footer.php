<?php
/**
 * Project: Qblockx
 * Include: footer.php — dark navy rounded panel footer (DeFiChain pattern)
 */
$currentYear = date('Y');
?>

<!-- ── Footer ──────────────────────────────────────────────────── -->
<footer aria-label="Site footer">

  <div class="footer-wrap">
    <div class="footer-panel">

      <div class="footer-grid">

        <!-- Brand + Newsletter -->
        <div>
          <a href="/" class="footer-logo" aria-label="Qblockx home">
            <img src="/assets/images/logo/logowhite.png" alt="Qblockx" style="height:28px; width:auto;">
            <span class="footer-logo-text">Qblockx</span>
          </a>
          <p class="footer-tagline">
            Air-gapped cold wallet storage — your private keys kept completely offline and out of reach.
          </p>
          <form class="footer-newsletter" action="#" method="post" onsubmit="return false;"
                aria-label="Newsletter signup">
            <input class="footer-newsletter-input" type="email" name="email"
                   placeholder="Enter your email" autocomplete="email">
            <button class="footer-newsletter-btn" type="submit">Subscribe</button>
          </form>
        </div>

        <!-- Product -->
        <div>
          <h4 class="footer-col-title">Product</h4>
          <ul class="footer-col-links">
            <li><a href="/#how-it-works">How It Works</a></li>
            <li><a href="/#plans">Storage Plans</a></li>
            <li><a href="/#assets">Supported Assets</a></li>
            <li><a href="/register">Get Started</a></li>
          </ul>
        </div>

        <!-- Company -->
        <div>
          <h4 class="footer-col-title">Company</h4>
          <ul class="footer-col-links">
            <li><a href="/about">About Us</a></li>
            <li><a href="/security">Security</a></li>
            <li><a href="/contact">Contact</a></li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <h4 class="footer-col-title">Legal</h4>
          <ul class="footer-col-links">
            <li><a href="/help">Help Centre</a></li>
            <li><a href="/privacy">Privacy Policy</a></li>
            <li><a href="/terms">Terms of Service</a></li>
            <li><a href="/risk">Security Disclosure</a></li>
          </ul>
        </div>

      </div><!-- /footer-grid -->

      <!-- Bottom bar -->
      <div class="footer-bottom">
        <span>&copy; <?= $currentYear ?> Qblockx. All rights reserved.</span>
        <span class="footer-status">
          <span class="status-dot" aria-hidden="true"></span>
          All Systems Operational
        </span>
      </div>

    </div><!-- /footer-panel -->
  </div><!-- /footer-wrap -->

</footer>

<!-- ── Security Notice ─────────────────────────────────────────── -->
<section class="disclosure" aria-label="Security notice">
  <div class="container">
    <p class="disclosure-text">
      <strong>Important Notice:</strong> Qblockx is a self-custody cold storage platform. You alone control your private keys — Qblockx cannot access, move, or recover funds on your behalf. Safeguard your recovery shares and credentials; loss of access may result in permanent loss of funds.
    </p>
    <p class="disclosure-text">
      By using Qblockx, you agree to our <a href="/terms">Terms of Service</a> and acknowledge you have read and understood the <a href="/risk">Security Disclosure</a>. Cryptocurrency holdings carry inherent risk and their value may fluctuate.
    </p>
  </div>
</section>

<!-- ── Page Loader ──────────────────────────────────────────────── -->
<div id="pageLoader" class="page-loader" aria-hidden="true">
  <img src="/assets/images/logo/logoblue.png" class="loader-logo" alt="Loading Qblockx">
</div>

<!-- ── Scripts ─────────────────────────────────────────────────── -->
<!-- main.js is loaded via head.php -->

<!-- Smartsupp Live Chat -->
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = 'a43df6084f576c1db44b8d3b000f1f43c5f83dc5';
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
</script>
<noscript>Powered by <a href="https://www.smartsupp.com" target="_blank">Smartsupp</a></noscript>
