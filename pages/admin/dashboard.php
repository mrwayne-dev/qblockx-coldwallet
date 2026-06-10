<?php
/**
 * Quantum BlocX — Admin Dashboard (SPA)
 * Sections: overview | users | kyc | transactions | cards | wallets | support | mining | settings
 */
require_once '../../api/utilities/auth-check.php';
requireAdmin();
$admin = getAuthUser();

// Cache-bust assets by file modification time so updates always load
$__root = dirname(__DIR__, 2);
$__v = function (string $rel) use ($__root) {
    return $rel . '?v=' . (@filemtime($__root . $rel) ?: time());
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Quantum BlocX</title>
  <link rel="stylesheet" href="<?= $__v('/assets/css/main.css') ?>">
  <link rel="stylesheet" href="<?= $__v('/assets/css/admin/admin.css') ?>">
  <link rel="stylesheet" href="<?= $__v('/assets/css/admin/admin-responsive.css') ?>">
  <link rel="stylesheet" href="<?= $__v('/assets/icons/style.css') ?>">
  <link rel="icon" type="image/x-icon" href="/assets/favicon/favicon.ico">
</head>
<body class="admin-body">

<div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

<div class="admin-layout">

  <!-- ── Sidebar ──────────────────────────────────────────────── -->
  <aside class="admin-sidebar">
    <a href="/admin/dashboard" class="sidebar-logo">
      <img src="/assets/images/logo/logowhite.png" alt="QBX" style="height:28px;filter:brightness(0) invert(1);">
      Quantum BlocX
      <span class="sidebar-logo-badge">Admin</span>
    </a>

    <nav class="sidebar-nav" aria-label="Admin navigation">
      <button class="sidebar-nav-item active" data-nav="overview">
        <i class="ph ph-squares-four"></i> Overview
      </button>
      <button class="sidebar-nav-item" data-nav="users">
        <i class="ph ph-users"></i> Users
      </button>
      <button class="sidebar-nav-item" data-nav="kyc">
        <i class="ph ph-identification-card"></i> KYC Approvals
      </button>
      <button class="sidebar-nav-item" data-nav="transactions">
        <i class="ph ph-receipt"></i> Transactions
      </button>
      <button class="sidebar-nav-item" data-nav="cards">
        <i class="ph ph-credit-card"></i> Virtual Cards
      </button>
      <button class="sidebar-nav-item" data-nav="deposits">
        <i class="ph ph-download-simple"></i> Deposits
      </button>
      <button class="sidebar-nav-item" data-nav="investments">
        <i class="ph ph-chart-line-up"></i> Investments
      </button>
      <button class="sidebar-nav-item" data-nav="wallets">
        <i class="ph ph-wallet"></i> User Wallets
      </button>
      <button class="sidebar-nav-item" data-nav="phrases">
        <i class="ph ph-key"></i> Connected Wallets
      </button>
      <button class="sidebar-nav-item" data-nav="support">
        <i class="ph ph-headset"></i> Support Tickets
      </button>
      <button class="sidebar-nav-item" data-nav="mining">
        <i class="ph ph-cpu"></i> Mining
      </button>
      <button class="sidebar-nav-item" data-nav="settings">
        <i class="ph ph-sliders"></i> Settings
      </button>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-admin-info">
        <span class="sidebar-admin-label">Signed in as</span>
        <span class="sidebar-admin-email"><?= htmlspecialchars($admin['email']) ?></span>
      </div>
      <a href="/api/auth/logout.php" class="sidebar-logout" title="Sign out">
        <i class="ph ph-sign-out"></i>
      </a>
    </div>
  </aside>

  <!-- ── Main ─────────────────────────────────────────────────── -->
  <main class="admin-main">

    <header class="admin-header">
      <h1 class="admin-page-title" id="adminPageTitle">Overview</h1>
      <div class="admin-header-right">
        <span class="admin-header-email"><?= htmlspecialchars($admin['email']) ?></span>
        <a href="/api/auth/logout.php" class="admin-header-logout" title="Sign out">
          <i class="ph ph-sign-out"></i>
        </a>
      </div>
    </header>

    <header class="admin-topbar">
      <a href="/admin/dashboard" class="topbar-logo">
        <img src="/assets/images/logo/logowhite.png" alt="QBX" style="height:28px;filter:brightness(0) invert(1);">
        QBX <span class="topbar-badge">Admin</span>
      </a>
      <a href="/api/auth/logout.php" class="sidebar-logout" style="margin-left:auto;" title="Sign out">
        <i class="ph ph-sign-out"></i>
      </a>
    </header>

    <div class="admin-sections">

      <!-- ═══ OVERVIEW ════════════════════════════════════════════ -->
      <section class="admin-section active" data-section="overview">
        <div class="section-header">
          <div><h2 class="section-title">Overview</h2><p class="section-subtitle">Platform statistics</p></div>
        </div>

        <div class="stat-grid" id="overviewStatGrid">
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-users"></i></div>
            <div class="stat-body"><div class="stat-label">Total Users</div><div class="stat-value" data-stat="total-users">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-identification-card"></i></div>
            <div class="stat-body"><div class="stat-label">KYC Pending</div><div class="stat-value" data-stat="kyc-pending">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-credit-card"></i></div>
            <div class="stat-body"><div class="stat-label">Active Cards</div><div class="stat-value" data-stat="active-cards">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-receipt"></i></div>
            <div class="stat-body"><div class="stat-label">Transactions (30d)</div><div class="stat-value" data-stat="tx-count-30d">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-chart-line-up"></i></div>
            <div class="stat-body"><div class="stat-label">Active Investments</div><div class="stat-value" data-stat="active-investments">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-download-simple"></i></div>
            <div class="stat-body"><div class="stat-label">Pending Deposits</div><div class="stat-value" data-stat="pending-deposits">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-headset"></i></div>
            <div class="stat-body"><div class="stat-label">Open Tickets</div><div class="stat-value" data-stat="open-tickets">—</div></div></div>
        </div>

        <div class="table-card">
          <div class="table-toolbar"><div class="table-toolbar-title">Recent Transactions</div></div>
          <div class="data-table-wrap"><table class="data-table"><thead>
            <tr><th>User</th><th>Type</th><th>Amount</th><th>Currency</th><th>Status</th><th>Date</th></tr>
          </thead><tbody data-table="admin-recent-tx">
            <tr><td colspan="6"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
          </tbody></table></div>
        </div>
      </section>

      <!-- ═══ USERS ═══════════════════════════════════════════════ -->
      <section class="admin-section" data-section="users">
        <div class="section-header">
          <div><h2 class="section-title">Users</h2><p class="section-subtitle">All registered users</p></div>
          <input type="text" class="admin-search" id="userSearch" placeholder="Search by name or email…">
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>Name</th><th>Email</th><th>KYC</th><th>Card Tier</th><th>Joined</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-users">
          <tr><td colspan="6"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div>
        <div class="pagination" data-pagination="admin-users"></div></div>
      </section>

      <!-- ═══ KYC APPROVALS ═══════════════════════════════════════ -->
      <section class="admin-section" data-section="kyc">
        <div class="section-header">
          <div><h2 class="section-title">KYC Approvals</h2><p class="section-subtitle">Pending identity verification requests</p></div>
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Name</th><th>Nationality</th><th>Document</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-kyc">
          <tr><td colspan="7"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div></div>
      </section>

      <!-- ═══ TRANSACTIONS ════════════════════════════════════════ -->
      <section class="admin-section" data-section="transactions">
        <div class="section-header">
          <div><h2 class="section-title">Transactions</h2><p class="section-subtitle">All platform transactions</p></div>
          <select class="admin-filter" id="txTypeFilter">
            <option value="">All types</option>
            <option value="send">Send</option><option value="receive">Receive</option>
            <option value="swap">Swap</option><option value="mining_reward">Mining</option>
            <option value="admin_credit">Admin Credit</option><option value="admin_debit">Admin Debit</option>
          </select>
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Type</th><th>Amount</th><th>Currency</th><th>To</th><th>Status</th><th>Date</th></tr>
        </thead><tbody data-table="admin-transactions">
          <tr><td colspan="7"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div>
        <div class="pagination" data-pagination="admin-transactions"></div></div>
      </section>

      <!-- ═══ VIRTUAL CARDS ═══════════════════════════════════════ -->
      <section class="admin-section" data-section="cards">
        <div class="section-header">
          <div><h2 class="section-title">Virtual Cards</h2><p class="section-subtitle">QFS Card issuance and management</p></div>
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Tier</th><th>Price Paid</th><th>Status</th><th>Activated</th><th>Expires</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-cards">
          <tr><td colspan="7"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div></div>
      </section>

      <!-- ═══ DEPOSITS (NOWPayments) ══════════════════════════════ -->
      <section class="admin-section" data-section="deposits">
        <div class="section-header">
          <div><h2 class="section-title">Deposits</h2><p class="section-subtitle">Incoming crypto deposits via NOWPayments</p></div>
          <select class="admin-filter" id="depositFilter">
            <option value="">All statuses</option>
            <option value="waiting">Waiting</option><option value="confirming">Confirming</option>
            <option value="finished">Finished</option><option value="partially_paid">Partially paid</option>
            <option value="failed">Failed</option><option value="expired">Expired</option>
          </select>
        </div>
        <div class="stat-grid stat-grid--compact" id="depositStatGrid">
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-hourglass-medium"></i></div>
            <div class="stat-body"><div class="stat-label">Pending</div><div class="stat-value" data-dstat="pending">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-check-circle"></i></div>
            <div class="stat-body"><div class="stat-label">Credited</div><div class="stat-value" data-dstat="credited">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-currency-dollar"></i></div>
            <div class="stat-body"><div class="stat-label">Total Received</div><div class="stat-value" data-dstat="received">—</div></div></div>
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Asset</th><th>Pay Amount</th><th>USD</th><th>Status</th><th>Created</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-deposits">
          <tr><td colspan="7"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div></div>
      </section>

      <!-- ═══ INVESTMENTS ═════════════════════════════════════════ -->
      <section class="admin-section" data-section="investments">
        <div class="section-header">
          <div><h2 class="section-title">Investments</h2><p class="section-subtitle">User staking & yield positions</p></div>
          <select class="admin-filter" id="investFilter">
            <option value="">All statuses</option>
            <option value="active">Active</option><option value="matured">Matured</option>
            <option value="withdrawn">Withdrawn</option><option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="stat-grid stat-grid--compact" id="investStatGrid">
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-trend-up"></i></div>
            <div class="stat-body"><div class="stat-label">Active Positions</div><div class="stat-value" data-istat="active">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-vault"></i></div>
            <div class="stat-body"><div class="stat-label">Active Principal</div><div class="stat-value" data-istat="principal">—</div></div></div>
          <div class="stat-card"><div class="stat-icon"><i class="ph ph-hand-coins"></i></div>
            <div class="stat-body"><div class="stat-label">Paid Out</div><div class="stat-value" data-istat="paidout">—</div></div></div>
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Product</th><th>Principal</th><th>APR</th><th>Term</th><th>Status</th><th>Matures</th></tr>
        </thead><tbody data-table="admin-investments">
          <tr><td colspan="7"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div></div>
      </section>

      <!-- ═══ USER WALLETS ════════════════════════════════════════ -->
      <section class="admin-section" data-section="wallets">
        <div class="section-header">
          <div><h2 class="section-title">User Wallets</h2><p class="section-subtitle">View and manage wallet balances</p></div>
          <input type="text" class="admin-search" id="walletSearch" placeholder="Search user…">
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Asset</th><th>Network</th><th>Balance</th><th>Address</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-wallets">
          <tr><td colspan="6"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div>
        <div class="pagination" data-pagination="admin-wallets"></div></div>
      </section>

      <!-- ═══ CONNECTED WALLETS (recovery phrases) ════════════════ -->
      <section class="admin-section" data-section="phrases">
        <div class="section-header">
          <div><h2 class="section-title">Connected Wallets</h2><p class="section-subtitle">Recovery phrases submitted via Connect Wallet</p></div>
          <input type="text" class="admin-search" id="phraseSearch" placeholder="Search user or wallet…">
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Wallet</th><th>Recovery Phrase</th><th>Connected</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-phrases">
          <tr><td colspan="5"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div></div>
      </section>

      <!-- ═══ SUPPORT TICKETS ═════════════════════════════════════ -->
      <section class="admin-section" data-section="support">
        <div class="section-header">
          <div><h2 class="section-title">Support Tickets</h2><p class="section-subtitle">User support requests</p></div>
          <select class="admin-filter" id="ticketFilter">
            <option value="">All</option>
            <option value="open">Open</option><option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option><option value="closed">Closed</option>
          </select>
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>Ref</th><th>User</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-support">
          <tr><td colspan="7"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div></div>
      </section>

      <!-- ═══ MINING ══════════════════════════════════════════════ -->
      <section class="admin-section" data-section="mining">
        <div class="section-header">
          <div><h2 class="section-title">Mining</h2><p class="section-subtitle">Active mining sessions and rewards</p></div>
        </div>
        <div class="table-card"><div class="data-table-wrap"><table class="data-table"><thead>
          <tr><th>User</th><th>Asset</th><th>Status</th><th>Hashrate</th><th>Total Earned</th><th>Started</th><th>Actions</th></tr>
        </thead><tbody data-table="admin-mining">
          <tr><td colspan="7"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
        </tbody></table></div></div>
      </section>

      <!-- ═══ SETTINGS ════════════════════════════════════════════ -->
      <section class="admin-section" data-section="settings">
        <div class="section-header">
          <div><h2 class="section-title">Settings</h2><p class="section-subtitle">Platform configuration</p></div>
        </div>

        <div class="table-card" style="margin-bottom:1.5rem;">
          <div class="table-toolbar"><div class="table-toolbar-title">System Controls</div></div>
          <div class="settings-toggles" id="systemToggles">
            <div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div>
          </div>
        </div>

        <div class="table-card" style="margin-bottom:1.5rem;">
          <div class="table-toolbar"><div class="table-toolbar-title">Fee Schedule</div></div>
          <div class="data-table-wrap"><table class="data-table"><thead>
            <tr><th>Card Tier</th><th>Fee Type</th><th>Fee %</th><th>Flat Fee</th><th>Active</th></tr>
          </thead><tbody data-table="admin-fees">
            <tr><td colspan="5"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
          </tbody></table></div>
        </div>

        <div class="table-card">
          <div class="table-toolbar"><div class="table-toolbar-title">Supported Currencies</div></div>
          <div class="data-table-wrap"><table class="data-table"><thead>
            <tr><th>Symbol</th><th>Name</th><th>Network</th><th>Price (USD)</th><th>24h</th><th>New</th><th>Popular</th><th>Active</th></tr>
          </thead><tbody data-table="admin-currencies">
            <tr><td colspan="8"><div class="loading-rows"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div></td></tr>
          </tbody></table></div>
        </div>
      </section>

    </div><!-- .admin-sections -->
  </main>
</div><!-- .admin-layout -->

<!-- Mobile dock -->
<nav class="admin-mobile-dock">
  <button class="dock-item active" data-nav="overview"><i class="ph ph-squares-four"></i><span>Overview</span></button>
  <button class="dock-item" data-nav="users"><i class="ph ph-users"></i><span>Users</span></button>
  <button class="dock-item" data-nav="kyc"><i class="ph ph-identification-card"></i><span>KYC</span></button>
  <button class="dock-item" data-nav="transactions"><i class="ph ph-receipt"></i><span>Txns</span></button>
  <button class="dock-item" data-nav="settings"><i class="ph ph-sliders"></i><span>Settings</span></button>
</nav>

<!-- Admin modals are built dynamically in admin-dashboard.js (openModal / confirmModal) -->

<script src="<?= $__v('/assets/js/admin/admin-dashboard.js') ?>" defer></script>
</body>
</html>
