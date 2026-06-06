<?php
/**
 * Project: qblockx
 * Page: User Login
 */
$pageTitle = 'Sign In';
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
        <h2 class="auth-panel-heading">Your keys,<br>still offline.</h2>
        <p class="auth-panel-sub">
          Sign in to manage your air-gapped cold wallet, view balances, and sign transfers — your private keys never leave cold storage.
        </p>
        <div class="auth-panel-stats">
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">12K+</span>
            <span class="auth-panel-stat-label">Holders</span>
          </div>
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">100%</span>
            <span class="auth-panel-stat-label">Offline Keys</span>
          </div>
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">0</span>
            <span class="auth-panel-stat-label">Breaches</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Right form panel ── -->
    <div class="auth-form-panel">

      <h1 class="auth-heading">Welcome back</h1>
      <p class="auth-subtext">Sign in to your cold wallet account</p>

      <!-- Error/success message -->
      <div id="authMsg" class="auth-msg" role="alert" aria-live="polite" style="display:none;"></div>

      <form id="loginForm" novalidate>

        <div class="form-group">
          <label for="email">Email address</label>
          <div class="input-icon-wrap">
            <i class="ph ph-envelope input-icon" aria-hidden="true"></i>
            <input type="email" id="email" name="email" required
                   placeholder="you@example.com" autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-icon-wrap">
            <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password" required
                   placeholder="Your password" autocomplete="current-password">
            <button type="button" class="input-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <i class="ph ph-eye" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <div class="auth-row">
          <a href="/forgot-password" class="auth-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary full-width auth-submit" id="loginBtn">
          Sign In
        </button>

      </form>

      <p class="auth-footer-text">
        Don't have an account?
        <a href="/register" class="auth-link">Create one</a>
      </p>

    </div>
  </div>
</div>


</body>
</html>
