<?php
/**
 * Project: Qblockx
 * Page: /docs/custody — the custody and security document.
 *
 * Written to answer the questions a due-diligence reader asks in their own order: where
 * the keys physically are, the multisig threshold, who can move assets, whether client
 * assets are segregated, what happens if we cease operations, and counterparty risk by
 * name. Where a control is asserted rather than independently evidenced, the page says
 * so; the phrase "counterparty risk" is used verbatim and not softened.
 *
 * Includes use __DIR__ because this file is one level deeper than the other pages.
 */
$pageTitle       = 'Custody & Security';
$pageDescription = 'How Qblockx holds private keys: air-gapped signing environment, multi-signature threshold, who can authorise a movement, segregation of client assets from company assets, business-continuity of client funds, and counterparty risk named and mitigated.';
$pageKeywords    = 'Qblockx custody, cold storage security, multisig 2-of-2, asset segregation, counterparty risk, business continuity';
require_once '../../../includes/head.php';
$navTheme = 'light';
require_once '../../../includes/header.php';
require_once '../../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Documentation</span>
      <h1 class="section-title">Custody &amp; Security</h1>
      <p class="section-subtitle">
        Where your keys are, who can move them, and what happens if we disappear. An
        answer of "not yet established" appears where it is true; a control we cannot
        describe is not a control we claim.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">Contents</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#summary">Summary</a>
            <a href="#where-keys-live">Where keys live</a>
            <a href="#multisig">Multisig setup</a>
            <a href="#authority">Who can move assets</a>
            <a href="#segregation">Segregation</a>
            <a href="#continuity">If we cease operations</a>
            <a href="#counterparty">Counterparty risk</a>
            <a href="#evidence">What evidences this</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="summary">Summary</h2>
          <ul>
            <li>Keys are generated in, and remain in, an air-gapped signing environment. The web application cannot spend from it.</li>
            <li>Movements require multi-signature authorisation. The threshold is described under <a href="#multisig">multisig setup</a>; one compromised credential is not sufficient alone.</li>
            <li>In a storage plan Qblockx has no standing ability to move your assets without your authorisation.</li>
            <li>Client assets are held at addresses derived from your own wallet, not in a pooled company balance.</li>
            <li>Value you deploy (mining, investments, card programme) is a claim on Qblockx and carries counterparty risk.</li>
            <li>No independent audit of these controls is published. Said plainly, not glossed.</li>
          </ul>

          <?php render_entity_block(); ?>

          <h2 id="where-keys-live">Where keys live</h2>
          <p>
            Private key material is held in a <strong>signing environment with no inbound
            network exposure</strong>. There is no listening service for an off-box attacker to
            query and no route from the web tier into key storage. The only artefact that
            leaves is a signed transaction, which becomes public anyway when broadcast.
          </p>
          <p>
            Keys are created in that environment at wallet setup, from a cryptographically
            secure entropy source, and are not transmitted to the connected layer afterwards.
            The connected layer stores public derivation information sufficient to compute
            receiving addresses and show balances. An address and a balance do not confer the
            ability to spend.
          </p>
          <p>
            <strong>Facility and region.</strong> Access to the signing environment is
            restricted to named operators, logged, and separate from the credentials that
            administer the web application: an administrative password reveals the dashboard,
            not the keys.
          </p>
          <!-- MISSING (M9): the brief asks which facility and region the air-gapped signing
               runs in. The codebase records no hosting region or facility, and naming one we
               cannot evidence is exactly the overclaim this document exists to avoid.
               MISSING: hosting provider/region or facility name for the signing layer. -->
          <div class="qbx-openq" data-appear>
            <p class="qbx-openq-title"><i class="ph ph-flask" aria-hidden="true"></i> What we are not claiming</p>
            <p>
              No HSM, certified data centre, or named hardware platform is claimed, because none
              is published for you to verify. "No inbound network exposure" is an architectural
              property you can test from outside by what does and does not respond. A hardware
              certification claim needs a certificate number attached; until it has one, we do
              not make it.
            </p>
          </div>

          <h2 id="multisig">Multisig setup</h2>
          <p>
            A threshold of distinct keys must sign before value leaves the cold layer. The
            purpose is narrow and specific: eliminate the single point of failure, so one lost
            device, one phished operator, or one dishonest employee cannot move assets alone.
          </p>
          <p>
            For an individual wallet the signing set is <strong>2 keys, 2 required</strong>
            (2-of-2): your authorised device or share, and the Qblockx co-signing key. Your
            half is what makes this self-custody; our half exists to make unilateral action by
            <em>us</em> impossible, not to make yours impossible. Because both are required,
            neither party can drain the balance alone while you hold your recovery material.
          </p>
          <p>
            Higher tiers can require a second independent approver before a movement is
            accepted, taking the effective policy to 3-of-3. That is the setting to use when an
            account holds more than you would tolerate losing to one compromised laptop.
          </p>

          <h2 id="authority">Who can move assets, and under what approval</h2>
          <p>This depends on one choice you make at setup, and it is the most consequential sentence on this page:</p>
          <div class="qbx-rails" data-appear>
            <div class="qbx-rail">
              <p class="qbx-rail-kicker">Stronger</p>
              <h3>Recovery phrase kept offline</h3>
              <p>
                You hold your recovery material and never store it with us. Qblockx then holds
                only its co-signing half: we can authorise our side of a transfer you asked for,
                but we cannot complete a movement, and neither can anyone who took our entire
                platform. Your recovery material is what stands between your assets and us.
              </p>
            </div>
            <div class="qbx-rail qbx-rail--alt">
              <p class="qbx-rail-kicker">Convenient</p>
              <h3>Recovery phrase stored with us</h3>
              <p>
                Stored encrypted, never in the clear, and we will never ask you for it. Name the
                trade honestly: you gain recovery without your own backup, and you concede that a
                party holding an encrypted copy of a key is in a stronger position in a disaster
                or dispute than one holding nothing. Your call, made deliberately.
              </p>
            </div>
          </div>
          <ul>
            <li><strong>You can.</strong> You authorise outbound transfers; nothing moves without a signature you caused.</li>
            <li><strong>Qblockx cannot move assets unilaterally</strong> while you hold your recovery material offline. Our co-signing key is one half of a threshold, not the whole of it.</li>
            <li><strong>Qblockx can suspend account access administratively</strong>, for instance during a fraud report. That pauses the interface, not on-chain funds you have already withdrawn, and is a further reason to keep recovery material off our systems.</li>
            <li><strong>Fees are deducted as published</strong> in the <a href="/docs/withdrawals#fees">fee schedule</a>. That is the only deduction taken without a per-transaction instruction.</li>
          </ul>

          <h2 id="segregation">Segregation of client assets</h2>
          <p>
            Assets you deposit to a storage plan sit at addresses derived from your own wallet,
            not in a pooled company balance. That is the substantive difference between
            segregation and a custodial exchange: on a pooled platform your claim is an IOU
            against a shared pot, whereas here your claim is against an address whose spend
            condition includes your key.
          </p>
          <p>
            Company operating funds are held separately from client assets and are not the
            source for client withdrawals; a withdrawal is funded from the client address it
            came from. If that arrangement changes, it changes here first and your plan's terms
            are restated rather than quietly amended.
          </p>

          <h2 id="continuity">What happens to client funds if Qblockx ceases operations</h2>
          <p>
            This question decides whether cold storage means anything, so it gets a direct
            answer rather than reassurance.
          </p>
          <p>
            <strong>Assets in a storage plan:</strong> your recovery shares are sufficient to
            reconstruct your key and sweep the assets to any address you choose, using standard
            wallet software. What disappears is the service, not your ability to reach your
            assets. That is the entire purpose of the architecture, and why your backups must
            not live only with us.
          </p>
          <p>
            <strong>Value deployed in a product:</strong> mining rewards, investment positions
            and card-programme balances are claims against Qblockx. If we cease trading those
            become claims in whatever process follows, alongside our other creditors. They are
            not swept by a recovery phrase.
          </p>
          <p>
            <strong>Not in place:</strong> no published insurance policy, no compensation
            scheme, and no insolvency-remote structure. Assume none until one is documented
            with a policy number you can check.
          </p>

          <h2 id="counterparty">Counterparty risk</h2>
          <p>
            <strong>Counterparty risk</strong> here means, precisely: the risk that Qblockx, the
            operator of this platform, is unable or unwilling to do what your arrangement with
            us requires. It is named rather than softened into "market risk", because market
            risk is your exposure to prices and counterparty risk is your exposure to us. They
            are different risks and only one of them is our problem.
          </p>
          <p>The exposures, each with how it is mitigated:</p>
          <ul>
            <li>
              <strong>Qblockx solvency.</strong> Relevant to deployed value; not to assets
              resting at your own cold addresses.
              <em>Mitigation:</em> deployed value is disclosed as a claim on us before you
              commit, and storage is architected so the largest balance class does not depend on
              our solvency at all.
            </li>
            <li>
              <strong>Qblockx continuity.</strong> If we stop operating, self-custody recovery
              becomes the mechanism and your backups become the only thing between you and a
              loss.
              <em>Mitigation:</em> recovery uses standard wallet software and needs nothing from
              our servers, which is the property described under
              <a href="#continuity">if we cease operations</a>.
            </li>
            <li>
              <strong>Upstream issuers and networks.</strong> Bridged, wrapped or
              issuer-controlled assets carry that party's risk, not ours.
              <em>Mitigation:</em> named at the point of deposit; a bridged asset is disclosed as
              a claim on its issuer.
            </li>
            <li>
              <strong>Card programme partners.</strong> Virtual card balances and cashback follow
              the issuing bank's programme terms, which govern over our interface.
              <em>Mitigation:</em> the programme terms are disclosed as the governing document.
            </li>
            <li>
              <strong>Our own accuracy.</strong> The balances you see are computed by our ledger.
              <em>Mitigation:</em> a discrepancy we caused is resolved in your favour.
            </li>
          </ul>
          <p>
            The residual, stated flatly: there is no third party standing behind this platform.
            No insurer, no compensation scheme, no supervisory regime. What protects stored
            assets is cryptography and your own backups, and what protects nothing else is our
            ability to pay. Sizing a deposit by that distinction is the rational response to
            this paragraph.
          </p>

          <h2 id="evidence">What evidences any of this</h2>
          <p>
            Architecture is a set of claims. The controls worth having are the ones a third
            party can confirm, so this lists what exists rather than what would reassure.
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
                    <span class="qbx-proof-pending">Not in place. Everything on this page is self-described and we say so.</span>
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

          <div class="qbx-related" data-appear>
            <a href="/docs/how-it-works">
              <span class="qbx-related-title">How It Works</span>
              <span class="qbx-related-body">The bridge transfer, settlement, and what backs a balance.</span>
            </a>
            <a href="/docs/withdrawals">
              <span class="qbx-related-title">Withdrawals &amp; Liquidity</span>
              <span class="qbx-related-body">Steps, fees, timing, and the freeze scenario.</span>
            </a>
          </div>

          <?php render_doc_date(); ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once '../../../includes/footer.php'; ?>

</body>
</html>
