<?php
/**
 * Project: qblockx
 * Page: User Dashboard — Single-Page Application (Quantum BlocX Cold Wallet)
 *
 * Sections: overview | connect-phrase | send | receive | mining | qfs-card |
 *           investments | profile | kyc | notifications | security | 2fa | support
 * Navigation handled by assets/js/user/user-dashboard.js via path-based routing.
 */

require_once '../../includes/auth-guard.php';

$pageTitle        = 'Dashboard';
$bodyClass        = 'dashboard-body';
// Cache-bust assets by file modification time so updates always load
$__root = dirname(__DIR__, 2);
$__v = function (string $rel) use ($__root) {
    return $rel . '?v=' . (@filemtime($__root . $rel) ?: time());
};
$extraHeadLinks   = [$__v('/assets/css/dashboard.css'), $__v('/assets/css/user/user-responsive.css')];
$extraHeadScripts = [];
require_once '../../includes/head.php';
?>

<!-- ── Global: Toast Container ──────────────────────────────── -->
<div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

<!-- ── Global: Full-page Loader ─────────────────────────────── -->
<div id="globalLoader" class="global-loader" role="status" aria-label="Loading">
  <div class="loader-spinner">
    <i class="ph ph-circle-notch" aria-hidden="true"></i>
  </div>
</div>

