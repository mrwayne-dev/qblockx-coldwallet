<?php
/**
 * Project: Qblockx
 * Page: /compliance — entity details, document index, and the five-question FAQ.
 *
 * Consistency requirement: every figure quoted in the FAQ is emitted by the same helper
 * that the documents use (qbx_fee_pct, qbx_withdrawal_window, qbx_min_withdrawal and the
 * mining rate table), so a number cannot drift out of agreement between this page and the
 * document it comes from. Where a fact genuinely does not exist, the FAQ says so rather
 * than asserting a figure.
 */
$pageTitle       = 'Compliance';
$pageDescription = 'Qblockx compliance hub: registered entity, support channel, the custody, how-it-works and withdrawal documents, and direct answers on mining cycles, withdrawals, server location, ownership and segregation.';
$pageKeywords    = 'Qblockx compliance, Qblockx registration, Qblockx entity, Qblockx FAQ, mining cycle, segregation of client assets';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';

// Mining rates — single source of truth is api/user-dashboard/mining.php (miningDailyUsd).
$qbx_mining = ['VirtuElevate' => 40, 'VirtuElite' => 75];
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Compliance</span>
      <h1 class="section-title">Compliance &amp; FAQ</h1>
      <p class="section-subtitle">
        Who operates Qblockx, the documents that define your position, and straight
        answers to the questions people actually ask before depositing.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-prose">

        <h2 id="entity">Registered entity</h2>
        <?php render_entity_block(); ?>
        <p>
          "<?= htmlspecialchars(company_get('trading_as')) ?>" is the trade name;
          <strong><?= htmlspecialchars(company_get('legal_name')) ?></strong> is the legal
          person behind it, operating since <?= htmlspecialchars(company_get('inception_date')) ?>.
          Where a contract, invoice or notice names that entity, it is the party to it. Full
          detail, including what this registration is <em>not</em>, is on the
          <a href="/company">Company page</a>.
        </p>
        <dl class="qbx-proof-grid" data-appear>
          <div class="qbx-proof-item">
            <dt>Support channel</dt>
            <dd>
              <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>
              · <a href="/contact">contact form</a> · live chat on every page
              <?php if (company_has('compliance_email')): ?>
                <br>Compliance:
                <a href="mailto:<?= htmlspecialchars(company_get('compliance_email')) ?>"><?= htmlspecialchars(company_get('compliance_email')) ?></a>
              <?php endif; ?>
            </dd>
          </div>
        </dl>

        <h2 id="documents">Documents</h2>
        <div class="qbx-related" data-appear>
          <a href="/docs/how-it-works">
            <span class="qbx-related-title">How It Works</span>
            <span class="qbx-related-body">What backs a balance, how it settles, the bridge transfer, supported chains, mining cycle length.</span>
          </a>
          <a href="/docs/custody">
            <span class="qbx-related-title">Custody &amp; Security</span>
            <span class="qbx-related-body">Where keys live, multisig threshold, who can move assets, segregation, counterparty risk.</span>
          </a>
          <a href="/docs/withdrawals">
            <span class="qbx-related-title">Withdrawals &amp; Liquidity</span>
            <span class="qbx-related-body">Step-by-step out to Trust Wallet, fee formulas, processing windows, freeze scenario.</span>
          </a>
          <a href="/mining">
            <span class="qbx-related-title">Mining, in plain numbers</span>
            <span class="qbx-related-body">The daily rate by plan, how rewards are claimed, and what the programme is.</span>
          </a>
        </div>
        <p>
          Governing documents: <a href="/terms">Terms of Service</a>,
          <a href="/privacy">Privacy Policy</a>,
          <a href="/risk">Risk Disclosure</a>.
        </p>

        <?php render_verification_gaps(); ?>

        <h2 id="faq">Frequently asked questions</h2>
        <p>
          The five questions that decide whether someone deposits. Numbers below are the same
          ones on the documents they come from.
        </p>

        <div class="faq-list">

          <details class="faq-item">
            <summary>How long does a mining cycle take?</summary>
            <div class="faq-body">
              <strong>There is no fixed cycle.</strong> A rig accrues at a fixed rate per day
              ($<?= $qbx_mining['VirtuElevate'] ?>/day on VirtuElevate,
              $<?= $qbx_mining['VirtuElite'] ?>/day on VirtuElite, up to 6 rigs) from the moment
              you start it, and you claim into your wallet whenever you choose. There is no
              N-day term, no maturity date, and nothing that must complete before earnings are
              yours. Stopping a rig claims whatever it has earned first.
              <!-- MISSING (M7): an explicit cycle length in days is not defined for this
                   product; mining_sessions has no cycle or duration column. If one is
                   introduced, update here and /docs/how-it-works#cycle together. -->
              See <a href="/docs/how-it-works#cycle">mining cycle length</a>.
            </div>
          </details>

          <details class="faq-item">
            <summary>How do I withdraw, and how long does it take?</summary>
            <div class="faq-body">
              <strong>A QFS Card is required first.</strong> You cannot withdraw until you have
              ordered a QFS Card and paid for it through our NOWPayments checkout; it unlocks
              withdrawals once active.
              <br><br>
              Then, from your dashboard: <em>Wallet → Withdraw → Crypto Wallet</em>, enter the amount
              (minimum $<?= qbx_min_withdrawal() ?>), select the network, paste your Trust Wallet
              address, confirm, wait for on-chain confirmation, and the balance appears in your
              wallet. Full steps on the
              <a href="/docs/withdrawals#steps">withdrawals guide</a>.
              <br><br>
              <strong>Timing:</strong> crypto withdrawals are reviewed and released within
              <?= qbx_withdrawal_window() ?>, then confirmed on-chain (typically minutes on
              TRC-20). Bank transfers take <?= qbx_bank_window() ?>.
              <br><br>
              <strong>Fee:</strong> withdrawal is
              <?= qbx_fee_pct('VirtuElevate') ?>%
              VirtuElevate, <?= qbx_fee_pct('VirtuElite') ?>% VirtuElite. Deposits are free on
              every plan. <strong>No lock-up period.</strong>
            </div>
          </details>

          <details class="faq-item">
            <summary>Where are the servers and the cold storage physically?</summary>
            <div class="faq-body">
              The web application and the signing layer are separate systems with separate access
              controls. <strong>The cold keys live in a signing environment with no inbound
              network exposure</strong>: no listening service an off-box attacker can query, and
              no route from the web tier into key storage. Only a signed transaction ever leaves
              it, which is public anyway once broadcast. Administrative credentials reveal the
              dashboard, never the keys.
              <!-- MISSING (M9): hosting provider / region / facility for the signing layer is
                   not recorded in the codebase, and naming one we cannot evidence would be an
                   overclaim. Add the region here and at /docs/custody#where-keys-live together. -->
              We do not publish a named facility, region or hardware certification, because none
              is evidenced; we would rather leave that blank than assert an HSM or a data centre
              we cannot show you. See <a href="/docs/custody#where-keys-live">where keys live</a>.
            </div>
          </details>

          <details class="faq-item">
            <summary>Who registered and owns the entity?</summary>
            <div class="faq-body">
              The platform is operated by <strong><?= htmlspecialchars(company_get('legal_name')) ?></strong>,
              a registered company,
              operating since
              <?= htmlspecialchars(company_get('inception_date')) ?>. That is the legal person you
              contract with and where notices are served.
              <br><br>
              What this is not: it is a company registration, <strong>not a licence to hold client
              assets</strong>. No custody licence number is claimed because none is held. We do
              not publish individual director or shareholder names on this site, so if beneficial
              ownership matters to your decision, ask us at
              <a href="mailto:<?= htmlspecialchars(company_has('compliance_email') ? company_get('compliance_email') : support_email()) ?>"><?= htmlspecialchars(company_has('compliance_email') ? company_get('compliance_email') : support_email()) ?></a>
              before committing sizeable capital. See the
              <a href="/company#status">regulatory status</a> section.
            </div>
          </details>

          <details class="faq-item">
            <summary>Are my funds segregated?</summary>
            <div class="faq-body">
              <strong>Yes, for assets held in a storage plan.</strong> They sit at addresses
              derived from your own wallet, not in a pooled company balance. On a pooled platform
              your balance is an IOU against a shared pot; here your claim is against an address
              whose spend condition includes your key. Company operating funds are held separately
              and are not the source for client withdrawals.
              <br><br>
              The exception, stated honestly: value you deploy into mining or an investment
              product is a claim on Qblockx rather than a segregated on-chain holding, and carries
              counterparty risk. <a href="/docs/custody#segregation">Segregation</a> and
              <a href="/docs/custody#counterparty">counterparty risk</a> in full.
            </div>
          </details>

        </div>

        <?php render_doc_date(); ?>

      </div>
    </div>
  </section>

</main>

<?php require_once '../../includes/footer.php'; ?>

</body>
</html>
