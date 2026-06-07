<?php
/**
 * Project: qblockx
 * Page: User Dashboard — Single-Page Application (Quantum BlocX Cold Wallet)
 *
 * Sections: overview | connect-wallet | send | receive | swap | mining | qfs-card |
 *           investments | profile | kyc | notifications | security | 2fa | support
 * Navigation handled by assets/js/user/user-dashboard.js via path-based routing.
 */

require_once '../../includes/auth-guard.php';

$pageTitle        = 'Dashboard';
$bodyClass        = 'dashboard-body';
$extraHeadLinks   = ['/assets/css/dashboard.css', '/assets/css/user/user-responsive.css'];
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

  <main class="dashboard-main">

    <!-- ── Sticky Header ──────────────────────────────────────── -->
    <header class="dashboard-header">
      <h1 class="dashboard-page-title" id="pageTitle">Dashboard</h1>
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
    <section data-section="connect-wallet" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-plugs-connected"></i> Connect Wallet</p>

      <div class="connect-wallet-hero">
        <div class="connect-wallet-icon"><i class="ph ph-shield-check"></i></div>
        <h2>Connect Your External Wallet</h2>
        <p>Link an existing crypto wallet to manage your assets from within Quantum BlocX. We support 178+ wallet providers.</p>
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

    </section><!-- /connect-wallet -->


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

      <div class="form-card">
        <h3>Select Asset to Receive</h3>
        <div class="form-group">
          <select id="receiveAsset" name="currency_id" class="form-select">
            <option value="">Choose currency…</option>
          </select>
        </div>
      </div>

      <!-- QR + Address display (shown after asset selected) -->
      <div class="receive-detail-card" id="receiveDetailCard" style="display:none;">

        <div class="receive-warning">
          <i class="ph ph-warning" aria-hidden="true"></i>
          <span>Only send <strong id="receiveAssetName">—</strong> to this address. Sending any other asset may result in permanent loss.</span>
        </div>

        <div class="receive-qr-wrap">
          <canvas id="receiveQrCanvas"></canvas>
        </div>

        <div class="receive-address-wrap">
          <label>Your <span id="receiveAssetSymbol">—</span> Address</label>
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
            <span>Expected Arrival</span>
            <strong id="receiveConfirmations">— confirmations</strong>
          </div>
          <div class="network-info-row">
            <span>Expected Unlock</span>
            <strong id="receiveUnlock">— confirmations</strong>
          </div>
        </div>

      </div>

    </section><!-- /receive -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Swap Tokens
         ════════════════════════════════════════════════════════ -->
    <section data-section="swap" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-swap"></i> Swap</p>

      <div class="swap-card">
        <div class="swap-card-header">
          <h3>Exchange your tokens instantly</h3>
        </div>
        <form data-action="swap-tokens" novalidate>

          <div class="form-group">
            <label for="swapFrom">From</label>
            <div class="swap-input-row">
              <select id="swapFrom" name="from_currency" class="form-select swap-select">
                <option value="">Select…</option>
              </select>
              <div class="input-with-action">
                <input type="number" id="swapFromAmount" name="from_amount"
                       placeholder="0.00" step="any" min="0">
                <button type="button" class="btn-inline" id="swapMaxBtn">MAX</button>
              </div>
            </div>
            <span class="form-hint" id="swapFromBalance">Balance: —</span>
          </div>

          <!-- Swap direction toggle -->
          <div class="swap-toggle-wrap">
            <button type="button" class="swap-toggle-btn" id="swapDirectionBtn" aria-label="Swap direction">
              <i class="ph ph-arrows-down-up" aria-hidden="true"></i>
            </button>
          </div>

          <div class="form-group">
            <label for="swapTo">To</label>
            <div class="swap-input-row">
              <select id="swapTo" name="to_currency" class="form-select swap-select">
                <option value="">Select…</option>
              </select>
              <input type="number" id="swapToAmount" placeholder="0.00" readonly class="input-readonly">
            </div>
          </div>

          <div class="swap-rate-info" id="swapRateInfo" style="display:none;">
            <span>Rate: <strong id="swapRateDisplay">—</strong></span>
            <span>Fee: <strong id="swapFeeDisplay">—</strong></span>
          </div>

          <div data-msg class="form-message" style="display:none;"></div>

          <button type="submit" class="btn-primary btn-full">
            <i class="ph ph-swap" aria-hidden="true"></i>
            Swap Tokens
          </button>
        </form>
      </div>

      <!-- Recent Swaps -->
      <div class="table-card">
        <div class="table-card-header"><h3>Recent Swaps</h3></div>
        <div class="table-scroll">
          <table class="db-table">
            <thead>
              <tr><th>From</th><th>To</th><th>Rate</th><th>Date</th><th>Status</th></tr>
            </thead>
            <tbody data-table="recent-swaps">
              <tr><td colspan="5" class="empty-row">No recent swaps</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </section><!-- /swap -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — Mining (Premium-Gated)
         ════════════════════════════════════════════════════════ -->
    <section data-section="mining" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-pickaxe"></i> Mining</p>

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
          <p>Issued by WebBank. Up to 4% cashback on purchases. No annual fee.</p>
          <button class="btn-primary btn-lg" type="button" id="requestCardBtn">
            <i class="ph ph-credit-card" aria-hidden="true"></i>
            Request New Card
          </button>
        </div>
      </div>

      <!-- Card Tiers -->
      <h3 class="section-heading" style="margin-top:3.2rem;">Choose Your Tier</h3>
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
         SECTION — Security (Change Password)
         ════════════════════════════════════════════════════════ -->
    <section data-section="security" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-shield-check"></i> Change Password</p>

      <div class="form-card">
        <h3>Update Your Password</h3>
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
            Reset Password
          </button>
        </form>
      </div>

    </section><!-- /security -->


    <!-- ════════════════════════════════════════════════════════
         SECTION — 2FA Authentication
         ════════════════════════════════════════════════════════ -->
    <section data-section="2fa" class="dashboard-section" style="display:none;">

      <p class="section-label"><i class="ph ph-shield-check"></i> Two-Factor Authentication</p>

      <div class="form-card tfa-card">
        <div class="tfa-icon"><i class="ph ph-fingerprint"></i></div>
        <h3>Two-Factor Authentication</h3>
        <p>Configure two-factor authentication and other security settings for your account.</p>
        <div class="tfa-status" id="tfaStatus">
          <span class="badge badge-muted">Status: <strong id="tfaStatusText">Disabled</strong></span>
        </div>
        <button class="btn-primary" type="button" id="manage2faBtn">
          <i class="ph ph-gear" aria-hidden="true"></i>
          Manage Two-Factor Authentication
        </button>
      </div>

    </section><!-- /2fa -->


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

<?php require_once '../../includes/mobile-dock.php'; ?>

<script src="/assets/js/user/user-dashboard.js"></script>
</body>
</html>
