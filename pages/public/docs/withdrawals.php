<?php
/**
 * Project: Qblockx
 * Page: /docs/withdrawals — the liquidity document.
 *
 * The page an actual user needs, so it commits to numbers. Every figure is read from the
 * shared config in includes/company-blocks.php, which mirrors the app's fee_schedule seed
 * and the withdraw/deposit modal minimums, so this document and the user's dashboard can
 * never quote different numbers. Change the app, change QBX_FEES in the same commit.
 *
 * Includes use __DIR__ because this file is one level deeper than the other pages.
 */
$pageTitle       = 'Withdrawals & Liquidity';
$pageDescription = 'How to withdraw from Qblockx to Trust Wallet: numbered steps, the fee formula for withdrawal and deposit, processing windows, lock-up terms, and the realistic path to spendable capital if bank rails freeze.';
$pageKeywords    = 'Qblockx withdrawal guide, withdraw to Trust Wallet, withdrawal fee, processing time, lock-up period, liquidity';
require_once '../../../includes/head.php';
$navTheme = 'light';
require_once '../../../includes/header.php';
require_once '../../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Documentation</span>
      <h1 class="section-title">Withdrawals &amp; Liquidity</h1>
      <p class="section-subtitle">
        The steps out, the fee as a formula, the processing window as a range, and what
        you can actually reach if the banks are under stress. Read this before you deposit,
        not after.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">Contents</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#steps">Steps out to Trust Wallet</a>
            <a href="#fees">Fees, as formulas</a>
            <a href="#timing">Processing time</a>
            <a href="#lockup">Lock-up and notice</a>
            <a href="#freeze">If bank rails freeze</a>
            <a href="#counterparty">Who the counterparty is</a>
            <a href="#delays">Why a withdrawal is delayed</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="steps">Moving funds out to Trust Wallet, step by step</h2>
          <p>
            This is the path from a Qblockx balance to coins sitting in a wallet you control.
            It takes a couple of minutes; the wait is in the processing window, not the form.
          </p>
          <ol class="qbx-steps">
            <li>
              <span class="qbx-step-n">1</span>
              <div>
                <strong>Initiate the withdrawal in your dashboard.</strong> Open
                <em>Wallet</em> and press <em>Withdraw</em>. No approval is needed to ask, and
                there is no gate that stops you asking.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">2</span>
              <div>
                <strong>Choose Crypto Wallet</strong> as the method, rather than Bank Transfer.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">3</span>
              <div>
                <strong>Enter the amount</strong> in USD. Minimum
                $<?= qbx_min_withdrawal() ?>. The fee and the amount you will actually receive
                are shown before you confirm.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">4</span>
              <div>
                <strong>Select the network.</strong> USDT on Tron (TRC-20) is the default and is
                normally the fastest and cheapest. USDT (ERC-20), BTC, ETH and BNB (BSC) are also
                available. The network you pick here must match the network your wallet expects.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">5</span>
              <div>
                <strong>Paste your Trust Wallet address.</strong> In Trust Wallet, choose the
                matching coin, pick the same network, and copy the <em>Receive</em> address into
                Qblockx. Check the first and last characters against what your wallet displays.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">6</span>
              <div>
                <strong>Confirm.</strong> The withdrawal is queued for review and released within
                <?= qbx_withdrawal_window() ?>.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">7</span>
              <div>
                <strong>Wait for on-chain confirmation.</strong> Once released, the transaction
                is on the blockchain. Track it from the transaction hash in your Qblockx history
                or on the relevant block explorer. Confirmation on TRC-20 is typically a matter of
                minutes.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">8</span>
              <div>
                <strong>Funds appear in Trust Wallet.</strong> After confirmation the balance
                shows in your wallet and is entirely yours to move, trade or hold with no further
                Qblockx involvement at all.
              </div>
            </li>
          </ol>
          <div class="qbx-plain">
            <span class="qbx-plain-label">The one thing to get right</span>
            <p>
              Match the coin <em>and</em> the network on both sides. USDT sent on TRC-20 to an
              address that only expects ERC-20 can be lost permanently. A withdrawal you authorised
              to a valid address is final once broadcast; we cannot reverse an on-chain transfer.
              For a large first transfer to a new destination, send a small test amount first.
            </p>
          </div>

          <h2 id="fees">Fees, as formulas</h2>
          <p>
            <strong>Withdrawal fee = withdrawn amount × your plan's withdrawal rate.</strong>
            Rates are flat percentages of the amount you take out, taken from the same fee
            schedule the dashboard charges:
          </p>
          <div class="qbx-feetable-wrap" data-appear>
            <table class="qbx-feetable">
              <thead>
                <tr>
                  <th scope="col">Plan</th>
                  <th scope="col">Withdrawal</th>
                  <th scope="col">Send to a Qblockx user</th>
                  <th scope="col">Swap</th>
                  <th scope="col">Receive</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (qbx_fee_rows() as $row): ?>
                  <tr<?= $row['highlight'] ? ' class="is-featured"' : '' ?>>
                    <th scope="row"><?= htmlspecialchars($row['tier']) ?></th>
                    <td><?= qbx_pct($row['fees']['withdrawal']) ?>%</td>
                    <td><?= qbx_pct($row['fees']['send']) ?>%</td>
                    <td><?= qbx_pct($row['fees']['swap']) ?>%</td>
                    <td><?= qbx_pct($row['fees']['receive']) ?>%</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="qbx-plain-inline">
            Worked example, $10,000 out: Standard
            <strong>$<?= number_format(10000 * qbx_fee_pct('none') / 100, 2) ?></strong>,
            VirtuElevate
            <strong>$<?= number_format(10000 * qbx_fee_pct('VirtuElevate') / 100, 2) ?></strong>,
            VirtuElite <strong>$0.00</strong>.
          </p>
          <p>
            <strong>Deposit fee = 0%.</strong> Receiving costs nothing on any plan. The minimum
            deposit is $<?= qbx_min_deposit() ?>. You still pay the network fee for the chain you
            send on, which is set by that network and charged by it, not by us; we do not add a
            network margin on top. Where a network fee applies to a withdrawal it is shown before
            you confirm.
          </p>

          <h2 id="timing">Processing time, as ranges</h2>
          <p>Total time to your wallet is the internal review window plus on-chain confirmation:</p>
          <ul>
            <li>
              <strong>Crypto withdrawal: <?= qbx_withdrawal_window() ?> internal review and
              release</strong>, then on-chain confirmation on the chosen network (typically
              minutes on TRC-20; longer on congested chains). Realistic total for USDT TRC-20:
              within <?= qbx_withdrawal_window() ?>.
            </li>
            <li><strong>Bank transfer: <?= qbx_bank_window() ?></strong>, longer for SWIFT and for a first transfer to a new account.</li>
            <li><strong>Mining reward claim: instant and free</strong>, into your Qblockx wallet, before the withdrawal clock even starts.</li>
          </ul>
          <p>
            These are working targets, not guarantees. A withdrawal can exceed the window for the
            reasons under <a href="#delays">why a withdrawal is delayed</a>, and the reason is
            always tellable if you ask.
          </p>

          <h2 id="lockup">Lock-up, freeze and notice periods</h2>
          <p>
            <strong>No lock-up period</strong> applies to assets held in a storage plan. There is
            no minimum holding period, no minimum notice to withdraw, no exit penalty, and no fee
            for closing your account. You may withdraw at any time, in whole or in part, subject
            only to the minimum amount and the fee for your plan.
          </p>
          <p>Two limitations that are real and should not be guessed at:</p>
          <ul>
            <li>
              <strong>Value deployed into a specific product</strong> follows that product's own
              terms, which are disclosed before you commit. An investment position may have its
              own maturity; that is a property of the product, not of the storage platform.
            </li>
            <li>
              <strong>We may freeze <em>account access</em></strong>, not your on-chain funds,
              during a fraud investigation or where a legal obligation requires it. Anything
              already withdrawn is unaffected.
            </li>
          </ul>

          <h2 id="freeze">If your bank rails freeze</h2>
          <p>
            The fair crisis question: if banks jam, correspondent rails freeze, or card networks
            are disrupted, how do you reach spendable capital? The honest answer splits by rail.
          </p>
          <div class="qbx-rails" data-appear>
            <div class="qbx-rail">
              <h3>The crypto path keeps working</h3>
              <p>
                <strong>Realistic path:</strong> claim any mining rewards into your wallet
                (instant) → request a crypto withdrawal → released within
                <?= qbx_withdrawal_window() ?> → on-chain confirmation in minutes → sell or spend
                from your own wallet, or cash out through a local peer or an exchange that is
                operating.
              </p>
              <p class="qbx-rail-note">
                This settles on the blockchain. It routes through no card network, no
                correspondent bank and no payment clearing house, so it is the path least exposed
                to a banking disruption.
              </p>
            </div>
            <div class="qbx-rail qbx-rail--alt">
              <h3>Bank transfers share the problem</h3>
              <p>
                A SEPA or SWIFT transfer depends on the very rails that are under strain, so it
                is the slower route exactly when you least want slow. When the banking system is
                the thing failing, take crypto out and convert locally.
              </p>
              <p class="qbx-rail-note">
                Time to spendable capital, crypto path: realistically inside
                <?= qbx_withdrawal_window() ?> plus network confirmation, assuming the market you
                sell into is functioning.
              </p>
            </div>
          </div>

          <h2 id="counterparty">Who the counterparty is in a freeze</h2>
          <p>
            Worth saying in plain language, because it is easy to aim the worry at the wrong
            party. <strong>In a freeze scenario the counterparty you are exposed to is the
            issuer and operator, not your bank.</strong>
          </p>
          <p>
            The bank's role is to deliver a transfer when you use the bank rail. If the banking
            system jams, that rail slows down and your crypto withdrawal is unaffected. What a
            crypto withdrawal <em>does</em> depend on is Qblockx: our signing layer has to be
            operating and our review process has to be releasing transactions. A platform-wide
            pause of <em>releases</em> would come from us, not from the banking system, and would
            be announced here first.
          </p>
          <p>
            Similarly, a stablecoin you hold carries its <strong>issuer's</strong> risk rather
            than a bank's: USDT is a claim on Tether, not a deposit anywhere. So the exposures in
            a freeze are the operator (us), the issuers of the assets you chose, and the market
            you sell into. That is the full list, and it is deliberately not padded with
            reassurance about banks.
          </p>

          <h2 id="delays">Why a withdrawal is delayed</h2>
          <p>Most waits are one of these, and knowing which lets you act:</p>
          <ul>
            <li><strong>Security review.</strong> A first withdrawal to a new address, an unusually large amount, or a recent password or recovery-phrase change is held for manual approval. A deliberate brake, not a backlog.</li>
            <li><strong>Network congestion.</strong> Funds already released and simply confirming.</li>
            <li><strong>Bank side.</strong> Your bank or an intermediary holding a credit; ask them, quoting the transfer.</li>
            <li><strong>Verification incomplete.</strong> Some first withdrawals require identity verification to be finished in your profile.</li>
          </ul>
          <p>
            If a crypto withdrawal has not been released after <?= qbx_withdrawal_window() ?>,
            email <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>
            with the date and amount and we will tell you where it sits in the flow.
          </p>

          <div class="qbx-related" data-appear>
            <a href="/docs/custody#counterparty">
              <span class="qbx-related-title">Counterparty risk</span>
              <span class="qbx-related-body">The risk named in full, with how each exposure is mitigated.</span>
            </a>
            <a href="/compliance">
              <span class="qbx-related-title">Compliance &amp; FAQ</span>
              <span class="qbx-related-body">Entity details, registration, and the answers to all five common questions.</span>
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
