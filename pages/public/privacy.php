<?php
/**
 * Project: Qblockx
 * Page: Privacy Policy.
 *
 * Plain-language privacy notice. It is specific about the one genuinely sensitive thing
 * this platform stores — the encrypted wallet recovery phrase (APP_KEY "Trust Wallet
 * phrase storage" in .env) and KYC documents — rather than generic, because that is the
 * data a custody customer actually worries about.
 */
$pageTitle       = 'Privacy Policy';
$pageDescription = 'How Qblockx handles your personal data: what we collect, why, the encrypted storage of your wallet recovery phrase and KYC documents, who we share it with, your rights, and how to reach us.';
$pageKeywords    = 'Qblockx privacy policy, crypto data privacy, KYC data, recovery phrase security';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Legal</span>
      <h1 class="section-title">Privacy Policy</h1>
      <p class="section-subtitle">
        What we collect, what we do with it, and the one thing we handle most carefully:
        your wallet data. No dark patterns, no selling your data to strangers.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-prose" data-appear>

        <span class="legal-page-date">
          This policy applies from <?= htmlspecialchars(company_get('docs_updated')) ?>.
        </span>

        <h2>1. Who controls your data</h2>
        <p>
          <?= htmlspecialchars(company_get('legal_name')) ?> (<?= htmlspecialchars(company_get('jurisdiction')) ?>)
          is responsible for the personal data described here. Contact
          <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a><?php if (company_has('compliance_email')): ?>
          or <a href="mailto:<?= htmlspecialchars(company_get('compliance_email')) ?>"><?= htmlspecialchars(company_get('compliance_email')) ?></a><?php endif; ?>.
        </p>

        <h2>2. What we collect</h2>
        <ul>
          <li><strong>Account data:</strong> name, email, password (stored only as a hash), country, phone if you give one.</li>
          <li><strong>Identity (KYC) data:</strong> where required, a government ID and a proof-of-address document, and the result of their verification.</li>
          <li><strong>Wallet data:</strong> the addresses and balances we show you, and transaction records on our ledger.</li>
          <li><strong>Recovery phrase (where you store it with us):</strong> if you use our recovery-phrase storage, the phrase is held <em>encrypted</em>, not in the clear (see section 4).</li>
          <li><strong>Usage data:</strong> standard log data such as device, approximate region, and pages used, to keep the service working and secure.</li>
        </ul>

        <h2>3. Why we use it</h2>
        <ul>
          <li>To run your account and show your balances and history.</li>
          <li>To process deposits, withdrawals and swaps, and to charge fees.</li>
          <li>To meet identity, anti-fraud and legal obligations.</li>
          <li>To reach you about security, and for support.</li>
        </ul>
        <p>
          We do not sell your personal data. We do not use your KYC documents for advertising.
        </p>

        <h2>4. The recovery phrase, specifically</h2>
        <p>
          A recovery phrase controls your assets, so how it is stored matters. When you choose to
          store it on Qblockx, it is encrypted before it is saved and is not readable as ordinary
          text in our database. That reduces the damage of a data breach; it does not make the
          phrase public. You remain responsible for your own backups. We will <strong>never ask you
          for your recovery phrase</strong> by email, chat, or phone, and you should never give it
          to someone who does.
        </p>

        <h2>5. Who we share it with</h2>
        <ul>
          <li><strong>Payment and processing partners</strong> who move funds or run invoices, only the data needed for that job.</li>
          <li><strong>Infrastructure and email providers</strong> who host systems on our behalf under their own confidentiality terms.</li>
          <li><strong>Regulators or courts</strong> where we are legally required to disclose.</li>
        </ul>
        <p>These providers handle data to do their job for us; we do not hand your data to unrelated advertisers.</p>

        <h2>6. International transfers</h2>
        <p>
          Our infrastructure and some partners may sit outside your country. Where that happens we
          take steps to keep your data protected to a standard comparable to the one you started
          under.
        </p>

        <h2>7. How long we keep it</h2>
        <p>
          We keep account and transaction data while your account is open and afterwards as long as
          law or a legitimate need requires (tax and anti-money-laundering records are commonly kept
          for several years). You can ask us to close your account; some records persist for those
          legal reasons.
        </p>

        <h2>8. Your rights</h2>
        <ul>
          <li>Access a copy of your personal data.</li>
          <li>Correct anything inaccurate.</li>
          <li>Ask for erasure where we have no legal reason to retain it.</li>
          <li>Object to or restrict certain processing.</li>
          <li>Ask how data was stored, including the handling of a stored recovery phrase.</li>
        </ul>
        <p>Email us to exercise any of these. We answer genuine requests from real people.</p>

        <h2>9. Cookies</h2>
        <p>
          We use a small number of cookies and similar storage to keep you signed in, remember
          basic preferences, and understand how the site is used. There is no advertising-tracker
          web bolted onto this site.
        </p>

        <h2>10. Security in brief</h2>
        <p>
          Passwords are hashed, your stored recovery phrase is encrypted, and access to systems is
          restricted. No system is impenetrable; where a failure could touch your crypto, the design
          goal is to limit what a breach can reach. See <a href="/custody">Custody &amp; Security</a>.
        </p>

        <h2>11. Changes and contact</h2>
        <p>
          If this policy changes materially we will say so here and, where appropriate, in the app.
          Questions:
          <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>.
        </p>

        <div class="qbx-related" data-appear>
          <a href="/terms">
            <span class="qbx-related-title">Terms of Service</span>
            <span class="qbx-related-body">The contract that governs your use of Qblockx.</span>
          </a>
          <a href="/company">
            <span class="qbx-related-title">Company</span>
            <span class="qbx-related-body">Who we are and how to reach us.</span>
          </a>
        </div>

      </div>
    </div>
  </section>

</main>

<?php require_once '../../includes/footer.php'; ?>

</body>
</html>
