<?php
/**
 * Project: Qblockx
 * Page: Risk Disclosure.
 *
 * The "Security Disclosure" the footer originally pointed at. Named risk for a risk.
 * Repeating the honest risk list is what makes a custody claim believable: a platform
 * that lists only upside reads as marketing, one that lists the ways you can lose reads
 * as someone who expects to be checked.
 */
$pageTitle       = 'Risk Disclosure';
$pageDescription = 'The risks of using Qblockx, named plainly: price volatility, loss of recovery material, finality of transactions, counterparty risk on deployed products, upstream issuer and network risk, and the absence of deposit insurance.';
$pageKeywords    = 'Qblockx risk disclosure, crypto custody risks, digital asset risk warning';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Legal</span>
      <h1 class="section-title">Risk Disclosure</h1>
      <p class="section-subtitle">
        Every way you can lose, written out. If a risk is real, it is here, even where it
        makes us look less reassuring.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">Risks</p>
          <nav class="qbx-doc-toc" aria-label="Risk sections">
            <a href="#before">Read this first</a>
            <a href="#market">Price and market</a>
            <a href="#access">Loss of access</a>
            <a href="#finality">Transaction finality</a>
            <a href="#counterparty">Counterparty</a>
            <a href="#upstream">Upstream and network</a>
            <a href="#no-insurance">No insurance</a>
            <a href="#regulatory">Regulatory</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="before">Read this first</h2>
          <p>
            Digital assets are risky and this is not investment advice. Only commit capital you can
            afford to have locked up, reduced in value, or, in the worst cases, lost. The specific
            risks of doing it through Qblockx are below.
          </p>

          <h2 id="market">Price and market risk</h2>
          <p>
            The value of any asset you hold can fall, quickly and a lot, and can fall to zero. Cold
            storage protects your keys from remote attack; it does not hedge price. A safely stored
            asset can still lose most of its value.
          </p>

          <h2 id="access">Loss of access</h2>
          <p>
            This is the risk self-custody trades for security, and it is real. If you lose your
            recovery shares and your credentials, and no backup works, <strong>stored assets may be
            permanently unrecoverable by anyone, including us.</strong> Keep independent backups of
            your recovery material offline. Do not keep your only copy inside our systems.
          </p>

          <h2 id="finality">Transaction finality</h2>
          <p>
            A withdrawal you authorise is final once it is on-chain. We cannot reverse a transfer
            you sent to the wrong address, or sent on a network the destination does not support.
            Verify address and network before confirming, and send a small test to a new destination
            first.
          </p>

          <h2 id="counterparty">Counterparty risk</h2>
          <p>
            Assets resting in your own cold addresses are reachable through your recovery material
            even if we stop operating. Value you <strong>deploy</strong>, by contrast, is a claim on
            Qblockx:
          </p>
          <ul>
            <li>Mining rewards accrue as a hosted programme that depends on us operating and paying.</li>
            <li>Investment positions and card-programme balances are subject to their terms and to our solvency.</li>
            <li>If Qblockx ceases trading, deployed value becomes a claim alongside our other creditors, not something a recovery phrase sweeps.</li>
          </ul>
          <p>See <a href="/custody#if-we-vanish">what happens if Qblockx stops trading</a>.</p>

          <h2 id="upstream">Upstream issuer and network risk</h2>
          <p>
            Some assets are issued or bridged by third parties. A stablecoin or wrapped/bridged
            token is a claim on its issuer and inherits their risk; a network upgrade, fork, or
            outage can affect an asset independently of us. We describe supported assets, but we do
            not control the networks or issuers behind them.
          </p>

          <h2 id="no-insurance">No deposit insurance</h2>
          <p>
            Balances here are <strong>not deposits</strong>. They are not government guaranteed and
            are not covered by a depositor-protection scheme. No insurance policy is claimed on this
            site unless one is published here with a policy you can check.
          </p>

          <h2 id="regulatory">Regulatory risk</h2>
          <p>
            Rules for digital assets are changing and differ by country. Qblockx is a registered
            company, not a licensed custodian or bank. A change in law where you live could affect
            what you can do with the platform or how it is available to you.
          </p>

          <div class="qbx-openq" data-appear>
            <p class="qbx-openq-title"><i class="ph ph-scales" aria-hidden="true"></i> The short, honest summary</p>
            <p>
              Holding on Qblockx trades some risks for others. The air gap and your own keys remove
              the "someone reaches in and steals it" risk. They leave price risk, the risk you lose
              your backups, the finality of your own mistakes, and, for anything you deploy, our
              risk as the counterparty. That is the deal; we would rather you understand it now than
              discover one piece of it at a withdrawal.
            </p>
          </div>

          <div class="qbx-related" data-appear>
            <a href="/custody">
              <span class="qbx-related-title">Custody and security</span>
              <span class="qbx-related-body">How the architecture reduces the risks listed here.</span>
            </a>
            <a href="/withdrawals">
              <span class="qbx-related-title">Withdrawals &amp; liquidity</span>
              <span class="qbx-related-body">How you get out, the fee, and the timing.</span>
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
