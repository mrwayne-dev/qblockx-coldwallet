<?php
/**
 * Project: Qblockx
 * Page: Company — who operates the platform, and what a reader can check.
 *
 * Answers "who are you", which is the first objection raised anywhere this platform
 * is discussed. Entity facts come from config/company.php so this page, /custody and
 * the footer cannot drift apart, and any fact not yet registered renders as absent
 * instead of as a placeholder.
 */
$pageTitle       = 'Company';
$pageDescription = 'Who operates Qblockx: the registered legal entity, registration number, support channels, and what you can and cannot independently verify about the platform.';
$pageKeywords    = 'Qblockx company, who owns Qblockx, Qblockx registered, Qblockx regulation, crypto company details';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Company</span>
      <h1 class="section-title">Who operates Qblockx</h1>
      <p class="section-subtitle">
        Identity, address, and the honest state of what you can verify. Qblockx is
        the product name; the entity that stands behind it is named below.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">On this page</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#identity">Registered entity</a>
            <a href="#what-we-do">What we do</a>
            <a href="#status">Regulatory status</a>
            <a href="#verify">What you can verify</a>
            <a href="#liquidity">Getting your money out</a>
            <a href="#contact">Contact and address</a>
            <a href="#documents">Governing documents</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="identity">Registered entity</h2>
          <p>
            Qblockx is operated by
            <strong><?= htmlspecialchars(company_get('legal_name')) ?></strong>, a registered
            company. That is the legal person behind the platform: the party to your contract
            and the owner of this domain.
          </p>
          <?php render_entity_block(); ?>
          <p>
            "<?= htmlspecialchars(company_get('trading_as')) ?>" is the trade name of the
            platform described on this site. Where a contract, invoice or notice names
            <?= htmlspecialchars(company_get('legal_name')) ?>, that entity is the party to it.
            Qblockx has operated under this structure since <?= htmlspecialchars(company_get('inception_date')) ?>.
          </p>

          <h2 id="what-we-do">What we do</h2>
          <p>
            Qblockx provides cold storage for digital assets, plus a connected account layer on
            top of it: balances and history, transfers, and optional products such as a virtual
            card programme, mining rewards and investment positions.
          </p>
          <p>
            Storage and the optional products are different arrangements with different risk, and
            the
            <a href="/how-it-works#tiers">how-it-works page</a> draws that line explicitly. It is
            the most useful page on this site if you are deciding whether to deposit.
          </p>

          <h2 id="status">Regulatory status</h2>
          <p>
            <?= htmlspecialchars(company_get('legal_name')) ?> is a registered company, and that
            is exactly what it is: a legal person that exists, can be contracted with and
            taken to court, and appears on a public company register.
          </p>
          <p>
            It is <strong>not</strong> a licence to hold client money, and we will not dress it
            up as one. Many crypto custody platforms register in a light-touch jurisdiction
            because the asset-custody rules there are fit for digital assets without the
            deposit-taking regime that was written for banks. What that means in practice:
          </p>
          <ul>
            <li>You are relying on our contract and on the architecture described in
              <a href="/custody">Custody &amp; Security</a>, not on a supervisory regime.</li>
            <li>Digital assets held here are <strong>not deposits</strong>, are not government
              guaranteed, and are not covered by a depositor-protection scheme.</li>
            <li>There is no separate custody licence number to cite, because we do not hold one.
              We would rather say so than point at a registration and let you assume a licence.</li>
          </ul>
          <p>
            This is the honest posture of a self-custody product. If a platform claims more than
            "registered company, not licensed custodian" without a reference you can look up,
            ask them to produce it.
          </p>

          <h2 id="verify">What you can and cannot verify</h2>
          <p>
            Anybody can write this page. What matters is the part you can check without trusting
            us, so we separate the two: what is verifiable now, and what we have not published
            yet.
          </p>

          <dl class="qbx-proof-grid" data-appear>
            <div class="qbx-proof-item">
              <dt>Registered legal entity</dt>
              <dd>
                <?php if (company_has('legal_name')): ?>
                  <?= htmlspecialchars(company_get('legal_name')) ?>
                  (<?= htmlspecialchars(company_get('registration_number')) ?>). A named
                  counterparty you can contract with.
                <?php else: ?>
                  <span class="qbx-proof-pending">Not yet named. Until it is, treat the operator as unidentified.</span>
                <?php endif; ?>
              </dd>
            </div>
            <div class="qbx-proof-item">
              <dt>Independent audit</dt>
              <dd>
                <?php if (company_has('auditor_name')): ?>
                  <?= htmlspecialchars(company_get('auditor_name')) ?><?php if (company_has('auditor_report_url')): ?>
                  · <a href="<?= htmlspecialchars(company_get('auditor_report_url')) ?>" rel="noopener nofollow">read the report</a>
                  <?php endif; ?>
                <?php else: ?>
                  Not published. Most self-custody platforms at this size have none; the controls
                  on our <a href="/custody">custody page</a> are self-described, so we say so here
                  rather than imply otherwise. Ask us and we will tell you what assurance we do have.
                <?php endif; ?>
              </dd>
            </div>
            <div class="qbx-proof-item">
              <dt>Named customers or backers</dt>
              <dd>
                <?php if (company_has('custody_partner')): ?>
                  Custody: <?= htmlspecialchars(company_get('custody_partner')) ?>
                <?php else: ?>
                  <span class="qbx-proof-pending">None published who will confirm they use us.</span>
                <?php endif; ?>
              </dd>
            </div>
            <div class="qbx-proof-item">
              <dt>Company profile</dt>
              <dd>
                <?php if (company_has('linkedin_url') || company_has('crunchbase_url')): ?>
                  <?php if (company_has('linkedin_url')): ?>
                    <a href="<?= htmlspecialchars(company_get('linkedin_url')) ?>" rel="noopener nofollow">LinkedIn</a>
                  <?php endif; ?>
                  <?php if (company_has('crunchbase_url')): ?>
                    · <a href="<?= htmlspecialchars(company_get('crunchbase_url')) ?>" rel="noopener nofollow">Crunchbase</a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="qbx-proof-pending">Not published yet.</span>
                <?php endif; ?>
              </dd>
            </div>
          </dl>

          <div class="qbx-related" data-appear>
            <a href="/custody">
              <span class="qbx-related-title">Custody and security</span>
              <span class="qbx-related-body">Where keys live, who can move assets, segregation, and what happens if we stop trading.</span>
            </a>
            <a href="/how-it-works">
              <span class="qbx-related-title">How it works</span>
              <span class="qbx-related-body">The mechanism: air-gapped signing and the bridge transfer.</span>
            </a>
          </div>

          <?php render_verification_gaps(); ?>

          <h2 id="contact">Contact and address</h2>
          <p>
            General and support enquiries:
            <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>.
            The
            <a href="/contact">contact form</a> reaches the same team, and live chat is available
            in the corner of every page.
          </p>
          <?php if (company_has('compliance_email')): ?>
            <p>
              Compliance, complaints and records requests:
              <a href="mailto:<?= htmlspecialchars(company_get('compliance_email')) ?>"><?= htmlspecialchars(company_get('compliance_email')) ?></a>.
            </p>
          <?php endif; ?>

          <h2 id="liquidity">Getting your money out</h2>
          <p>
            Who we are matters less than whether you can leave. The single most important page on
            this site is the
            <a href="/withdrawals">withdrawals guide</a>: it gives the exact steps to move funds
            back to your own wallet (Trust Wallet or any other), the precise fee by plan, the
            processing time, and what happens to a withdrawal if bank rails are under stress.
            Read it before you deposit, not after.
          </p>

          <h2 id="documents">Governing documents</h2>
          <ul>
            <li><a href="/terms">Terms of Service</a> — the contract between you and the entity named above.</li>
            <li><a href="/privacy">Privacy Policy</a> — what we collect, especially around identity verification.</li>
            <li><a href="/risk">Risk disclosure</a> — what can go wrong that we cannot prevent.</li>
            <li><a href="/custody">Custody and security</a> — key control, segregation and counterparty risk.</li>
            <li><a href="/how-it-works">How it works</a> — the mechanism behind the claims.</li>
          </ul>

          <?php render_doc_date(); ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once '../../includes/footer.php'; ?>

</body>
</html>
