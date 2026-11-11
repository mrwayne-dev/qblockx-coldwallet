<?php
/**
 * Project: Qblockx
 * Page: Terms of Service.
 *
 * Concise, plain-English terms written to match the custody posture used across the
 * site (self-custody storage vs deployed products, offshore registration, mining as a
 * hosted reward programme). This is the contract of record; it defers specifics to the
 * custody, withdrawal and mining pages rather than restating them and drifting.
 */
require_once '../../includes/company-blocks.php';
$pageTitle       = 'Terms of Service';
$pageDescription = 'The agreement between you and ' . company_get('legal_name') . ' for use of the cold storage platform: accounts, custody, fees, deposits and withdrawals, mining and premium products, liability and termination.';
$pageKeywords    = 'Qblockx terms of service, Qblockx terms, crypto custody terms';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Legal</span>
      <h1 class="section-title">Terms of Service</h1>
      <p class="section-subtitle">
        The agreement between you and the entity named below when you use Qblockx.
        Written to be read; the parts that cost you money or limit our liability are
        in plain words, not hidden.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-prose" data-appear>

        <h2>1. Who this is between</h2>
        <p>
          These terms are between you and
          <strong><?= htmlspecialchars(company_get('legal_name')) ?></strong>
          (<?= htmlspecialchars(company_get('registration_number')) ?>), trading as
          <?= htmlspecialchars(company_get('trading_as')) ?>. By creating an account or using the
          platform you accept them. If you do not agree, do not use the service.
        </p>

        <h2>2. What Qblockx is, and is not</h2>
        <p>
          Qblockx provides cold storage for digital assets and a connected account layer with
          optional products. It is <strong>not a bank</strong>: balances are not deposits, are not
          government guaranteed, and are not covered by any depositor-protection scheme. It is
          <strong>not a licensed custodian or financial adviser</strong>. Assets here are held under
          this contract and the architecture described in
          <a href="/custody">Custody &amp; Security</a>, not under a banking regime.
        </p>

        <h2>3. Accounts and eligibility</h2>
        <ul>
          <li>You must be at least 18 and legally able to enter a contract.</li>
          <li>You are responsible for everything done under your account and for keeping your credentials and recovery material secure.</li>
          <li>We may require identity verification. Some actions, including first withdrawals, may need it complete.</li>
          <li>One person, one account. We may close duplicate or abusive accounts.</li>
        </ul>

        <h2>4. Custody, control, and what we cannot undo</h2>
        <p>
          For assets held in a storage plan, outbound movement requires your authorisation and we
          do not move them without it. That is self-custody, and it cuts both ways:
        </p>
        <ul>
          <li>If you lose your recovery shares or credentials, <strong>we cannot recover the assets for you</strong>. There is no override.</li>
          <li>A withdrawal you authorise to a valid address is final once broadcast. We cannot reverse an on-chain transfer sent to the wrong or a mistaken address.</li>
          <li>We may administratively suspend <em>account access</em>, for example during a fraud investigation. That pauses the interface; it is not a seizure of assets you have already taken on-chain.</li>
        </ul>

        <h2>5. Fees</h2>
        <p>
          Fees are set by your plan and shown on the
          <a href="/withdrawals#fees">fee table</a>. By using a feature you accept its fee. Network
          (on-chain) fees are set by the destination blockchain and, where they apply, are shown to
          you before you confirm. We will give notice before raising a fee; continuing to use the
          feature is acceptance of the new rate.
        </p>

        <h2>6. Deposits and withdrawals</h2>
        <ul>
          <li>Deposits have a stated minimum and are credited when the source network confirms them.</li>
          <li>Withdrawals and sends are available only once you have ordered a QFS Card and paid for it (through our NOWPayments checkout). Until the card is active, withdrawals are locked.</li>
          <li>Withdrawals are reviewed and, for crypto, released within
            <?= qbx_withdrawal_window() ?>; bank transfers take <?= qbx_bank_window() ?>. Times are
            targets, not guarantees, and may extend for security review or network conditions.</li>
          <li>You are responsible for the accuracy of destination addresses and for matching coin
            and network. Mismatched-network transfers may be unrecoverable.</li>
        </ul>

        <h2>7. Premium plans, mining and products</h2>
        <p>
          VirtuElevate and VirtuElite are paid plans that unlock features such as mining and
          investments and reduce transaction fees. <strong>Mining is a hosted reward programme</strong>
          that accrues a fixed rate by plan and is paid in the mined coin; it is not hardware you
          own and not a guaranteed return independent of our ability to operate. Investments and
          other products have their own terms shown before you commit. Value you deploy into a
          product is a claim on Qblockx and carries counterparty risk, distinct from assets resting
          in storage. See <a href="/mining">Mining</a> and
          <a href="/custody#counterparty">counterparty risk</a>.
        </p>

        <h2>8. Prohibited use</h2>
        <p>You will not use Qblockx for:</p>
        <ul>
          <li>Activity that is unlawful in your jurisdiction, or funds from unlawful activity.</li>
          <li>Attempts to defeat, probe maliciously, or place undue load on the platform's security.</li>
          <li>Automated access that abuses the service beyond ordinary personal use.</li>
        </ul>

        <h2>9. Suspension and termination</h2>
        <p>
          You may stop using Qblockx and withdraw your assets at any time, subject to fees and
          verification. We may suspend or close an account for breach of these terms, a legal or
          compliance requirement, or suspected fraud. On closure we will let you withdraw eligible
          assets unless a legal obligation requires us to hold them.
        </p>

        <h2>10. Risk disclosure</h2>
        <p>
          Digital assets are volatile and can lose value. You accept that risk. The fuller set of
          risks, including those we cannot control, is in the
          <a href="/risk">Risk Disclosure</a>, which forms part of these terms.
        </p>

        <h2>11. Our liability</h2>
        <p>
          We provide the service on a reasonable-skills basis. To the maximum extent the law
          allows, we are not liable for losses arising from: your loss of credentials or recovery
          material; transactions you authorised; third-party networks, issuers or counterparties;
          or events outside our reasonable control. Where we are liable, our liability for a claim
          is limited to the fees you paid us in the preceding twelve months, except that nothing
          limits liability we cannot lawfully limit.
        </p>

        <h2>12. Changes</h2>
        <p>
          We may update these terms. Material changes are announced in the app or by email before
          they take effect. Continuing to use Qblockx afterwards means you accept them. The version
          in force is always the one published at this address.
        </p>

        <h2>13. Governing law and contact</h2>
        <p>
          These terms are governed by the laws that apply to the operating
          entity named above, and disputes are subject to the competent courts, without affecting any non-excludable rights you have where you live. Questions:
          <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a><?php if (company_has('compliance_email')): ?>;
          compliance: <a href="mailto:<?= htmlspecialchars(company_get('compliance_email')) ?>"><?= htmlspecialchars(company_get('compliance_email')) ?></a><?php endif; ?>.
        </p>

        <div class="qbx-related" data-appear>
          <a href="/privacy">
            <span class="qbx-related-title">Privacy Policy</span>
            <span class="qbx-related-body">What we collect and do with your data.</span>
          </a>
          <a href="/risk">
            <span class="qbx-related-title">Risk Disclosure</span>
            <span class="qbx-related-body">The risks we cannot prevent.</span>
          </a>
        </div>

      </div>
    </div>
  </section>

</main>

<?php require_once '../../includes/footer.php'; ?>

</body>
</html>
