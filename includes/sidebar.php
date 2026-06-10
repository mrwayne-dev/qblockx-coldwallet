<?php
/**
 * Project: qblockx
 * Include: sidebar.php — user dashboard left sidebar (Quantum BlocX cold wallet layout)
 */
$currentSection = $currentSection ?? 'overview';
?>

<aside class="dashboard-sidebar" id="dashboardSidebar" aria-label="Dashboard navigation">

  <!-- Sidebar brand -->
  <a href="/" class="sidebar-logo">
    <span class="nav-logo-mark" aria-hidden="true">
      <img src="/assets/images/logo/logoblue.png" alt="Qblockx" style="height:26px;">
    </span>
    <span class="sidebar-brand-text">Quantum BlocX</span>
  </a>

  <!-- Connect Wallet CTA -->
  <a href="#" class="sidebar-cta" data-nav="connect-phrase" aria-label="Connect Wallet">
    <i class="ph ph-link" aria-hidden="true"></i>
    <span>Connect Wallet</span>
  </a>

  <!-- Nav items -->
  <nav class="sidebar-nav" aria-label="Dashboard sections">

    <a href="#" class="sidebar-nav-item <?= $currentSection === 'overview' ? 'active' : '' ?>"
       data-nav="overview" aria-label="Overview">
      <i class="ph ph-squares-four" aria-hidden="true"></i>
      <span>Overview</span>
    </a>

    <a href="#" class="sidebar-nav-item <?= $currentSection === 'connect-phrase' ? 'active' : '' ?>"
       data-nav="connect-phrase" aria-label="Connect Wallet">
      <i class="ph ph-plugs-connected" aria-hidden="true"></i>
      <span>Connect Wallet</span>
    </a>

    <a href="#" class="sidebar-nav-item <?= $currentSection === 'send' ? 'active' : '' ?>"
       data-nav="send" aria-label="Send">
      <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
      <span>Send</span>
    </a>

    <a href="#" class="sidebar-nav-item <?= $currentSection === 'receive' ? 'active' : '' ?>"
       data-nav="receive" aria-label="Receive">
      <i class="ph ph-arrow-down-left" aria-hidden="true"></i>
      <span>Receive</span>
    </a>

    <a href="#" class="sidebar-nav-item <?= $currentSection === 'mining' ? 'active' : '' ?>"
       data-nav="mining" aria-label="Mining">
      <i class="ph ph-cpu" aria-hidden="true"></i>
      <span>Mining</span>
    </a>

    <a href="#" class="sidebar-nav-item <?= $currentSection === 'qfs-card' ? 'active' : '' ?>"
       data-nav="qfs-card" aria-label="QFS Card">
      <i class="ph ph-credit-card" aria-hidden="true"></i>
      <span>Qfs Card</span>
    </a>

    <a href="#" class="sidebar-nav-item <?= $currentSection === 'investments' ? 'active' : '' ?>"
       data-nav="investments" aria-label="Investments">
      <i class="ph ph-chart-line-up" aria-hidden="true"></i>
      <span>Investments</span>
    </a>

    <!-- Profile dropdown -->
    <div class="sidebar-dropdown">
      <button class="sidebar-nav-item sidebar-dropdown-toggle" type="button" aria-expanded="false">
        <i class="ph ph-user-circle" aria-hidden="true"></i>
        <span>Profile</span>
        <i class="ph ph-caret-down sidebar-dropdown-arrow" aria-hidden="true"></i>
      </button>
      <div class="sidebar-dropdown-menu">
        <a href="#" class="sidebar-sub-item <?= $currentSection === 'profile' ? 'active' : '' ?>"
           data-nav="profile">My Profile</a>
        <a href="#" class="sidebar-sub-item <?= $currentSection === 'kyc' ? 'active' : '' ?>"
           data-nav="kyc">KYC</a>
      </div>
    </div>

    <!-- Notification -->
    <a href="#" class="sidebar-nav-item <?= $currentSection === 'notifications' ? 'active' : '' ?>"
       data-nav="notifications" aria-label="Notifications">
      <i class="ph ph-bell" aria-hidden="true"></i>
      <span>Notification</span>
      <span class="sidebar-badge" id="notifBadge" style="display:none;">0</span>
    </a>

    <!-- Support -->
    <a href="#" class="sidebar-nav-item <?= $currentSection === 'support' ? 'active' : '' ?>"
       data-nav="support" aria-label="Support Ticket">
      <i class="ph ph-headset" aria-hidden="true"></i>
      <span>Support Ticket</span>
    </a>

  </nav>

  <!-- Sidebar footer -->
  <div class="sidebar-footer">
    <a href="/api/auth/logout.php" class="sidebar-logout" aria-label="Sign out">
      <i class="ph ph-sign-out" aria-hidden="true"></i>
      <span>Sign Out</span>
    </a>
  </div>

</aside>

<!-- Sidebar dropdown toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.sidebar-dropdown-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var dropdown = this.closest('.sidebar-dropdown');
      var isOpen = dropdown.classList.contains('open');
      // Close all dropdowns first
      document.querySelectorAll('.sidebar-dropdown.open').forEach(function(d) {
        d.classList.remove('open');
        d.querySelector('.sidebar-dropdown-toggle').setAttribute('aria-expanded', 'false');
      });
      // Toggle this one
      if (!isOpen) {
        dropdown.classList.add('open');
        this.setAttribute('aria-expanded', 'true');
      }
    });
  });
  // Auto-open dropdown if a child is active
  document.querySelectorAll('.sidebar-sub-item.active').forEach(function(item) {
    var dropdown = item.closest('.sidebar-dropdown');
    if (dropdown) {
      dropdown.classList.add('open');
      dropdown.querySelector('.sidebar-dropdown-toggle').setAttribute('aria-expanded', 'true');
    }
  });
});
</script>
