<?php
/**
 * Project: Qblockx
 * Page: Mining — the production/cycle FAQ.
 *
 * The question a prospect asks is "is a mining cycle days, weeks, or months?", because
 * a page that says "liquid" but never states a cycle reads as a trap. The app has a real
 * model to describe (api/user-dashboard/mining.php): rigs accrue a fixed USD/day by card
 * tier ($40 VirtuElevate / $75 VirtuElite), you claim to your wallet at any time, up to 6
 * rigs, and stopping a rig claims first. There is no fixed cycle length, and rather than
 * imply one this page says so plainly and gives the honest rate and payback.
 */
$pageTitle       = 'Mining';
$pageDescription = 'How Qblockx mining works: the daily reward rate by plan ($40 VirtuElevate / $75 VirtuElite), there is no fixed cycle length, you claim rewards to your wallet any time, up to 6 rigs, and how earnings get to your own wallet.';
$pageKeywords    = 'Qblockx mining, mining cycle length, mining rewards, Qblockx mining rate, passive crypto mining';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';

// Mining economics — mirror api/user-dashboard/mining.php (miningDailyUsd/tierHashrate).
$qbx_mining = [
    'VirtuElevate' => ['daily' => 40, 'hashrate' => 65],
    'VirtuElite'   => ['daily' => 75, 'hashrate' => 120],
];
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Mining</span>
      <h1 class="section-title">Mining, in plain numbers</h1>
      <p class="section-subtitle">
        How long a cycle is, what it pays, when you can claim, and how the earnings
        reach your own wallet. No "liquid" hand-waving: the rate and the claim
        mechanics are below.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">On this page</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#cycle">The cycle question</a>
            <a href="#rate">What a rig pays</a>
            <a href="#how">How a rig works</a>
            <a href="#claim">Claiming and getting out</a>
            <a href="#who">Who can mine</a>
            <a href="#risk">What "mining" means here</a>
            <a href="#faq">Mining FAQ</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="cycle">The cycle question, answered first</h2>
          <p>
            The most common question is whether a mining cycle is days, weeks or months. The
            honest answer is that Qblockx mining does <strong>not</strong> have a fixed cycle you
            are locked into. A rig keeps earning from the moment you start it, and you
            <strong>claim rewards into your wallet at any time</strong>. There is no 30-day term
            you must sit through, and no maturity date before you can touch what you have earned.
          </p>
          <div class="qbx-plain">
            <span class="qbx-plain-label">In one line</span>
            <p>
              Rewards accrue continuously and you claim whenever you like. "Cycle length" only
              matters as a frame for how long you choose to run a rig, not a lock.
            </p>
          </div>

          <h2 id="rate">What a rig pays</h2>
          <p>
            Each running rig accrues a fixed amount per day, set by your plan, converted into the
            coin you are mining at its current price. You can run up to
            <strong>6 rigs</strong> at once.
          </p>
          <div class="qbx-feetable-wrap" data-appear>
            <table class="qbx-feetable">
              <thead>
                <tr>
                  <th scope="col">Plan</th>
                  <th scope="col">Per rig, per day</th>
                  <th scope="col">Indicative hashrate</th>
                  <th scope="col">6 rigs, per day</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($qbx_mining as $tier => $m): ?>
                  <tr<?= $tier === 'VirtuElite' ? ' class="is-featured"' : '' ?>>
                    <th scope="row"><?= htmlspecialchars($tier) ?></th>
                    <td>$<?= $m['daily'] ?>.00</td>
                    <td><?= $m['hashrate'] ?> TH/s</td>
                    <td>$<?= $m['daily'] * 6 ?>.00</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="qbx-plain-inline">
            Hashrate is shown as an indicative rig spec, not a guarantee of throughput; the reward
            is the per-day figure, not the hashrate. Rewards are credited in the mined coin at its
            price when you claim, so the USD value you see accrue can move with that coin's market
            price.
          </p>

          <h2 id="how">How a rig works</h2>
          <ol class="qbx-steps">
            <li>
              <span class="qbx-step-n">1</span>
              <div><strong>Start a rig</strong> on a coin you want to mine, from the Mining tab.</div>
            </li>
            <li>
              <span class="qbx-step-n">2</span>
              <div><strong>It accrues continuously.</strong> Pending rewards grow from the second you start, at your plan's daily rate.</div>
            </li>
            <li>
              <span class="qbx-step-n">3</span>
              <div><strong>Claim any time.</strong> Claiming credits what you have earned so far into your Qblockx wallet. It is instant and costs nothing.</div>
            </li>
            <li>
              <span class="qbx-step-n">4</span>
              <div><strong>Stop when you like.</strong> Stopping a rig claims its pending rewards first, then closes it. Nothing you mined is left behind.</div>
            </li>
          </ol>

          <h2 id="claim">Claiming and getting out</h2>
          <p>
            Mining earnings are not a separate withdrawal mechanism. They behave like any other
            balance once claimed:
          </p>
          <ol>
            <li><strong>Claim</strong> the rig's rewards into your Qblockx wallet (instant, free).</li>
            <li><strong>Withdraw</strong> from your wallet to your own Trust Wallet or bank, exactly
              as on the <a href="/withdrawals">withdrawals guide</a>, subject to the same fee by
              plan and the <?= qbx_withdrawal_window() ?> review window.</li>
          </ol>
          <p>
            This is deliberate: it means the liquidity answer for mining is the same simple answer
            as for everything else, and there is no special lock on mining earnings that does not
            apply to your balance generally.
          </p>

          <h2 id="who">Who can mine</h2>
          <p>
            Mining is a feature of the premium plans: <strong>VirtuElevate</strong> ($25,000) and
            <strong>VirtuElite</strong> ($35,000). It is not enabled on a standard account. Those
            plans also reduce or remove the transaction and swap fees shown on the
            <a href="/withdrawals#fees">fee table</a>, which is the other side of what the plan is
            for.
          </p>

          <h2 id="risk">What "mining" means here</h2>
          <p>
            We will not pretend this is you running a rig in your garage. Qblockx mining is a
            hosted reward programme: you hold a premium plan, and the plan entitles you to accrue a
            fixed daily reward while a rig is running. Its honest characteristics:
          </p>
          <ul>
            <li>The <strong>reward rate is fixed in USD/day</strong> by plan, but is paid in the
              mined coin, so the market value fluctuates with that coin.</li>
            <li>The programme <strong>depends on Qblockx operating</strong>. Rewards are a claim on
              us while you run, which is a counterparty exposure, not a sovereign asset. See
              <a href="/custody#counterparty">counterparty risk</a>.</li>
            <li>Because you can claim and stop at will, <strong>you are never trapped</strong> by a
              term you cannot exit; the exposure is the plan you chose, not a lock on the earnings.</li>
          </ul>
          <div class="qbx-openq" data-appear>
            <p class="qbx-openq-title"><i class="ph ph-warn-octagon" aria-hidden="true"></i> The straight version</p>
            <p>
              A fixed daily return that you claim on demand is not the same as holding a volatile
              asset, and it is not a deposit with a guaranteed interest rate either. It is a
              hosted programme with a stated rate and an operating counterparty. If a platform
              promises a daily return with no counterparty and no rate risk, that is a reason to
              read the fine print, not a reason to deposit more.
            </p>
          </div>

          <h2 id="faq">Mining FAQ</h2>
          <div class="faq-list">
            <details class="faq-item">
              <summary>Is a mining cycle days, weeks, or months?</summary>
              <div class="faq-body">There is no fixed cycle. A rig accrues continuously and you claim whenever you want. Cycle length is only how long you choose to keep a rig running; there is no minimum term and no maturity wait.</div>
            </details>
            <details class="faq-item">
              <summary>How much does mining pay?</summary>
              <div class="faq-body">$<?= $qbx_mining['VirtuElevate']['daily'] ?>/day per rig on VirtuElevate and $<?= $qbx_mining['VirtuElite']['daily'] ?>/day per rig on VirtuElite, up to 6 rigs. Paid in the mined coin at its price when you claim.</div>
            </details>
            <details class="faq-item">
              <summary>When can I take mining earnings out?</summary>
              <div class="faq-body">Any time. Claim to your wallet instantly, then withdraw to your own address or bank by the normal path on the <a href="/withdrawals">withdrawals guide</a>.</div>
            </details>
            <details class="faq-item">
              <summary>Do I need mining hardware?</summary>
              <div class="faq-body">No. It is a hosted reward programme tied to your plan, not hardware you own. The hashrate figures are indicative rig specs, not equipment shipped to you.</div>
            </details>
            <details class="faq-item">
              <summary>Can mining rewards be paused?</summary>
              <div class="faq-body">Because it is a hosted programme, accrual depends on Qblockx operating normally. Any pause would be announced; your already-claimed balance is unaffected and remains withdrawable.</div>
            </details>
            <details class="faq-item">
              <summary>Is the daily rate guaranteed forever?</summary>
              <div class="faq-body">The rate is set by your plan as stated on this page. Like any programme with a fixed return, it depends on the operator's continued ability to pay; we state the rate rather than promise it beyond our own good-faith operation.</div>
            </details>
          </div>

          <div class="qbx-related" data-appear>
            <a href="/withdrawals">
              <span class="qbx-related-title">Withdrawals &amp; liquidity</span>
              <span class="qbx-related-body">Steps, fees, timing, and the bank-rails-frozen answer.</span>
            </a>
            <a href="/custody">
              <span class="qbx-related-title">Custody and security</span>
              <span class="qbx-related-body">Who can move assets and what happens if we are unavailable.</span>
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
