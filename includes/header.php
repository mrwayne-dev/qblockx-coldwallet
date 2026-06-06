<?php
/**
 * Project: Qblockx
 * Include: header.php
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$lightNav    = isset($navTheme) && $navTheme === 'light';

function navActive(string $page, string $current): string {
    return $current === $page ? ' aria-current="page"' : '';
}
?>

<header class="nav-public" data-header<?= $lightNav ? ' data-light-nav' : '' ?>>
  <div class="nav-inner">

    <!-- Logo -->
    <a href="/" class="nav-logo" aria-label="Qblockx home">
      <img src="/assets/images/logo/logowhite.png" class="nav-logo-img nav-logo-dark" alt="">
      <img src="/assets/images/logo/logoblue.png"  class="nav-logo-img nav-logo-light" alt="">
      <span class="nav-logo-text">QBLOCKX</span>
    </a>

    <!-- Actions -->
    <div class="nav-actions">
      <a href="/login" class="nav-login-link">Log in</a>
      <a href="/register" class="nav-cta-btn">Get started</a>
      <button class="nav-hamburger" data-nav-toggler
              aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
        <span></span>
        <span></span>
      </button>
    </div>

  </div>
</header>

<!-- Mobile drawer overlay -->
<div class="nav-mobile-drawer" id="mobileMenu" data-mobile-menu aria-hidden="true">
  <div class="mobile-drawer-inner">

    <button class="mobile-drawer-close" data-nav-toggler aria-label="Close menu">
      <i class="ph ph-x" aria-hidden="true"></i>
    </button>

    <a href="/" class="mobile-drawer-logo">
      <img src="/assets/images/logo/logowhite.png" alt="" style="height:24px;">
      <span>QBLOCKX</span>
    </a>

    <div class="mobile-drawer-actions">
      <a href="/login" class="mobile-drawer-login">Log In</a>
      <a href="/register" class="btn-primary" style="justify-content:center;">Get Started</a>
    </div>

  </div>
</div>
