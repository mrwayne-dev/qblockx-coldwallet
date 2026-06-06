<?php
/**
 * Project: Qblockx
 * Page: Homepage
 */
$pageTitle       = 'Home';
$pageDescription = 'Qblockx keeps your private keys completely offline in an air-gapped cold wallet — disconnected from the internet and protected from remote hacks, malware, and phishing attacks.';
$pageKeywords    = 'Qblockx, cold wallet, cold storage, offline crypto storage, air-gapped wallet, private key security, self-custody';
require_once '../../includes/head.php';
?>

<?php require_once '../../includes/header.php'; ?>

<main>

  <!-- ── 1. Hero ──────────────────────────────────────────────────── -->
  <div class="hero-outer">
    <div class="hero-panel">

      <div class="hero-content hero-home" data-appear>

        <!-- Spinning badge -->
        <div class="badge-outer">
          <div class="badge-ring"></div>
          <div class="badge-ring" style="animation-delay:-1s;"></div>
          <div class="badge-ring" style="animation-delay:-2s;"></div>
          <div class="badge-inner">
            <i class="ph ph-shield-check" aria-hidden="true" style="margin-right:6px;"></i>
            Air-gapped cold storage
          </div>
        </div>

        <!-- H1 -->
        <h1 class="hero-h1 hero-h1--xl">
          Your keys,<br>completely offline.
        </h1>

        <!-- Subtext -->
        <p class="hero-subtext">
          Qblockx stores your private keys in an air-gapped cold wallet — disconnected from the internet and shielded from remote hacks, malware, and phishing attacks.
        </p>

        <!-- CTAs -->
        <div class="hero-actions">
          <a href="/register" class="btn-primary">
            Secure My Crypto <i class="ph ph-arrow-right" aria-hidden="true"></i>
          </a>
          <a href="#how-it-works" class="btn-outline-white">
            How It Works
          </a>
        </div>

        <!-- Social proof -->
        <div class="hero-proof">
          <div class="hero-avatars" aria-hidden="true">
            <div class="hero-avatar" style="background:linear-gradient(135deg,#2262FF,#6B99FF);"></div>
            <div class="hero-avatar" style="background:linear-gradient(135deg,#111C3A,#2262FF);"></div>
            <div class="hero-avatar" style="background:linear-gradient(135deg,#3FE0A1,#2262FF);"></div>
          </div>
          <p class="hero-proof-text">Trusted by <strong>12,500+</strong> self-custody holders worldwide</p>
        </div>

        <!-- Stats card — flows naturally centered -->
        <div class="hero-stats-card hero-stats-home">
          <div class="hero-stat">
            <span class="hero-stat-value text-gradient-blue" data-counter="100" data-counter-suffix="%">0</span>
            <span class="hero-stat-label">Keys Held Offline</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat-value text-gradient-blue-rev" data-counter="0">0</span>
            <span class="hero-stat-label">Remote Breaches</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat-value text-gradient-blue" data-counter="500" data-counter-prefix="$" data-counter-suffix="M+">0</span>
            <span class="hero-stat-label">Assets Secured</span>
          </div>
        </div>

      </div>

    </div>
  </div>


  <!-- ── 1b. Logo Carousel ─────────────────────────────────────────── -->
  <section class="logo-carousel-section" aria-label="Trusted by leading institutions">
    <p class="logo-carousel-label">Securing digital assets alongside the industry's most trusted names</p>
    <div class="logo-ticker-wrap" aria-hidden="true">
      <div class="logo-ticker-track">
        <!-- First set -->
        <div class="logo-ticker-item"><i class="ph ph-bank"></i><span>ClearBank</span></div>
        <div class="logo-ticker-item"><i class="ph ph-currency-btc"></i><span>BlockFi</span></div>
        <div class="logo-ticker-item"><i class="ph ph-buildings"></i><span>PropVest</span></div>
        <div class="logo-ticker-item"><i class="ph ph-chart-bar"></i><span>TradeAxis</span></div>
        <div class="logo-ticker-item"><i class="ph ph-shield-check"></i><span>SecureVault</span></div>
        <div class="logo-ticker-item"><i class="ph ph-globe"></i><span>GlobalFund</span></div>
        <div class="logo-ticker-item"><i class="ph ph-coins"></i><span>MetalCore</span></div>
        <div class="logo-ticker-item"><i class="ph ph-trend-up"></i><span>AlphaEdge</span></div>
        <div class="logo-ticker-item"><i class="ph ph-briefcase"></i><span>CapVenture</span></div>
        <div class="logo-ticker-item"><i class="ph ph-diamond"></i><span>PremiumAssets</span></div>
        <!-- Duplicate for seamless loop -->
        <div class="logo-ticker-item"><i class="ph ph-bank"></i><span>ClearBank</span></div>
        <div class="logo-ticker-item"><i class="ph ph-currency-btc"></i><span>BlockFi</span></div>
        <div class="logo-ticker-item"><i class="ph ph-buildings"></i><span>PropVest</span></div>
        <div class="logo-ticker-item"><i class="ph ph-chart-bar"></i><span>TradeAxis</span></div>
        <div class="logo-ticker-item"><i class="ph ph-shield-check"></i><span>SecureVault</span></div>
        <div class="logo-ticker-item"><i class="ph ph-globe"></i><span>GlobalFund</span></div>
        <div class="logo-ticker-item"><i class="ph ph-coins"></i><span>MetalCore</span></div>
        <div class="logo-ticker-item"><i class="ph ph-trend-up"></i><span>AlphaEdge</span></div>
        <div class="logo-ticker-item"><i class="ph ph-briefcase"></i><span>CapVenture</span></div>
        <div class="logo-ticker-item"><i class="ph ph-diamond"></i><span>PremiumAssets</span></div>
      </div>
    </div>
  </section>


  <!-- ── 2. Why Qblockx ────────────────────────────────────────────── -->
  <section class="section" id="why-qblockx" role="region" aria-labelledby="why-title">
    <div class="container">

      <div class="why-bento">

        <!-- Main dark cell -->
        <div class="why-bento-main" data-appear>
          <span class="section-label">WHY QBLOCKX</span>
          <h2 id="why-title" class="section-title">
            Offline by design.<br>Secure by default.
          </h2>
          <p class="section-subtitle">
            Qblockx keeps your private keys in a fully air-gapped environment — never exposed to the internet. The result is custody-grade protection that stays simple enough for anyone to use.
          </p>
          <a href="/register" class="btn-primary" style="align-self:flex-start;">
            Open a Cold Wallet <i class="ph ph-arrow-right" aria-hidden="true"></i>
          </a>
        </div>

        <!-- Benefit cells -->
        <div class="why-bento-cell" data-appear>
          <div class="why-bento-icon"><i class="ph ph-wifi-slash" aria-hidden="true"></i></div>
          <div class="why-bento-title">Air-Gapped Keys</div>
          <p class="why-bento-body">Private keys are generated and stored completely offline — never touching a connected device.</p>
        </div>

        <div class="why-bento-cell" data-appear>
          <div class="why-bento-icon"><i class="ph ph-bug-beetle" aria-hidden="true"></i></div>
          <div class="why-bento-title">Hack &amp; Malware Proof</div>
          <p class="why-bento-body">With no internet exposure, remote hacks, malware, and phishing have nothing to attack.</p>
        </div>

        <div class="why-bento-cell" data-appear>
          <div class="why-bento-icon"><i class="ph ph-key" aria-hidden="true"></i></div>
          <div class="why-bento-title">You Hold the Keys</div>
          <p class="why-bento-body">True self-custody. Only you can authorize a transfer — signed offline, every time.</p>
        </div>

        <div class="why-bento-cell" data-appear>
          <div class="why-bento-icon"><i class="ph ph-arrows-clockwise" aria-hidden="true"></i></div>
          <div class="why-bento-title">Secure Recovery</div>
          <p class="why-bento-body">Encrypted multi-share backups let you restore access without ever exposing your seed online.</p>
        </div>

      </div>

    </div>
  </section>


  <!-- ── 3. How It Works ──────────────────────────────────────────── -->
  <section class="section" id="how-it-works" role="region" aria-labelledby="hiw-title">
    <div class="container">

      <div class="section-header" data-appear>
        <h2 id="hiw-title" class="section-title">How does it work?</h2>
        <p class="section-subtitle" style="margin-top:var(--space-4);">
          Three simple steps to move your crypto into air-gapped cold storage. Setup takes under 10 minutes.
        </p>
      </div>

      <div class="hiw-bento" data-appear>

        <!-- Step 1: wide cell -->
        <div class="hiw-bento-step hiw-bento-step--wide">
          <div class="hiw-bento-num" aria-hidden="true">01</div>
          <h3 class="hiw-bento-title">Create your cold wallet</h3>
          <p class="hiw-bento-body">Sign up in minutes and generate a brand-new wallet whose private keys are created entirely offline. Your keys never touch an internet-connected server — they live in an air-gapped environment from the very first moment.</p>
          <a href="/register" class="how-step-link">Get Started <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- Visual cell: tall accent panel -->
        <div class="hiw-bento-visual" aria-hidden="true">
          <div class="hiw-bento-visual-inner">
            <i class="ph ph-wifi-slash hiw-bento-visual-icon"></i>
            <div class="hiw-bento-visual-stat">100%</div>
            <div class="hiw-bento-visual-label">Keys kept offline</div>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="hiw-bento-step">
          <div class="hiw-bento-num" aria-hidden="true">02</div>
          <h3 class="hiw-bento-title">Deposit your assets</h3>
          <p class="hiw-bento-body">Send crypto to your wallet address. Funds rest in cold storage, fully disconnected from the internet.</p>
          <a href="/register" class="how-step-link">Deposit Now <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- Step 3: accent dark -->
        <div class="hiw-bento-step hiw-bento-step--accent">
          <div class="hiw-bento-num" aria-hidden="true">03</div>
          <h3 class="hiw-bento-title">Sign offline to move</h3>
          <p class="hiw-bento-body">When you transfer out, transactions are signed in the air-gapped layer — your keys never go online.</p>
          <a href="#security" class="how-step-link" style="color:rgba(107,153,255,0.90);">See Security <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
        </div>

      </div>

    </div>
  </section>


  <!-- ── 5. Asset Categories ───────────────────────────────────────── -->
  <section style="background:var(--color-bg); padding:0 0 var(--space-8);" id="assets" role="region" aria-labelledby="assets-title">
    <div class="section-dark" style="padding:var(--space-20) var(--space-6);">
      <div class="container">

        <div style="text-align:center; margin-bottom:var(--space-12);" data-appear>
          <span class="section-label" style="color:rgba(211,216,233,0.70);">SUPPORTED ASSETS</span>
          <h2 id="assets-title" class="section-title" style="color:#fff; margin-top:var(--space-3);">
            Cold storage for every chain
          </h2>
        </div>

        <div class="asset-cards" data-appear>

          <div class="asset-card">
            <div class="asset-card-icon"><i class="ph ph-currency-btc" aria-hidden="true"></i></div>
            <h3 class="asset-card-title">Bitcoin</h3>
            <p class="asset-card-body">Store BTC in deep cold storage with offline key generation and air-gapped transaction signing.</p>
            <a href="/register" class="asset-card-link">Secure BTC <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
          </div>

          <div class="asset-card">
            <div class="asset-card-icon"><i class="ph ph-currency-eth" aria-hidden="true"></i></div>
            <h3 class="asset-card-title">Ethereum &amp; ERC-20</h3>
            <p class="asset-card-body">Keep ETH and any ERC-20 token offline — stablecoins, DeFi assets, and more, all under your keys.</p>
            <a href="/register" class="asset-card-link">Secure ETH <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
          </div>

          <div class="asset-card">
            <div class="asset-card-icon"><i class="ph ph-globe-hemisphere-west" aria-hidden="true"></i></div>
            <h3 class="asset-card-title">Multi-Chain</h3>
            <p class="asset-card-body">Support for major networks including Solana, BNB Chain, Tron, and more — one air-gapped wallet for all.</p>
            <a href="/register" class="asset-card-link">View Networks <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
          </div>

        </div>
      </div>
    </div>
  </section>


  <!-- ── 7. Security & Trust ──────────────────────────────────────── -->
  <section class="section" id="security" role="region" aria-labelledby="security-title">
    <div class="container">

      <div style="text-align:center; margin-bottom:var(--space-12);" data-appear>
        <span class="section-label">SECURITY &amp; ARCHITECTURE</span>
        <h2 id="security-title" class="section-title" style="margin-top:var(--space-3);">
          Air-gapped by architecture
        </h2>
        <p class="section-subtitle" style="max-width:520px; margin:var(--space-4) auto 0;">
          Your private keys are generated and stored in an environment that is physically disconnected from the internet — there is no remote path to your funds.
        </p>
      </div>

      <div class="security-grid" data-appear>

        <div class="security-stat-card">
          <div class="security-stat-icon">
            <i class="ph ph-wifi-slash" aria-hidden="true"></i>
          </div>
          <div class="security-stat-value">100%</div>
          <div class="security-stat-name">Offline Keys</div>
          <p class="security-stat-desc">Private keys never touch a connected device. The air gap removes the entire remote attack surface — hacks, malware, and phishing simply can't reach them.</p>
        </div>

        <div class="security-stat-card security-stat-card--accent">
          <div class="security-stat-icon">
            <i class="ph ph-shield-check" aria-hidden="true"></i>
          </div>
          <div class="security-stat-value">0</div>
          <div class="security-stat-name">Remote Breaches</div>
          <p class="security-stat-desc">Since launch, Qblockx has maintained a perfect record — zero remote breaches, zero key compromises, zero fund losses.</p>
        </div>

        <div class="security-stat-card">
          <div class="security-stat-icon">
            <i class="ph ph-fingerprint" aria-hidden="true"></i>
          </div>
          <div class="security-stat-value">Multi-Sig</div>
          <div class="security-stat-name">Offline Signing</div>
          <p class="security-stat-desc">Every transfer is signed inside the air-gapped layer and protected by configurable multi-signature approval — no single point of failure.</p>
        </div>

      </div>
    </div>
  </section>


  <!-- ── 8. FAQ ─────────────────────────────────────────────────────── -->
  <section class="section" id="faq" role="region" aria-labelledby="faq-title">
    <div class="container">

      <div class="faq-layout">

        <!-- Left: sticky header -->
        <div class="faq-header" data-appear>
          <span class="section-label">FAQ</span>
          <h2 id="faq-title" class="section-title" style="margin-top:var(--space-3);">
            Common questions
          </h2>
          <p class="section-subtitle" style="margin-top:var(--space-4);">
            Everything you need to know about cold storage with Qblockx. Can't find your answer?
          </p>
          <a href="/contact" class="btn-primary" style="margin-top:var(--space-8); display:inline-flex;">
            Ask us directly <i class="ph ph-arrow-right" aria-hidden="true"></i>
          </a>
        </div>

        <!-- Right: accordion -->
        <div class="faq-list" data-appear>

          <details class="faq-item">
            <summary>What is a cold wallet?</summary>
            <div class="faq-body">A cold wallet is a cryptocurrency storage method that keeps your private keys completely offline, disconnected from the internet. This creates an air-gapped environment that protects your digital assets from remote hacks, malware, and phishing attacks.</div>
          </details>

          <details class="faq-item">
            <summary>How are my private keys kept offline?</summary>
            <div class="faq-body">Your keys are generated and stored inside an air-gapped layer that is never connected to the internet. When you move funds, the transaction is signed offline and only the signed result — never the key itself — passes to the online network.</div>
          </details>

          <details class="faq-item">
            <summary>What happens if I lose access to my device?</summary>
            <div class="faq-body">Qblockx uses encrypted multi-share recovery. Your seed is split into encrypted shares so you can restore your wallet without ever exposing the full seed phrase online. Higher tiers add inheritance and multi-signature recovery options.</div>
          </details>

          <details class="faq-item">
            <summary>Which assets can I store?</summary>
            <div class="faq-body">Bitcoin, Ethereum and all ERC-20 tokens, plus major networks like Solana, BNB Chain, and Tron. View the full list in the <a href="#assets" style="color:var(--color-accent);">Supported Assets</a> section.</div>
          </details>

          <details class="faq-item">
            <summary>Do you ever have access to my crypto?</summary>
            <div class="faq-body">No. Qblockx is self-custody — only you hold the keys, and every transfer requires your offline signature. We cannot move, freeze, or access your funds.</div>
          </details>

          <details class="faq-item">
            <summary>How do I get started?</summary>
            <div class="faq-body">Create a free account, generate your air-gapped cold wallet, and deposit your crypto. Your keys are offline from the very first moment — the entire setup takes under 10 minutes.</div>
          </details>

        </div>

      </div>
    </div>
  </section>


  <!-- ── 9. CTA ────────────────────────────────────────────────────── -->
  <section style="background:var(--color-bg); padding:0 0 var(--space-8);" role="region" aria-labelledby="cta-title">
    <div class="section-dark" style="padding:var(--space-20) var(--space-6); text-align:center;">
      <div style="max-width:600px; margin:0 auto;" data-appear>
        <h2 id="cta-title" class="section-title" style="color:#fff; margin-bottom:var(--space-5);">
          Take your crypto offline today
        </h2>
        <p class="section-subtitle" style="color:rgba(255,255,255,0.55); margin-bottom:var(--space-10);">
          Join thousands of holders already securing their assets with Qblockx cold storage.
        </p>
        <div class="cta-actions">
          <a href="/register" class="btn-primary">Create Free Account <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
          <a href="/contact" class="btn-outline-white">Talk to Us</a>
        </div>
      </div>
    </div>
  </section>

</main>

</body>
</html>