<!-- ── App Shell ─────────────────────────────────────────────── -->
<div class="dashboard-wrapper">

  <?php require_once '../../includes/sidebar.php'; ?>
  <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

  <main class="dashboard-main">

    <!-- ── Sticky Header ──────────────────────────────────────── -->
    <header class="dashboard-header">
      <div class="dashboard-header-left">
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Open menu" aria-expanded="false">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"
               stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
            <line x1="3" y1="6"  x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <h1 class="dashboard-page-title" id="pageTitle">Dashboard</h1>
      </div>
      <div class="dashboard-header-right">
        <button class="header-icon-btn" type="button" data-nav="notifications" aria-label="Notifications">
          <i class="ph ph-bell" aria-hidden="true"></i>
          <span class="header-notif-dot" id="headerNotifDot" style="display:none;"></span>
        </button>
        <div class="header-user">
          <span class="header-username" data-user="name"></span>
          <div class="avatar-circle" data-user="initial" aria-hidden="true">U</div>
        </div>
      </div>
    </header>

    <!-- ════════════════════════════════════════════════════════
         SECTION — Overview (Crypto Portfolio)
         ════════════════════════════════════════════════════════ -->
    <section data-section="overview" class="dashboard-section">

      <p class="section-label"><i class="ph ph-squares-four"></i> Overview</p>

      <!-- Balance Hero Card -->
      <div class="balance-hero balance-hero--crypto">
        <div class="balance-hero-left">
          <span class="balance-label">Total Balance</span>
          <div class="balance-display">
            <span class="balance-currency">$</span>
            <span class="balance-value" data-stat="total-balance">0.00</span>
            <button class="balance-toggle" id="balanceToggle" type="button" aria-label="Toggle balance visibility">
              <i class="ph ph-eye" id="balanceToggleIcon"></i>
            </button>
          </div>
          <span class="balance-sub">Welcome back, <span data-user="name">User</span></span>
        </div>
        <div class="balance-hero-actions">
          <button class="btn-hero btn-hero--receive" type="button" data-nav="receive">
            <i class="ph ph-arrow-down-left" aria-hidden="true"></i>
            Receive
          </button>
          <button class="btn-hero btn-hero--send" type="button" data-nav="send">
            <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
            Send
          </button>
        </div>
      </div>

      <!-- Asset List -->
      <div class="asset-list-card">
        <div class="asset-list-header">
          <h3>Assets</h3>
          <span class="asset-count" id="assetCount">29 assets</span>
        </div>
        <div class="asset-list" id="assetList">
          <!-- Rendered by JS — each row structure:
          <div class="asset-row" data-symbol="BTC">
            <div class="asset-row-left">
              <img class="asset-icon" src="https://cdn.jsdelivr.net/gh/nickvdyck/cryptocurrency-icons/128/color/btc.png" alt="BTC" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <div class="asset-icon-fallback" style="display:none;">BTC</div>
              <div class="asset-name-col">
                <span class="asset-name">Bitcoin</span>
                <span class="asset-symbol">BTC</span>
              </div>
            </div>
            <div class="asset-row-center">
              <span class="asset-price">$104,230.50</span>
              <span class="asset-change asset-change--up">+2.35%</span>
            </div>
            <div class="asset-row-right">
              <span class="asset-holding-usd">$0.00</span>
              <span class="asset-holding-native">0.00000000 BTC</span>
            </div>
            <span class="asset-badge asset-badge--new">New</span>
          </div>
          -->
          <div class="asset-list-loading">
            <i class="ph ph-circle-notch ph-spin" aria-hidden="true"></i>
            <span>Loading assets…</span>
          </div>
        </div>
      </div>

    </section><!-- /overview -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Connect Wallet
         ════════════════════════════════════════════════════════ -->
    <section data-section="connect-phrase" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-plugs-connected"></i> Connect Wallet</p>

      <!-- Connected wallets panel (shown once the user has linked at least one) -->
      <div class="connected-wallets-panel" id="connectedWalletsPanel" style="display:none;">
        <div class="cw-head">
          <div class="cw-head-text">
            <h3><i class="ph ph-wallet"></i> Your Connected Wallets</h3>
            <p>Wallets linked to your account — recovery phrases are encrypted &amp; stored securely.</p>
          </div>
          <span class="cw-count-badge"><i class="ph ph-check-circle"></i> <span id="cwCount">0</span><span class="cw-count-max">/5</span></span>
        </div>
        <div class="connected-wallets-grid" id="connectedWalletsList"></div>
      </div>

      <div class="connect-wallet-hero">
        <div class="connect-wallet-icon"><i class="ph ph-link-simple"></i></div>
        <h2>Connect Your Wallet</h2>
        <p>Select your wallet and connect it with your recovery phrase to manage your assets from within Quantum BlocX. We support 170+ wallets.</p>
      </div>

      <div class="wallet-search-wrap">
        <div class="input-icon-wrap">
          <i class="ph ph-magnifying-glass input-icon" aria-hidden="true"></i>
          <input type="text" id="walletProviderSearch" placeholder="Search wallets…"
                 autocomplete="off" class="wallet-search-input">
        </div>
      </div>

      <div class="wallet-provider-grid" id="walletProviderGrid">
        <div class="asset-list-loading">
          <i class="ph ph-circle-notch ph-spin" aria-hidden="true"></i>
          <span>Loading wallet providers…</span>
        </div>
      </div>

    </section><!-- /connect-phrase -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Send Crypto
         ════════════════════════════════════════════════════════ -->
    <section data-section="send" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-arrow-up-right"></i> Send</p>

      <!-- Send gate: card required -->
      <div class="gate-banner" id="sendGateBanner">
        <i class="ph ph-warning-circle" aria-hidden="true"></i>
        <div>
          <strong>QFS Card Required</strong>
          <p>You can only send out assets when you have successfully activated your QFS card.</p>
        </div>
        <button class="btn-primary btn-sm" type="button" data-nav="qfs-card">Get QFS Card</button>
      </div>

      <!-- Send form (hidden until card active) -->
      <div class="send-form-wrap" id="sendFormWrap" style="display:none;">

        <!-- Recipient type toggle -->
        <div class="form-card">
          <h3>Send To</h3>
          <div class="send-type-toggle">
            <label class="send-type-option">
              <input type="radio" name="send_type" value="address" checked>
              <span><i class="ph ph-wallet"></i> External Wallet Address</span>
            </label>
            <label class="send-type-option">
              <input type="radio" name="send_type" value="internal">
              <span><i class="ph ph-user"></i> Quantum BlocX User</span>
            </label>
          </div>
        </div>

        <div class="form-card">
          <h3>Transaction Details</h3>
          <form data-action="send-crypto" novalidate>

            <div class="form-group">
              <label for="sendAsset">Select Asset</label>
              <select id="sendAsset" name="currency_id" class="form-select">
                <option value="">Choose currency…</option>
              </select>
            </div>

            <div class="form-group" id="sendAddressGroup">
              <label for="sendAddress">Recipient Address</label>
              <div class="input-icon-wrap">
                <i class="ph ph-wallet input-icon" aria-hidden="true"></i>
                <input type="text" id="sendAddress" name="address"
                       placeholder="Enter wallet address" autocomplete="off">
              </div>
            </div>

            <div class="form-group" id="sendUsernameGroup" style="display:none;">
              <label for="sendUsername">Recipient Username or Email</label>
              <div class="input-icon-wrap">
                <i class="ph ph-user input-icon" aria-hidden="true"></i>
                <input type="text" id="sendUsername" name="recipient"
                       placeholder="Enter username or email" autocomplete="off">
              </div>
            </div>

            <div class="form-group">
              <label for="sendAmount">Amount</label>
              <div class="input-with-action">
                <input type="number" id="sendAmount" name="amount"
                       placeholder="0.00" step="any" min="0">
                <button type="button" class="btn-inline" id="sendMaxBtn">MAX</button>
              </div>
              <span class="form-hint" id="sendAvailable">Available: —</span>
            </div>

            <div data-msg class="form-message" style="display:none;"></div>

            <button type="submit" class="btn-primary btn-full">
              <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
              Continue
            </button>
          </form>
        </div>
      </div>

    </section><!-- /send -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Receive Crypto
         ════════════════════════════════════════════════════════ -->
    <section data-section="receive" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-arrow-down-left"></i> Receive</p>

      <!-- Step 1 — choose asset + amount -->
      <div class="form-card" id="receiveChooseCard">
        <h3>Receive Crypto</h3>
        <p class="form-hint">Generate a secure deposit address. Send the exact amount shown and your balance updates automatically once the network confirms it.</p>
        <div class="form-row form-row--2">
          <div class="form-group">
            <label for="receiveAsset">Asset</label>
            <select id="receiveAsset" name="currency_id" class="form-select">
              <option value="">Loading assets…</option>
            </select>
          </div>
          <div class="form-group">
            <label for="receiveAmount">Amount (USD)</label>
            <div class="input-icon-wrap">
              <i class="ph ph-currency-dollar input-icon" aria-hidden="true"></i>
              <input type="number" id="receiveAmount" placeholder="100.00" min="0" step="any" autocomplete="off">
            </div>
          </div>
        </div>
        <div data-msg class="form-message" style="display:none;"></div>
        <button type="button" class="btn-primary btn-full" id="receiveGenBtn">
          <i class="ph ph-qr-code" aria-hidden="true"></i>
          Generate Deposit Address
        </button>
      </div>

      <!-- Step 2 — deposit address + live status -->
      <div class="receive-detail-card" id="receiveDetailCard" style="display:none;">

        <div class="receive-status-bar" id="receiveStatusBar">
          <span class="rcv-dot" id="receiveStatusDot"></span>
          <span id="receiveStatusText">Waiting for your payment…</span>
        </div>

        <div class="receive-warning">
          <i class="ph ph-warning" aria-hidden="true"></i>
          <span>Send only <strong id="receiveAssetName">—</strong> on the <strong id="receiveNetwork2">—</strong> network. Sending anything else may be lost permanently.</span>
        </div>

        <div class="receive-qr-wrap">
          <img id="receiveQrImg" alt="Deposit QR code" width="220" height="220"
               onerror="this.style.display='none';">
        </div>

        <div class="receive-amount-box">
          <label>Send exactly</label>
          <div class="copy-field">
            <code id="receivePayAmount" class="receive-amount-text">—</code>
            <button type="button" class="btn-copy" id="copyAmountBtn" aria-label="Copy amount">
              <i class="ph ph-copy" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <div class="receive-address-wrap">
          <label>To this <span id="receiveAssetSymbol">—</span> address</label>
          <div class="copy-field">
            <code id="receiveAddress" class="receive-address-text">—</code>
            <button type="button" class="btn-copy" id="copyAddressBtn" aria-label="Copy address">
              <i class="ph ph-copy" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <div class="receive-network-info">
          <div class="network-info-row">
            <span>Network</span>
            <strong id="receiveNetwork">—</strong>
          </div>
          <div class="network-info-row">
            <span>USD value</span>
            <strong id="receiveUsd">—</strong>
          </div>
          <div class="network-info-row">
            <span>Address expires</span>
            <strong id="receiveExpiry">—</strong>
          </div>
        </div>

        <button type="button" class="btn-outline btn-full" id="receiveNewBtn">
          <i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i>
          Generate another address
        </button>
      </div>

    </section><!-- /receive -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Mining (Premium-Gated)
         ════════════════════════════════════════════════════════ -->
    <section data-section="mining" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-cpu"></i> Mining</p>

      <div class="premium-gate" id="miningGate">
        <div class="premium-gate-icon"><i class="ph ph-lock-simple"></i></div>
        <h2>Crypto Mining</h2>
        <p>Earn cryptocurrency rewards automatically. Mine different cryptocurrencies to diversify your holdings and contribute to blockchain network security.</p>

        <div class="premium-benefits">
          <div class="premium-benefit">
            <i class="ph ph-coins" aria-hidden="true"></i>
            <h4>Passive Income</h4>
            <p>Earn cryptocurrency automatically without active trading</p>
          </div>
          <div class="premium-benefit">
            <i class="ph ph-chart-pie-slice" aria-hidden="true"></i>
            <h4>Portfolio Diversification</h4>
            <p>Mine different cryptocurrencies to diversify your holdings</p>
          </div>
          <div class="premium-benefit">
            <i class="ph ph-shield-checkered" aria-hidden="true"></i>
            <h4>Support Networks</h4>
            <p>Contribute to blockchain security and operations</p>
          </div>
        </div>

        <div class="premium-gate-cta">
          <p>Mining requires a <strong>VirtuElevate</strong> ($25,000) or <strong>VirtuElite</strong> ($35,000) premium card.</p>
          <button class="btn-primary btn-lg" type="button" data-nav="qfs-card">
            <i class="ph ph-credit-card" aria-hidden="true"></i>
            Upgrade to Premium Card
          </button>
        </div>
      </div>

      <!-- Mining dashboard (shown for premium users) -->
      <div id="miningDashboard" style="display:none;">
        <!-- Populated by JS for premium card holders -->
      </div>

    </section><!-- /mining -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — QFS Card
         ════════════════════════════════════════════════════════ -->
    <section data-section="qfs-card" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-credit-card"></i> Qfs Card</p>

      <div class="qfs-card-hero">
        <div class="qfs-card-visual">
          <div class="virtual-card-face">
            <div class="vc-top">
              <span class="vc-brand">Quantum BlocX</span>
              <span class="vc-network">Mastercard</span>
            </div>
            <div class="vc-number">•••• •••• •••• ••••</div>
            <div class="vc-bottom">
              <div>
                <span class="vc-label">Card Holder</span>
                <span class="vc-value" data-user="name">—</span>
              </div>
              <div>
                <span class="vc-label">Expires</span>
                <span class="vc-value">—/—</span>
              </div>
            </div>
            <div class="vc-badge">The QFS Virtual Card® — The XRP Edition</div>
          </div>
        </div>
        <div class="qfs-card-info">
          <h2>The QFS Virtual Card®</h2>
          <p id="qfsCardInfoText">Issued by WebBank. Up to 4% cashback on purchases. No annual fee.</p>
          <button class="btn-primary btn-lg" type="button" id="requestCardBtn">
            <i class="ph ph-credit-card" aria-hidden="true"></i>
            Request New Card
          </button>
        </div>
      </div>

      <!-- Card Tiers -->
      <h3 class="section-heading" id="qfsTierHeading" style="margin-top:3.2rem;">Choose Your Tier</h3>
      <div class="card-tier-grid">
        <div class="card-tier">
          <div class="card-tier-header">
            <h4>VirtuElevate</h4>
            <span class="card-tier-price">$25,000</span>
          </div>
          <ul class="card-tier-features">
            <li><i class="ph ph-check-circle"></i> Mining Access</li>
            <li><i class="ph ph-check-circle"></i> Premium Transaction Limits</li>
            <li><i class="ph ph-check-circle"></i> Reduced Exchange Fees</li>
            <li><i class="ph ph-x-circle"></i> <span class="tier-disabled">Zero Exchange Fees</span></li>
            <li><i class="ph ph-x-circle"></i> <span class="tier-disabled">Priority Support</span></li>
          </ul>
          <button class="btn-primary btn-full" type="button" data-tier="VirtuElevate">Select VirtuElevate</button>
        </div>
        <div class="card-tier card-tier--elite">
          <div class="card-tier-badge">Most Popular</div>
          <div class="card-tier-header">
            <h4>VirtuElite</h4>
            <span class="card-tier-price">$35,000</span>
          </div>
          <ul class="card-tier-features">
            <li><i class="ph ph-check-circle"></i> Mining Access</li>
            <li><i class="ph ph-check-circle"></i> Highest Transaction Limits</li>
            <li><i class="ph ph-check-circle"></i> Zero Exchange Fees</li>
            <li><i class="ph ph-check-circle"></i> Investment Access</li>
            <li><i class="ph ph-check-circle"></i> Priority Support</li>
          </ul>
          <button class="btn-primary btn-full" type="button" data-tier="VirtuElite">Select VirtuElite</button>
        </div>
      </div>

    </section><!-- /qfs-card -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Investments (Premium-Gated)
         ════════════════════════════════════════════════════════ -->
    <section data-section="investments" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-chart-line-up"></i> Investments</p>

      <div class="premium-gate" id="investmentsGate">
        <div class="premium-gate-icon"><i class="ph ph-lock-simple"></i></div>
        <h2>Investments</h2>
        <p>Earn passive returns on your cryptocurrency holdings. This feature is exclusively available to premium card holders.</p>
        <div class="premium-gate-cta">
          <p>Investments require a <strong>VirtuElevate</strong> ($25,000) or <strong>VirtuElite</strong> ($35,000) premium card.</p>
          <button class="btn-primary btn-lg" type="button" data-nav="qfs-card">
            <i class="ph ph-credit-card" aria-hidden="true"></i>
            Upgrade to Premium Card
          </button>
        </div>
      </div>

      <div id="investmentsDashboard" style="display:none;">
        <!-- Populated by JS for premium card holders -->
      </div>

    </section><!-- /investments -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Profile
         ════════════════════════════════════════════════════════ -->
    <section data-section="profile" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-user-circle"></i> My Profile</p>

      <!-- Profile Header Card -->
      <div class="profile-header-card">
        <div class="avatar-circle avatar-circle--lg" data-user="initial" aria-hidden="true">U</div>
        <div class="profile-meta">
          <h2 data-user="name">Loading…</h2>
          <p class="text-muted" data-profile="email">—</p>
          <div class="profile-badges">
            <span class="badge badge-muted">
              Member since: <span data-profile="member-since">—</span>
            </span>
            <span class="badge" data-profile="verified">—</span>
            <span class="badge" data-profile="kyc-status">KYC: Unverified</span>
          </div>
        </div>
      </div>

      <!-- Personal Info Form -->
      <div class="form-card">
        <h3>Personal Information</h3>
        <form data-action="update-profile" novalidate>
          <div class="form-group">
            <label for="profileFullName">Full Name</label>
            <div class="input-icon-wrap">
              <i class="ph ph-user input-icon" aria-hidden="true"></i>
              <input type="text" id="profileFullName" name="full_name"
                     placeholder="Your full name" autocomplete="name">
            </div>
          </div>
          <div class="form-group">
            <label for="profileEmail">Email Address</label>
            <div class="input-icon-wrap">
              <i class="ph ph-envelope input-icon" aria-hidden="true"></i>
              <input type="email" id="profileEmail" name="email"
                     placeholder="your@email.com" autocomplete="email" readonly class="input-readonly">
            </div>
          </div>
          <div data-msg class="form-message" style="display:none;"></div>
          <button type="submit" class="btn-primary">
            <i class="ph ph-floppy-disk" aria-hidden="true"></i>
            Save Changes
          </button>
        </form>
      </div>

      <!-- Recovery Phrase -->
      <div class="form-card">
        <h3><i class="ph ph-key"></i> Recovery Phrase</h3>
        <p class="form-hint">The Recovery Phrase is the Master Key to your funds. Never share it with anyone else.</p>
        <div class="recovery-phrase-box" id="recoveryPhraseBox">
          <span class="recovery-hidden">•••••• •••••• •••••• •••••• •••••• ••••••</span>
        </div>
        <button class="btn-outline" type="button" id="showPhraseBtn">
          <i class="ph ph-eye" aria-hidden="true"></i>
          Show Phrase
        </button>
      </div>

      <!-- IP + Session Info -->
      <div class="form-card">
        <h3>Session Information</h3>
        <div class="wallet-info-grid">
          <div class="wallet-info-item">
            <span class="wallet-info-label">Current IP</span>
            <span class="wallet-info-value" data-profile="ip">—</span>
          </div>
          <div class="wallet-info-item">
            <span class="wallet-info-label">Account Status</span>
            <span class="wallet-info-value" data-profile="active">—</span>
          </div>
        </div>
      </div>

      <!-- Change Password -->
      <div class="form-card">
        <h3><i class="ph ph-lock-simple"></i> Change Password</h3>
        <form data-action="change-password" novalidate>
          <div class="form-group">
            <label for="secOldPass">Old Password</label>
            <div class="input-icon-wrap">
              <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
              <input type="password" id="secOldPass" name="current_password"
                     placeholder="Current password" autocomplete="current-password" required>
            </div>
          </div>
          <div class="form-group">
            <label for="secNewPass">New Password</label>
            <div class="input-icon-wrap">
              <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
              <input type="password" id="secNewPass" name="new_password"
                     placeholder="Min. 8 characters" autocomplete="new-password" required>
            </div>
          </div>
          <div class="form-group">
            <label for="secConfirmPass">Confirm New Password</label>
            <div class="input-icon-wrap">
              <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
              <input type="password" id="secConfirmPass" name="confirm_password"
                     placeholder="Confirm new password" autocomplete="new-password" required>
            </div>
          </div>
          <div data-msg class="form-message" style="display:none;"></div>
          <button type="submit" class="btn-primary">
            <i class="ph ph-floppy-disk" aria-hidden="true"></i>
            Update Password
          </button>
        </form>
      </div>

    </section><!-- /profile -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — KYC Verification
         ════════════════════════════════════════════════════════ -->
    <section data-section="kyc" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-identification-card"></i> KYC Verification</p>

      <div class="kyc-status-banner" id="kycStatusBanner">
        <i class="ph ph-info"></i>
        <span>KYC Status: <strong id="kycStatusText">Unverified</strong></span>
      </div>

      <div class="form-card" id="kycForm">
        <h3>Identity Verification</h3>
        <p class="form-hint">Complete your KYC to unlock sending, card requests, and full platform access. You can't edit these details once submitted.</p>

        <form data-action="submit-kyc" novalidate>

          <h4>Section 1 — Personal Details</h4>
          <div class="form-row form-row--2">
            <div class="form-group">
              <label for="kycFirstName">First Name</label>
              <input type="text" id="kycFirstName" name="first_name" placeholder="First name" required>
            </div>
            <div class="form-group">
              <label for="kycLastName">Last Name</label>
              <input type="text" id="kycLastName" name="last_name" placeholder="Last name" required>
            </div>
          </div>
          <div class="form-row form-row--2">
            <div class="form-group">
              <label for="kycEmail">Email</label>
              <input type="email" id="kycEmail" name="email" placeholder="Email address" required>
            </div>
            <div class="form-group">
              <label for="kycPhone">Phone Number</label>
              <input type="tel" id="kycPhone" name="phone" placeholder="Phone number" required>
            </div>
          </div>
          <div class="form-row form-row--2">
            <div class="form-group">
              <label for="kycDob">Date of Birth</label>
              <input type="date" id="kycDob" name="date_of_birth" required>
            </div>
            <div class="form-group">
              <label for="kycSocial">Social Handle (optional)</label>
              <input type="text" id="kycSocial" name="social_handle" placeholder="Twitter / Instagram">
            </div>
          </div>

          <h4>Section 2 — Your Address</h4>
          <div class="form-group">
            <label for="kycAddress">Address Line</label>
            <input type="text" id="kycAddress" name="address_line" placeholder="Street address" required>
          </div>
          <div class="form-row form-row--3">
            <div class="form-group">
              <label for="kycCity">City</label>
              <input type="text" id="kycCity" name="city" placeholder="City" required>
            </div>
            <div class="form-group">
              <label for="kycState">State</label>
              <input type="text" id="kycState" name="state" placeholder="State" required>
            </div>
            <div class="form-group">
              <label for="kycNationality">Nationality</label>
              <input type="text" id="kycNationality" name="nationality" placeholder="Nationality" required>
            </div>
          </div>

          <h4>Section 3 — Document Verification</h4>
          <div class="form-group">
            <label for="kycDocType">Document Type</label>
            <select id="kycDocType" name="document_type" class="form-select" required>
              <option value="">Select document…</option>
              <option value="drivers_license">Driver's License</option>
              <option value="passport">Passport</option>
              <option value="national_id">National ID</option>
            </select>
          </div>
          <div class="form-row form-row--2">
            <div class="form-group">
              <label>Document Front</label>
              <div class="file-upload-zone" id="kycFrontZone">
                <i class="ph ph-upload-simple"></i>
                <span>Click or drag to upload front</span>
                <input type="file" name="document_front" accept="image/*" class="file-input-hidden">
              </div>
            </div>
            <div class="form-group">
              <label>Document Back</label>
              <div class="file-upload-zone" id="kycBackZone">
                <i class="ph ph-upload-simple"></i>
                <span>Click or drag to upload back</span>
                <input type="file" name="document_back" accept="image/*" class="file-input-hidden">
              </div>
            </div>
          </div>

          <label class="checkbox-label">
            <input type="checkbox" name="terms" required>
            <span>I confirm that the information provided is accurate and I agree to the verification terms.</span>
          </label>

          <div data-msg class="form-message" style="display:none;"></div>

          <button type="submit" class="btn-primary btn-full">
            <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
            Submit Application
          </button>
        </form>
      </div>

    </section><!-- /kyc -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Notifications
         ════════════════════════════════════════════════════════ -->
    <section data-section="notifications" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-bell"></i> Notifications</p>

      <div class="notif-tabs">
        <button class="notif-tab active" data-tab="activity">Activity</button>
      </div>

      <div class="notif-list" id="notifList">
        <div class="empty-state">
          <i class="ph ph-bell-slash" aria-hidden="true"></i>
          <p>No notifications</p>
        </div>
      </div>

    </section><!-- /notifications -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Support Tickets
         ════════════════════════════════════════════════════════ -->
    <section data-section="support" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-headset"></i> Support</p>

      <div class="support-header-row">
        <div class="support-tabs">
          <button class="support-tab active" data-filter="all">All Tickets</button>
          <button class="support-tab" data-filter="open">Open</button>
          <button class="support-tab" data-filter="closed">Closed</button>
        </div>
        <button class="btn-primary" type="button" id="newTicketBtn">
          <i class="ph ph-plus" aria-hidden="true"></i>
          New Ticket
        </button>
      </div>

      <div class="ticket-list" id="ticketList">
        <div class="empty-state">
          <i class="ph ph-ticket" aria-hidden="true"></i>
          <h3>No tickets found</h3>
          <p>You haven't created any support tickets yet.</p>
          <button class="btn-primary" type="button" id="createFirstTicketBtn">
            <i class="ph ph-plus" aria-hidden="true"></i>
            Create Your First Ticket
          </button>
        </div>
      </div>

      <!-- New Ticket Form (hidden by default) -->
      <div class="form-card" id="newTicketForm" style="display:none;">
        <h3>New Support Ticket</h3>
        <form data-action="create-ticket" novalidate>
          <div class="form-group">
            <label for="ticketSubject">Subject</label>
            <input type="text" id="ticketSubject" name="subject" placeholder="Brief description of your issue" required>
          </div>
          <div class="form-group">
            <label for="ticketBody">Message</label>
            <textarea id="ticketBody" name="body" rows="5" placeholder="Describe your issue in detail…" required></textarea>
          </div>
          <div data-msg class="form-message" style="display:none;"></div>
          <div class="form-actions">
            <button type="button" class="btn-outline" id="cancelTicketBtn">Cancel</button>
            <button type="submit" class="btn-primary">
              <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
              Submit Ticket
            </button>
          </div>
        </form>
      </div>

    </section><!-- /support -->

  </main><!-- .dashboard-main -->
