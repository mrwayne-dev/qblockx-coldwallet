<?php
/**
 * Project: qblockx
 * Page: Reset Password
 */
$pageTitle = 'Reset Password';
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
        <h2 class="auth-panel-heading">Almost there.<br>You're secure.</h2>
        <p class="auth-panel-sub">
          Choose a strong password to protect your cold wallet account. We recommend using a mix of letters, numbers, and symbols.
        </p>
      </div>
    </div>

    <!-- ── Right form panel ── -->
    <div class="auth-form-panel">

      <div class="auth-icon-wrap" aria-hidden="true">
        <i class="ph ph-shield-check auth-page-icon"></i>
      </div>

      <h1 class="auth-heading">Set a new password</h1>
      <p class="auth-subtext">Choose a strong password of at least 8 characters.</p>

      <div id="authMsg" class="auth-msg" role="alert" aria-live="polite" style="display:none;"></div>

      <form id="resetForm" novalidate>

        <div class="form-group">
          <label for="password">New password</label>
          <div class="input-icon-wrap">
            <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password" required
                   minlength="8" placeholder="Min. 8 characters" autocomplete="new-password">
            <button type="button" class="input-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <i class="ph ph-eye" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm">Confirm password</label>
          <div class="input-icon-wrap">
            <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
            <input type="password" id="confirm" name="confirm" required
                   placeholder="Repeat your password" autocomplete="new-password">
          </div>
        </div>

        <button type="submit" class="btn-primary full-width auth-submit" id="resetBtn">
          Reset Password
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


</body>
</html>
