<?php
/**
 * Project: Qblockx
 * Page: Withdrawals — the liquidity guide.
 *
 * The question that decides whether money arrives is not "who are you" but "can I get
 * out, how much does it cost, how long, and what if the banks jam". This page answers
 * those with the platform's real numbers, taken from the withdraw flow and fee_schedule
 * rather than invented, so the public page and the dashboard can never disagree:
 *   - processing window: 24-48h (includes/modals/withdraw-modal.php)
 *   - minimums: $10 withdraw / $100 deposit (same files)
 *   - fee by card tier: 2% / 1% / 0% (dbschema fee_schedule withdrawal rows)
 *   - rails: crypto via NOWPayments, or bank SEPA/SWIFT — the crypto path is the
 *     "bank rails frozen" answer because it settles on-chain without a card network.
 */
$pageTitle       = 'Withdrawals & Liquidity';
$pageDescription = 'How to withdraw from Qblockx back to your own wallet: exact steps, fees by plan (2% / 1% / 0%), the 24-48 hour processing time, minimums, mining claim steps, and what happens to withdrawals if bank rails are disrupted.';
$pageKeywords    = 'Qblockx withdrawal, how to withdraw crypto, Qblockx fees, withdrawal time, Trust Wallet withdrawal, liquidity';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Withdrawals &amp; liquidity</span>
      <h1 class="section-title">How you get your money<br>back out</h1>
      <p class="section-subtitle">
        The steps, the exact fee, the processing time, and what happens if the banks
        are under stress. If you only read one page on this site before depositing,
        read this one.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">On this page</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#quick">The short answer</a>
            <a href="#fees">Fees, by plan</a>
            <a href="#two-ways">Two ways out</a>
            <a href="#steps">To your own wallet, step by step</a>
            <a href="#mining">Withdrawing mining rewards</a>
            <a href="#timing">Timing and limits</a>
            <a href="#rails">If bank rails freeze</a>
            <a href="#delays">Why a withdrawal is delayed</a>
            <a href="#faq">Withdrawal FAQ</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="quick">The short answer</h2>
          <ul>
            <li><strong>A QFS Card is required to withdraw.</strong> You cannot withdraw or send assets until you have ordered a QFS Card and paid for it through our NOWPayments checkout. Once the card is active, withdrawals unlock.</li>
            <li><strong>Two rails.</strong> Send crypto straight to your own wallet (Trust Wallet, MetaMask, or any address you control), or take a bank transfer.</li>
            <li><strong>The fee depends only on your plan:</strong> <?= qbx_fee_pct('VirtuElevate') ?>% VirtuElevate, <?= qbx_fee_pct('VirtuElite') ?>% VirtuElite.</li>
            <li><strong>Crypto withdrawals are reviewed and sent within <?= qbx_withdrawal_window() ?>.</strong></li>
            <li><strong>The crypto path needs no bank.</strong> If card and banking rails are jammed, an on-chain transfer still settles. More under
              <a href="#rails">If bank rails freeze</a>.</li>
          </ul>

          <?php render_qfs_requirement(); ?>

          <!-- ── Fee schedule, read from the same source the app uses ── -->
          <h2 id="fees">Fees, by plan</h2>
          <p>
            The withdrawal fee is a flat percentage of what you take out, set by your plan. There
            is no hidden markup and no charge to request a withdrawal that you later cancel before
            it is processed.
          </p>
          <div class="qbx-feetable-wrap" data-appear>
            <table class="qbx-feetable">
              <thead>
                <tr>
                  <th scope="col">Plan</th>
                  <th scope="col">Withdrawal fee</th>
                  <th scope="col">Send to another Qblockx user</th>
                  <th scope="col">Swap</th>
                  <th scope="col">Receive</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (qbx_fee_rows() as $row): ?>
                  <tr<?= $row['highlight'] ? ' class="is-featured"' : '' ?>>
                    <th scope="row"><?= htmlspecialchars($row['tier']) ?></th>
                    <td><?= qbx_fee_cell($row['tier'], 'withdrawal') ?></td>
                    <td><?= qbx_fee_cell($row['tier'], 'send') ?></td>
                    <td><?= qbx_fee_cell($row['tier'], 'swap') ?></td>
                    <td><?= qbx_fee_cell($row['tier'], 'receive') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="qbx-plain-inline">
            On a $10,000 withdrawal that is
            <strong>$<?= number_format(10000 * qbx_fee_pct('VirtuElevate') / 100, 2) ?></strong> on
            VirtuElevate and <strong>$0</strong> on VirtuElite. Network (on-chain) fees, if any
            apply, are shown to you before you confirm; we do not add a network-margin on top.
          </p>

          <h2 id="two-ways">Two ways out</h2>
          <div class="qbx-rails" data-appear>
            <div class="qbx-rail">
              <p class="qbx-rail-kicker">Recommended</p>
              <h3>Crypto to your own wallet</h3>
              <ul>
                <li>Supported: USDT (TRC-20), USDT (ERC-20), BTC, ETH, BNB (BSC)</li>
                <li>Minimum withdrawal: <strong>$<?= qbx_min_withdrawal() ?></strong></li>
                <li>Sent and reviewed within <strong><?= qbx_withdrawal_window() ?></strong></li>
                <li>Needs no bank, no card network, no correspondent bank</li>
              </ul>
              <p class="qbx-rail-note">
                USDT on TRC-20 is the default and usually the fastest and cheapest network. The
                address you paste is a normal public address, the same one you would give anyone
                sending you crypto.
              </p>
            </div>
            <div class="qbx-rail qbx-rail--alt">
              <p class="qbx-rail-kicker">Alternative</p>
              <h3>Bank transfer</h3>
              <ul>
                <li>SEPA and SWIFT to a bank account in your name</li>
                <li>Takes longer than crypto: expect <?= qbx_bank_window() ?></li>
                <li>Subject to your bank and any intermediary bank accepting the transfer</li>
                <li>Uses traditional rails, so it is the rail most exposed to a banking disruption</li>
              </ul>
              <p class="qbx-rail-note">
                Useful when you want fiat in a specific account. If your priority is reaching your
                money fast and outside the banking system, use the crypto path above.
              </p>
            </div>
          </div>

          <h2 id="steps">To your own wallet, step by step</h2>
          <p>This is the "get out back into Trust Wallet" path, in order:</p>
          <ol class="qbx-steps">
            <li>
              <span class="qbx-step-n">1</span>
              <div>
                <strong>Make sure your QFS Card is active.</strong> Withdrawals stay locked until you have
                ordered the card from the <em>QFS Card</em> page and paid for it. Then open your
                Qblockx dashboard wallet and press <em>Withdraw</em>.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">2</span>
              <div>
                <strong>Choose Crypto Wallet</strong> as the method (not Bank Transfer).
              </div>
            </li>
            <li>
              <span class="qbx-step-n">3</span>
              <div>
                <strong>Enter the amount</strong> in USD. Minimum
                $<?= qbx_min_withdrawal() ?>. The fee and the net you will receive are shown
                before you confirm.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">4</span>
              <div>
                <strong>Pick the currency</strong> you want to leave in. For most people that is
                USDT (TRC-20).
              </div>
            </li>
            <li>
              <span class="qbx-step-n">5</span>
              <div>
                <strong>Get your receiving address from Trust Wallet.</strong> Open Trust Wallet,
                choose the matching coin (for USDT, pick the same network, TRC-20), and copy the
                <em>Receive</em> address. Paste it into Qblockx. The network on both sides must
                match.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">6</span>
              <div>
                <strong>Confirm.</strong> The withdrawal is queued for review and sent within
                <?= qbx_withdrawal_window() ?>.
              </div>
            </li>
            <li>
              <span class="qbx-step-n">7</span>
              <div>
                <strong>Watch it land.</strong> Once released, the transfer is on-chain. Open it
                in Trust Wallet, or paste the transaction hash into the relevant block explorer.
                When the network confirms, the balance is yours and moves however you like, with
                no Qblockx involvement.
              </div>
            </li>
          </ol>
          <div class="qbx-plain">
            <span class="qbx-plain-label">The one thing to get right</span>
            <p>
              Network match. Sending USDT on the TRC-20 network to an address that only expects
              ERC-20 can lose the funds permanently. Match the coin <em>and</em> the network on
              both sides. We cannot recover a correctly-executed transfer to the wrong address.
            </p>
          </div>

          <h2 id="mining">Withdrawing mining rewards</h2>
          <p>
            Mining rewards are not a separate withdrawal. A running rig accrues a fixed value per
            day (see the <a href="/mining">mining page</a> for the rate), which you
            <strong>claim</strong> into your Qblockx wallet at any time, and then withdraw from
            that wallet by the normal path above. So getting mining earnings out is two moves:
          </p>
          <ol>
            <li><strong>Claim</strong> the rig's pending rewards into your wallet. This is instant and free.</li>
            <li><strong>Withdraw</strong> from your wallet to your own address, as in
              <a href="#steps">the steps above</a>.</li>
          </ol>
          <p>
            You can claim without withdrawing, and you can withdraw principal without claiming.
            Stopping a rig claims whatever it has earned up to that moment first, so nothing you
            mined is left behind.
          </p>

          <h2 id="timing">Timing and limits</h2>
          <ul>
            <li><strong>Minimum withdrawal:</strong> $<?= qbx_min_withdrawal() ?>.</li>
            <li><strong>Minimum deposit</strong> (for context): $<?= qbx_min_deposit() ?>.</li>
            <li><strong>Crypto review window:</strong> <?= qbx_withdrawal_window() ?> from request to release. After release it is only as fast as the chosen network.</li>
            <li><strong>Bank transfer:</strong> <?= qbx_bank_window() ?>, longer for SWIFT and for first transfers to a new account.</li>
            <li><strong>No exit fee for leaving.</strong> You pay the withdrawal fee above; nothing is charged for closing your account.</li>
          </ul>

          <h2 id="rails">If bank rails freeze</h2>
          <p>
            This is the fair crisis question: if banks jam, correspondent rails freeze, or card
            networks are disrupted, how do you reach your money? The honest answer splits by rail.
          </p>
          <div class="qbx-rails" data-appear>
            <div class="qbx-rail">
              <h3>Crypto withdrawals keep working</h3>
              <p>
                A crypto withdrawal settles on the blockchain. It does not route through a card
                network, a correspondent bank, or a payment clearing house. As long as the Qblockx
                signing layer is releasing transactions, an on-chain withdrawal goes out and lands
                in a wallet you control, independent of your bank.
              </p>
            </div>
            <div class="qbx-rail qbx-rail--alt">
              <h3>Bank transfers share the banking problem</h3>
              <p>
                A SEPA or SWIFT transfer is at the mercy of the same rails under stress, so it is
                the slower path exactly when you least want slow. When the banking system is the
                thing under strain, take crypto out and convert locally.
              </p>
            </div>
          </div>
          <div class="qbx-openq" data-appear>
            <p class="qbx-openq-title"><i class="ph ph-warn-octagon" aria-hidden="true"></i> What we do not claim</p>
            <p>
              We are not claiming that no disruption can ever affect Qblockx. Our signing and
              review layer is operated by us; a platform-wide pause of <em>releases</em> would come
              from us, not from the banking system, and would be announced on this page first.
              What we are saying is narrower and checkable: the crypto exit path does not depend
              on your bank being open.
            </p>
          </div>

          <h2 id="delays">Why a withdrawal is delayed</h2>
          <p>
            Most waits are one of these, and knowing which lets you act:
          </p>
          <ul>
            <li><strong>Security review.</strong> A first withdrawal to a new address, an unusually
              large amount, or a recent password or recovery-phrase change is held for manual
              approval. This is a deliberate brake, not a backlog.</li>
            <li><strong>Network congestion.</strong> The destination blockchain is slow; your funds
              are already released and just confirming.</li>
            <li><strong>Bank side.</strong> For bank transfers, your bank or an intermediary held
              the credit; ask them, referencing the transfer.</li>
            <li><strong>Verification not complete.</strong> Some first withdrawals need identity
              verification finished. Complete it in your profile.</li>
          </ul>
          <p>
            If a crypto withdrawal has not been released after <?= qbx_withdrawal_window() ?>,
            email <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>
            with the date and amount. We will tell you exactly where it is in the flow.
          </p>

          <h2 id="faq">Withdrawal FAQ</h2>
          <div class="faq-list">
            <details class="faq-item">
              <summary>Is there a lock-up or a penalty to withdraw?</summary>
              <div class="faq-body">No lock-up and no exit penalty. You pay only the withdrawal fee for your plan (<?= qbx_fee_pct('VirtuElevate') ?>% VirtuElevate, <?= qbx_fee_pct('VirtuElite') ?>% VirtuElite). Withdrawals need an active, paid QFS Card. Funds you have deployed into a specific investment product follow that product's terms, which are shown before you commit.</div>
            </details>
            <details class="faq-item">
              <summary>How fast is a withdrawal, really?</summary>
              <div class="faq-body">Crypto withdrawals are reviewed and released within <?= qbx_withdrawal_window() ?>. After release, arrival depends only on the blockchain, which is minutes for USDT TRC-20. Bank transfers take <?= qbx_bank_window() ?>.</div>
            </details>
            <details class="faq-item">
              <summary>Can I withdraw to any wallet, or only Trust Wallet?</summary>
              <div class="faq-body">Any wallet or exchange address you control. Trust Wallet is the common choice because many users connect it at the start, but the destination is just a public address and can be anywhere, as long as you pick a supported currency and matching network.</div>
            </details>
            <details class="faq-item">
              <summary>What if I paste the wrong address?</summary>
              <div class="faq-body">A withdrawal you authorised to a valid address is final once broadcast; we cannot reverse an on-chain transfer. Double-check the address and that the network matches. Send a small test first if you are moving a large amount to a new destination.</div>
            </details>
            <details class="faq-item">
              <summary>Do you charge to receive or to deposit?</summary>
              <div class="faq-body">Receiving is free on every plan. Deposits start at $<?= qbx_min_deposit() ?> and arrive through our payment processor; the network fee for the chain you send on is set by that network, not by us.</div>
            </details>
            <details class="faq-item">
              <summary>Can someone stop me from withdrawing?</summary>
              <div class="faq-body">Once your QFS Card is active you can submit a withdrawal at any time. We can administratively suspend <em>account access</em> (for example on a fraud report), which pauses convenience while it is investigated; it is not a seizure of on-chain funds you have already withdrawn. Keeping your own recovery material means you are never dependent on our systems to reach your crypto.</div>
            </details>
          </div>

          <div class="qbx-related" data-appear>
            <a href="/custody">
              <span class="qbx-related-title">Custody and security</span>
              <span class="qbx-related-body">Who can move assets, segregation, and what happens if the company is unavailable.</span>
            </a>
            <a href="/mining">
              <span class="qbx-related-title">Mining, in plain terms</span>
              <span class="qbx-related-body">Cycle length, the daily rate by plan, and how earnings reach your wallet.</span>
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