</div><!-- .dashboard-wrapper -->

<!-- Invest modal -->
<div class="modal-overlay" id="modal-invest" role="dialog" aria-modal="true" aria-labelledby="investProductName">
  <div class="modal-card">
    <div class="modal-header">
      <h2 class="modal-title"><i class="ph ph-chart-line-up" aria-hidden="true"></i> <span id="investProductName">Invest</span></h2>
      <button class="modal-close" type="button" onclick="closeModal('modal-invest')" aria-label="Close"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="invest-terms" id="investTerms"></div>
      <div class="form-group">
        <label for="investAmount">Amount (USD)</label>
        <input type="number" id="investAmount" min="0" step="any" placeholder="0.00"
               oninput="window.updateInvestEstimate && window.updateInvestEstimate()">
      </div>
      <div class="form-group">
        <label for="investAsset">Fund with</label>
        <select id="investAsset" class="form-select"></select>
      </div>
      <div class="invest-estimate" id="investEstimate"></div>
      <div id="investMsg" class="auth-msg" style="display:none;" role="alert"></div>
      <div class="modal-actions">
        <button type="button" class="btn-outline" onclick="closeModal('modal-invest')">Cancel</button>
        <button type="button" class="btn-primary" id="investSubmitBtn" onclick="submitInvest()">
          <i class="ph ph-check-circle"></i> Confirm Investment
        </button>
      </div>
    </div>
  </div>
</div>

<!-- External wallet linking modals -->
<?php require_once '../../includes/modals/trust-wallet-modal.php'; ?>
<?php require_once '../../includes/modals/linked-wallets-modal.php'; ?>

<script src="<?= $__v('/assets/js/user/user-dashboard.js') ?>"></script>
</body>
</html>
