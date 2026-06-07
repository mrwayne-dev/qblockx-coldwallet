<?php
/**
 * Project: qblockx
 * Include: mobile-dock.php — bottom mobile navigation dock (Quantum BlocX cold wallet layout)
 * Visible only on ≤ 899px via responsive.css
 */
$currentSection = $currentSection ?? 'overview';
?>

<nav class="mobile-dock" aria-label="Mobile navigation" role="navigation">

  <a href="#" class="dock-item <?= $currentSection === 'overview' ? 'active' : '' ?>"
     data-nav="overview" aria-label="Overview">
    <i class="ph ph-squares-four" aria-hidden="true"></i>
    <span>Overview</span>
  </a>

  <a href="#" class="dock-item <?= $currentSection === 'send' ? 'active' : '' ?>"
     data-nav="send" aria-label="Send">
    <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
    <span>Send</span>
  </a>

  <a href="#" class="dock-item <?= $currentSection === 'receive' ? 'active' : '' ?>"
     data-nav="receive" aria-label="Receive">
    <i class="ph ph-arrow-down-left" aria-hidden="true"></i>
    <span>Receive</span>
  </a>

  <a href="#" class="dock-item <?= $currentSection === 'swap' ? 'active' : '' ?>"
     data-nav="swap" aria-label="Swap">
    <i class="ph ph-swap" aria-hidden="true"></i>
    <span>Swap</span>
  </a>

  <a href="#" class="dock-item <?= $currentSection === 'profile' ? 'active' : '' ?>"
     data-nav="profile" aria-label="Profile">
    <i class="ph ph-user-circle" aria-hidden="true"></i>
    <span>Profile</span>
  </a>

</nav>
