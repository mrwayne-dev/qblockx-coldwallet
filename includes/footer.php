<?php
/**
 * Project: Qblockx
 * Include: footer.php — dark navy rounded panel footer (DeFiChain pattern)
 *
 * Every href here must resolve to a file under pages/public/. Before adding a
 * link, check it: the catch-all in .htaccess routes an unknown URL back to the
 * homepage with HTTP 200, so a dead link looks exactly like a working one and
 * only shows up as landing-page copy in a "Terms" window.
 */
/*
 * Absolute path, deliberately: index.php chdir()s into pages/public before
 * requiring its pages, and a path relative to the CWD would resolve against
 * there instead of this file.
 */
require_once __DIR__ . '/company-blocks.php';
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
            <li><a href="<?= htmlspecialchars(qbx_doc_url('how-it-works')) ?>">How It Works</a></li>
            <li><a href="<?= htmlspecialchars(qbx_doc_url('custody')) ?>">Custody &amp; Security</a></li>
            <li><a href="<?= htmlspecialchars(qbx_doc_url('withdrawals')) ?>">Withdrawals</a></li>
            <li><a href="/mining">Mining</a></li>
            <li><a href="/register">Get Started</a></li>
          </ul>
        </div>

        <!-- Company -->
        <div>
          <h4 class="footer-col-title">Company</h4>
          <ul class="footer-col-links">
            <li><a href="/company">Who Operates Qblockx</a></li>
            <li><a href="/#faq">Common Questions</a></li>
            <li><a href="/contact">Contact</a></li>
          </ul>
        </div>

        <!-- Compliance -->
        <div>
          <h4 class="footer-col-title">Compliance</h4>
          <ul class="footer-col-links">
            <li><a href="<?= htmlspecialchars(qbx_doc_url('compliance')) ?>">Compliance</a></li>
            <li><a href="<?= htmlspecialchars(qbx_doc_url('custody')) ?>">Custody &amp; Security</a></li>
            <li><a href="<?= htmlspecialchars(qbx_doc_url('how-it-works')) ?>">How It Works</a></li>
            <li><a href="<?= htmlspecialchars(qbx_doc_url('withdrawals')) ?>">Withdrawals &amp; Liquidity</a></li>
            <li><a href="/company">Company Details</a></li>
            <li><a href="/terms">Terms of Service</a></li>
            <li><a href="/privacy">Privacy Policy</a></li>
            <li><a href="/risk">Risk Disclosure</a></li>
          </ul>
        </div>

      </div><!-- /footer-grid -->

      <!-- Entity block: the "who are you" answer, present on every page.
           Lines appear only once the fact is known (config/company.php). -->
      <dl class="footer-entity">
        <?php if (company_has('legal_name')): ?>
          <div class="footer-entity-row">
            <dt>Registered</dt>
            <dd>
              <?= htmlspecialchars(company_get('legal_name')) ?><?php if (company_has('registration_number')): ?>
              (<?= htmlspecialchars(company_get('registration_number')) ?>)<?php endif; ?><?php if (company_has('jurisdiction')): ?>
              , <?= htmlspecialchars(company_get('jurisdiction')) ?><?php endif; ?>
            </dd>
          </div>
        <?php endif; ?>
        <?php if (company_has('registered_address')): ?>
          <div class="footer-entity-row">
            <dt>Address</dt>
            <dd><?= htmlspecialchars(company_get('registered_address')) ?></dd>
          </div>
        <?php endif; ?>
        <div class="footer-entity-row">
          <dt>Contact</dt>
          <dd>
            <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a><?php if (company_has('compliance_email')): ?>
            · Compliance: <a href="mailto:<?= htmlspecialchars(company_get('compliance_email')) ?>"><?= htmlspecialchars(company_get('compliance_email')) ?></a><?php endif; ?>
          </dd>
        </div>
      </dl>

      <!-- Bottom bar -->
      <div class="footer-bottom">
        <span>&copy; <?= $currentYear ?> <?= htmlspecialchars(company_has('legal_name') ? company_get('legal_name') : company_get('trading_as')) ?>. All rights reserved.</span>
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
      <strong>Important Notice:</strong> Assets held in a Qblockx storage plan are self-custodied:
      outbound movements require your authorisation, and Qblockx cannot move them without it.
      Safeguard your recovery shares and credentials — if you lose them there is no support
      process that can recover those assets for you, and permanent loss is possible. Value you
      elect to deploy in mining, investments or card-programme products is a separate
      arrangement with Qblockx as counterparty; the difference is set out in
      <a href="/how-it-works#tiers">How It Works</a> and in
      <a href="/custody">Custody &amp; Security</a>.
    </p>
    <p class="disclosure-text">
      By using Qblockx you agree to our <a href="/terms">Terms of Service</a> and confirm you have
      read the <a href="/risk">Risk Disclosure</a>. Cryptocurrency holdings carry inherent risk and
      their value may fluctuate. Digital assets held on this platform are not deposits, are not
      government guaranteed, and are not covered by a depositor-protection scheme. Qblockx is
      operated by <?= company_has('legal_name') ? htmlspecialchars(company_get('legal_name')) : 'the entity named on our <a href="/company">Company page</a>' ?>.
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
