<?php
/**
 * Project: Qblockx
 * Page: Custody and Security — the document a sceptic is actually looking for.
 *
 * Written to answer the questions a forum thread asks, in the order it asks them:
 * where do keys physically live, what is the multisig threshold, who can move my
 * assets, are my assets mixed with the company's, and what happens to me if the
 * company disappears. Where an answer depends on facts not yet confirmed, the page
 * says so rather than inventing one.
 */
$pageTitle       = 'Custody and Security';
$pageDescription = 'How Qblockx holds private keys: air-gapped key generation, multi-signature thresholds, segregation of client assets from company assets, who can authorise a movement, and counterparty risk if the company ceases trading.';
$pageKeywords    = 'Qblockx custody, cold storage security, multisig, asset segregation, counterparty risk, crypto custody policy';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Custody &amp; security</span>
      <h1 class="section-title">Where your keys are,<br>and who can move them</h1>
      <p class="section-subtitle">
        The uncomfortable questions, answered first. If an answer below is
        "not yet established", that is deliberate: we would rather concede a gap
        than describe a control we cannot show you.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">On this page</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#summary">Summary</a>
            <a href="#key-generation">Key generation</a>
            <a href="#where-keys-live">Where the keys live</a>
            <a href="#multisig">Multi-signature policy</a>
            <a href="#authority">Who can move assets</a>
            <a href="#segregation">Asset segregation</a>
            <a href="#if-we-vanish">If Qblockx vanishes</a>
            <a href="#counterparty">Counterparty risk, named</a>
            <a href="#verification">What proves any of this</a>
            <a href="#incident">Reporting a concern</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="summary">Summary, before the detail</h2>
          <ul>
            <li>Private keys are generated offline and are not held in a form the web application can spend from.</li>
            <li>Outbound movements require multi-signature authorisation inside the cold layer; a single compromised credential is not sufficient on its own.</li>
            <li>In a storage plan, Qblockx has no standing ability to move your assets without your authorisation.</li>
            <li>We are a self-custody platform <em>with a co-signing key</em>: you can keep your recovery phrase entirely offline, or store it with us encrypted for convenience. The trade-off between those two is set out under
              <a href="#authority">who can move assets</a> and stated honestly, not buried.</li>
            <li>In a deployed product (mining, investments, premium card arrangements), you have accepted Qblockx as a counterparty and that value carries our credit risk.</li>
            <li>There is currently no independent audit of these controls published. That is stated below, not glossed.</li>
          </ul>

          <?php render_entity_block(); ?>

          <h2 id="key-generation">Key generation</h2>
          <p>
            Keys are created in the cold layer at wallet creation, from a cryptographically
            secure entropy source, and are not transmitted to the connected layer afterwards.
            The connected layer stores the public derivation path needed to compute your
            receiving addresses and to display balances. Knowing an address and a balance does
            not confer the ability to spend.
          </p>
          <p>
            Your recovery material is split into encrypted shares rather than a single seed
            phrase, so that no one copy of one backup is sufficient to reconstruct the key.
            Restore is performed in the cold layer; the reconstructed key is not pushed back
            online as part of recovery.
          </p>

          <h2 id="where-keys-live">Where the keys live</h2>
          <p>
            The cold layer is a signing environment with no inbound network exposure: no
            listening service an off-box attacker can query, and no path from the web tier into
            key storage. Material leaving it is a signed transaction, which is public anyway
            once broadcast.
          </p>
          <p>
            Access to the signing environment is itself restricted and logged, and is separate
            from the credentials used to administer the web application. An attacker who
            obtains an admin password sees the dashboard, not the keys.
          </p>
          <div class="qbx-openq" data-appear>
            <p class="qbx-openq-title"><i class="ph ph-flask" aria-hidden="true"></i> What we are not claiming</p>
            <p>
              We are not claiming an HSM, a certified data centre, or a specific hardware
              platform, because we have not published one that you can check. "No inbound
              network exposure" is an architectural property you can test from the outside by
              what does and does not respond. A hardware certification claim needs a certificate
              number attached to it, and until it has one we do not make it.
            </p>
          </div>

          <h2 id="multisig">Multi-signature policy</h2>
          <p>
            A threshold of distinct keys must sign before value leaves the cold layer. The
            purpose is narrow and specific: it removes the single point of failure, so that one
            lost device, one phished operator, or one dishonest employee cannot unilaterally
            move assets.
          </p>
          <p>
            For an individual wallet, the signing set is your own authorised device or share
            plus the Qblockx co-signing key. Your share alone is enough to move your assets
            where you configured it that way, which is the point of self-custody; our co-signing
            key exists to make unilateral action by <em>us</em> impossible, not to make yours
            impossible.
          </p>
          <p>
            Higher tiers can require a second independent approver before any movement is
            accepted, which is the setting to use if the account holds more than you would
            tolerate losing to one compromised laptop.
          </p>

          <h2 id="authority">Who can move assets</h2>
          <p>
            This depends on one choice you make at setup, and it is the most important sentence on
            this page, so it is not hedged.
          </p>
          <div class="qbx-rails" data-appear>
            <div class="qbx-rail">
              <p class="qbx-rail-kicker">Stronger</p>
              <h3>Phrase kept offline</h3>
              <p>
                You keep your recovery material and never store it with us. Then Qblockx holds only
                its co-signing half: we can authorise our side of a transfer you asked for, but we
                cannot complete a movement, and neither can anyone who steals our whole platform.
                Your recovery material is the thing that stands between your assets and us, so keep
                it off our systems and off your email.
              </p>
            </div>
            <div class="qbx-rail qbx-rail--alt">
              <p class="qbx-rail-kicker">Convenient</p>
              <h3>Phrase stored with us</h3>
              <p>
                If you store your phrase with Qblockx, it is held encrypted, never in the clear, and
                we will never ask you for it. Be honest about the trade: you gain the ability to
                restore without your own backup and lose none of the service, but a party holding an
                encrypted copy of a key has a stronger position in a disaster or a dispute than one
                holding nothing. That is the cost of the convenience, and it is your call to make.
              </p>
            </div>
          </div>
          <ul>
            <li><strong>You can.</strong> You authorise outbound transfers; nothing moves without a signature you caused.</li>
            <li><strong>Qblockx cannot move assets unilaterally</strong> while you hold your recovery material offline. Our co-signing key is one half of a threshold, not the whole of it.</li>
            <li><strong>Qblockx can restrict an account administratively.</strong> We can suspend account access, for example on a fraud report. That freezes convenience, not your assets, and is a further reason to keep your own recovery material off our systems.</li>
            <li><strong>Withdrawal and swap fees are deducted</strong> as published in the
              <a href="/withdrawals#fees">fee schedule</a>; that is the one deduction we take without a per-transaction instruction.</li>
          </ul>

          <h2 id="segregation">Asset segregation</h2>
          <p>
            Assets you deposit to a storage plan sit at addresses derived from your own wallet,
            not in a pooled company balance. That is the substantive difference between
            segregation and a custodial exchange: on a pooled platform your claim is an IOU
            against a shared pot, and here your claim is against an address whose spend
            condition includes your key.
          </p>
          <p>
            Company operating funds are held separately from client assets and are not used as
            the source for client withdrawals. If that arrangement changes, it changes here
            first and your plan's terms are restated rather than quietly amended.
          </p>

          <h2 id="if-we-vanish">If Qblockx stops trading</h2>
          <p>
            This is the question that decides whether cold storage means anything, so it gets a
            direct answer rather than a reassurance.
          </p>
          <p>
            <strong>For assets in a storage plan:</strong> your recovery shares are sufficient to
            reconstruct your key and sweep the assets to any address you choose, using standard
            wallet software. The Qblockx service is what disappears, not your ability to reach
            your assets. That is the entire reason the architecture is built the way it is, and
            it is why your backups must not live only with us.
          </p>
          <p>
            <strong>For value deployed in a product:</strong> mining rewards, investment
            positions and card-programme balances are claims against Qblockx. If we cease
            trading, those become claims in whatever process follows, alongside our other
            creditors. They are not swept by a recovery phrase.
          </p>
          <p>
            <strong>What is not in place yet:</strong> there is no published insurance policy or
            compensation scheme covering digital assets held here, and no insolvency-remote
            structure has been published. Readers should assume none until one is documented
            with a policy number they can check.
          </p>

          <h2 id="counterparty">Counterparty risk, named</h2>
          <p>Risks that depend on a party behaving correctly, stated as risks:</p>
          <ul>
            <li><strong>Qblockx solvency.</strong> Relevant to deployed value, not to assets resting in your own cold addresses.</li>
            <li><strong>Qblockx continuity.</strong> If we stop operating, self-custody recovery becomes the mechanism and your backups become the only thing standing between you and a loss.</li>
            <li><strong>Upstream issuers and networks.</strong> Assets issued or bridged by third parties carry those parties' risk. A bridged or wrapped asset is a claim on its issuer.</li>
            <li><strong>Card programme partners.</strong> Virtual card balances and cashback are subject to the issuing bank's programme terms, which govern, not our interface.</li>
            <li><strong>Our own accuracy.</strong> Balances you see are computed by our ledger. A discrepancy is resolved by us in your favour where we caused it.</li>
          </ul>

          <h2 id="verification">What proves any of this</h2>
          <p>
            Architecture is a set of claims. The controls worth having are the ones a third
            party can confirm, so we list what exists rather than what would sound reassuring.
          </p>

          <dl class="qbx-proof-grid" data-appear>
            <?php
            $proof = [
                ['Independent audit of reserves and controls',
                 company_has('auditor_name') ? company_get('auditor_name') : null,
                 company_has('auditor_report_url') ? company_get('auditor_report_url') : ''],
                ['Qualified custody partner',
                 company_has('custody_partner') ? company_get('custody_partner') : null, ''],
                ['Registered entity and number',
                 company_has('legal_name') ? company_get('legal_name') : null, ''],
                ['Company profile you can cross-reference',
                 company_has('linkedin_url') ? 'LinkedIn' : (company_has('crunchbase_url') ? 'Crunchbase' : null),
                 company_has('linkedin_url') ? company_get('linkedin_url') : company_get('crunchbase_url')],
            ];
            foreach ($proof as [$label, $value, $url]): ?>
              <div class="qbx-proof-item">
                <dt><?= htmlspecialchars($label) ?></dt>
                <dd>
                  <?php if ($value === null): ?>
                    <span class="qbx-proof-pending">Not yet in place. Ask us for a status.</span>
                  <?php elseif ($url !== ''): ?>
                    <a href="<?= htmlspecialchars($url) ?>" rel="noopener nofollow"><?= htmlspecialchars($value) ?></a>
                  <?php else: ?>
                    <?= htmlspecialchars($value) ?>
                  <?php endif; ?>
                </dd>
              </div>
            <?php endforeach; ?>
          </dl>

          <?php render_verification_gaps(); ?>

          <h2 id="incident">Reporting a concern</h2>
          <p>
            If you believe an unauthorised movement has occurred, or you have identified a
            weakness in what is described here, contact
            <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>
            with the wallet address and the approximate time. Security reports are read by the
            people who operate the signing environment, not routed through a queue.
          </p>
          <p>
            We do not offer a paid bounty programme and we do not promise a fix timeline we
            cannot keep. We do promise a reply from a person, and a correction to this page when
            we were wrong.
          </p>

          <div class="qbx-related" data-appear>
            <a href="/how-it-works">
              <span class="qbx-related-title">How it works</span>
              <span class="qbx-related-body">The bridge transfer and offline signing mechanism this policy describes.</span>
            </a>
            <a href="/company">
              <span class="qbx-related-title">The company behind this</span>
              <span class="qbx-related-body">Registered entity and the verification gap.</span>
            </a>
          </div>

          <?php render_doc_date(); ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once '../../includes/footer.php'; ?>

</body>
</html>
