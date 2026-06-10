<?php
/**
 * Project: Qblockx
 * Page: Contact — public support / enquiry form.
 * Posts to /api/utilities/contact.php which emails support and redirects back
 * with ?success=1 or ?error=<reason>.
 */
$pageTitle       = 'Contact';
$pageDescription = 'Get in touch with the Qblockx team — account help, payments, security questions, or general enquiries. We typically reply within a few hours.';
$pageKeywords    = 'Qblockx contact, crypto support, cold wallet help, customer support';
require_once '../../includes/head.php';
$navTheme = 'light';   // light page → dark-text nav (white-on-white otherwise)
require_once '../../includes/header.php';

// Flash message from the form handler redirect
$flashType = '';
$flashMsg  = '';
if (isset($_GET['success'])) {
    $flashType = 'ok';
    $flashMsg  = "Thanks — your message is on its way. Our team will get back to you shortly.";
} elseif (isset($_GET['error'])) {
    $flashType = 'err';
    $errMap = [
        'missing_fields' => 'Please add your name, a valid email, and a message.',
        'send_failed'    => 'Something went wrong sending your message. Please try again in a moment.',
    ];
    $flashMsg = $errMap[$_GET['error']] ?? 'Something went wrong. Please try again.';
}
?>

<main>

  <section class="contact-section">
    <div class="container">

      <!-- Intro -->
      <div class="contact-intro" data-appear>
        <span class="section-label">Contact</span>
        <h1 class="section-title contact-title">Get in touch</h1>
        <p class="section-subtitle contact-sub">
          Questions about your account, a payment, or keeping your crypto secure? Send us a note
          and a real person will reply — usually within a few hours.
        </p>
      </div>

      <div class="contact-grid">

        <!-- Left: info -->
        <aside class="contact-info" data-appear>

          <div class="contact-info-card">
            <span class="contact-info-ic"><i class="ph ph-envelope-simple" aria-hidden="true"></i></span>
            <div>
              <h3>Email us</h3>
              <p>Reach support directly any time.</p>
              <a href="mailto:support@qblockx.com" class="contact-info-link">support@qblockx.com</a>
            </div>
          </div>

          <div class="contact-info-card">
            <span class="contact-info-ic"><i class="ph ph-chats-circle" aria-hidden="true"></i></span>
            <div>
              <h3>Live chat</h3>
              <p>Chat with us in real time — the widget sits in the bottom-right of every page.</p>
              <button type="button" class="contact-info-link contact-chat-btn">Open live chat</button>
            </div>
          </div>

          <div class="contact-info-card">
            <span class="contact-info-ic"><i class="ph ph-clock-countdown" aria-hidden="true"></i></span>
            <div>
              <h3>Response time</h3>
              <p>We typically reply within a few hours, 7 days a week.</p>
            </div>
          </div>

          <div class="contact-info-note">
            <i class="ph ph-shield-check" aria-hidden="true"></i>
            <span>Qblockx will never ask for your recovery phrase or password. Never share them with anyone.</span>
          </div>

        </aside>

        <!-- Right: form -->
        <div class="contact-form-card" data-appear>

          <?php if ($flashMsg): ?>
            <div class="contact-flash contact-flash--<?= $flashType ?>" role="alert">
              <i class="ph <?= $flashType === 'ok' ? 'ph-check-circle' : 'ph-warning-circle' ?>" aria-hidden="true"></i>
              <span><?= htmlspecialchars($flashMsg) ?></span>
            </div>
          <?php endif; ?>

          <h2 class="contact-form-title">Send us a message</h2>

          <form action="/api/utilities/contact.php" method="post" novalidate>

            <div class="contact-form-row">
              <div class="form-group">
                <label for="first_name">First name</label>
                <div class="input-icon-wrap">
                  <i class="ph ph-user input-icon" aria-hidden="true"></i>
                  <input type="text" id="first_name" name="first_name" placeholder="Jane" autocomplete="given-name" required>
                </div>
              </div>
              <div class="form-group">
                <label for="last_name">Last name</label>
                <div class="input-icon-wrap">
                  <i class="ph ph-user input-icon" aria-hidden="true"></i>
                  <input type="text" id="last_name" name="last_name" placeholder="Doe" autocomplete="family-name" required>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="email">Email address</label>
              <div class="input-icon-wrap">
                <i class="ph ph-envelope input-icon" aria-hidden="true"></i>
                <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
              </div>
            </div>

            <div class="form-group">
              <label for="problem_type">What's this about?</label>
              <div class="input-icon-wrap">
                <i class="ph ph-tag input-icon" aria-hidden="true"></i>
                <select id="problem_type" name="problem_type" class="contact-select" required>
                  <option value="account-access">Account access issue</option>
                  <option value="payment-issue">Payment / transaction issue</option>
                  <option value="data-correction">Incorrect or missing information</option>
                  <option value="password-issue">Password issue</option>
                  <option value="security">Security concern</option>
                  <option value="other" selected>General enquiry</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="description">How can we help?</label>
              <textarea id="description" name="description" rows="6" class="contact-textarea"
                        placeholder="Tell us what's going on…" required></textarea>
            </div>

            <button type="submit" class="btn-primary full-width contact-submit">
              Send message <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
            </button>

          </form>

        </div>

      </div>
    </div>
  </section>

</main>

<!-- Smartsupp Live Chat (kept so the live-chat card still works without the footer) -->
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

<script>
  // Open the Smartsupp live chat from the info card
  document.querySelectorAll('.contact-chat-btn').forEach(function (b) {
    b.addEventListener('click', function () {
      if (window.smartsupp) { window.smartsupp('chat:open'); }
    });
  });
  // Clear ?success / ?error from the URL after showing the flash
  if (location.search.indexOf('success') !== -1 || location.search.indexOf('error') !== -1) {
    history.replaceState(null, '', location.pathname);
  }
</script>

</body>
</html>
