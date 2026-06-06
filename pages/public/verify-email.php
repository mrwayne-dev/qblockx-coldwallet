<?php
/**
 * Project: qblockx
 * Page: Email Verification — enter 6-digit code sent by email
 */
$pageTitle = 'Verify Your Email';
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
        <h2 class="auth-panel-heading">One last step<br>to get started.</h2>
        <p class="auth-panel-sub">
          Check your inbox for the 6-digit code and verify your Qblockx account.
        </p>
        <div class="auth-panel-stats">
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">12K+</span>
            <span class="auth-panel-stat-label">Holders</span>
          </div>
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">$500M+</span>
            <span class="auth-panel-stat-label">Secured</span>
          </div>
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">100%</span>
            <span class="auth-panel-stat-label">Offline Keys</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Right panel ── -->
    <div class="auth-form-panel" id="verifyPanel">

      <div class="auth-icon-wrap" aria-hidden="true">
        <i class="ph ph-envelope-open auth-page-icon"></i>
      </div>

      <h1 class="auth-heading">Enter your verification code</h1>
      <p class="auth-subtext" id="verifySubtext">
        We sent a 6-digit code to your email address. It expires in 15 minutes.
      </p>

      <div id="verifyMsg" class="auth-msg" role="alert" aria-live="polite" style="display:none;"></div>

      <form id="verifyCodeForm" novalidate style="margin-top: var(--space-6);">
        <div class="form-group">
          <input
            id="verifyCode"
            type="text"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            placeholder="000000"
            autocomplete="one-time-code"
            class="verify-code-input"
            aria-label="6-digit verification code"
          >
        </div>

        <button type="submit" class="btn-primary full-width auth-submit" id="verifyBtn">
          Verify Email
        </button>
      </form>

      <p class="auth-disclaimer" style="margin-top: var(--space-6);">
        Didn't receive it?
        <a href="#" id="resendLink" class="auth-link">Resend code</a>
      </p>
      <div id="resendMsg" class="auth-msg" role="alert" aria-live="polite" style="display:none; margin-top: var(--space-3);"></div>

      <p class="auth-footer-text" style="margin-top: var(--space-4);">
        Already verified? <a href="/login" class="auth-link">Sign in</a>
      </p>

    </div>
  </div>
</div>

<style>
/* ── Verification code input ── */
.verify-code-input {
  width: 100%;
  text-align: center;
  font-size: 2.4rem;
  font-family: 'Courier New', Courier, monospace;
  letter-spacing: 0.5em;
  padding: var(--space-4) var(--space-3);
  background: var(--color-surface-2);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
  outline: none;
  transition: border-color 0.2s;
}

.verify-code-input:focus {
  border-color: var(--color-accent);
}

.verify-code-input::placeholder {
  color: var(--color-text-muted);
  letter-spacing: 0.4em;
}
</style>


</body>
</html>
