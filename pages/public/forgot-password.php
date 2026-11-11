<?php
/**
 * Project: qblockx
 * Page: Forgot Password
 */
$pageTitle = 'Forgot Password';
require_once '../../includes/head.php';
?>

<div class="auth-page">
  <div class="auth-split">

    <!-- ── Left brand panel ── -->
    <div class="auth-panel">
      <a href="/" class="auth-panel-logo" aria-label="Qblockx home">
        <img src="/assets/images/logo/logowhite.png" alt="">
        <span class="auth-panel-logo-text">Qblockx</span>
      </a>

      <div class="auth-panel-body">
        <h2 class="auth-panel-heading">We'll get you<br>back in.</h2>
        <p class="auth-panel-sub">
          Enter your registered email address and we'll send you a secure link to reset your password within minutes.
        </p>
      </div>
    </div>

    <!-- ── Right form panel ── -->
    <div class="auth-form-panel">

      <div class="auth-icon-wrap" aria-hidden="true">
        <i class="ph ph-lock-key-open auth-page-icon"></i>
      </div>

      <h1 class="auth-heading">Forgot your password?</h1>
      <p class="auth-subtext">
        Enter your email and we'll send you a secure link to reset your password.
      </p>

      <div id="authMsg" class="auth-msg" role="alert" aria-live="polite" style="display:none;"></div>

      <form id="forgotForm" novalidate>

        <div class="form-group">
          <label for="email">Email address</label>
          <div class="input-icon-wrap">
            <i class="ph ph-envelope input-icon" aria-hidden="true"></i>
            <input type="email" id="email" name="email" required
                   placeholder="you@example.com" autocomplete="email">
          </div>
        </div>

        <button type="submit" class="btn-primary full-width auth-submit" id="forgotBtn">
          Send Reset Link
        </button>

      </form>

      <p class="auth-footer-text">
        <a href="/login" class="auth-link">
          <i class="ph ph-arrow-left" aria-hidden="true"></i>
          Back to Sign In
        </a>
      </p>

    </div>
  </div>
</div>


<?php require_once '../../includes/footer.php'; ?>

</body>
</html>
