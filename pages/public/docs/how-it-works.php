<?php
/**
 * Project: Qblockx
 * Page: /docs/how-it-works — canonical "how it works" document.
 *
 * The compliance brief refers to /docs/* routes. This page is the authoritative
 * version; the flat /how-it-works remains as a short alias to it so the pre-existing
 * URL keeps working. Includes use __DIR__ rather than the repo's usual relative paths
 * because this file sits one level deeper than every other page.
 *
 * Sections the landing-page version did not carry: what actually backs a balance and
 * how it settles, the named withdrawal chains and their verification, and mining cycle
 * length.
 */
$pageTitle       = 'How It Works';
$pageDescription = 'How Qblockx works: what a balance actually is and how it settles, the bridge transfer and offline signing, which chains are supported and how a transfer is verified, and mining cycle length.';
$pageKeywords    = 'Qblockx how it works, cold wallet architecture, air-gapped signing, bridge transfer, mining cycle length, supported chains';
require_once '../../../includes/head.php';
$navTheme = 'light';
require_once '../../../includes/header.php';
require_once '../../../includes/company-blocks.php';
?>

<main>

  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">Documentation</span>
      <h1 class="section-title">How Qblockx works</h1>
      <p class="section-subtitle">
        What "cold storage" means concretely here, what a balance actually is, the exact
        path a transfer takes, and the one question this product has no fixed answer for.
        Written to be checked, not admired.
      </p>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">Contents</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#two-layers">Two layers, one gap</a>
            <a href="#bridge">The bridge transfer</a>
            <a href="#what-backs">What backs a balance</a>
            <a href="#settles">How it settles</a>
            <a href="#chains">Which chains, and verification</a>
            <a href="#cold">What "cold storage" means here</a>
            <a href="#cycle">Mining cycle length</a>
            <a href="#tiers">Storage vs deployed value</a>
          </nav>
        </aside>

        <div class="qbx-prose">

          <h2 id="two-layers">Two layers, one gap</h2>
          <p>
            A wallet is not "cold" because it is slow or inconvenient. It is cold because the
            private key has no network path to a remote attacker. Qblockx splits the system
            into two layers with different jobs and different trust:
          </p>
          <ul>
            <li>
              <strong>The cold layer</strong> holds private keys and signs transactions. It
              exposes no listening network service, so nothing can reach in from outside to
              ask it for a key, because there is nothing there to answer.
            </li>
            <li>
              <strong>The connected layer</strong> is the interface you use: balances,
              history, requests. It holds no private keys, so compromising it exposes data,
              not funds.
            </li>
          </ul>
          <p>
            The two exchange exactly one thing: an <em>unsigned</em> transaction going in, and
            an <em>already-signed</em> transaction coming out. The key moves in neither
            direction.
          </p>
          <div class="qbx-plain">
            <span class="qbx-plain-label">In plain terms</span>
            <p>
              Your online account knows how much you have. It cannot spend it. The part that
              can spend is not reachable from the internet, and the only message it accepts is
              a request you authorised.
            </p>
          </div>

          <h2 id="bridge">The bridge transfer</h2>
          <p>
            "Bridging" is where cold-storage claims usually go vague. This is the sequence a
            transfer actually follows:
          </p>
          <ol>
            <li>
              The connected layer builds an unsigned transaction: from your address, to a
              destination, for an amount, with a fee. Incomplete and harmless.
            </li>
            <li>
              It crosses to the cold layer as data, by one-way presentation. The cold layer
              opens no return channel.
            </li>
            <li>
              The cold layer re-validates against its own view of the ledger: balance covers
              it, destination is well-formed for the chosen network, fee is inside your
              configured ceiling. A tampered request is rejected here, and rejection is the
              safe default.
            </li>
            <li>
              The required signatures are applied inside the cold layer. This is the only step
              that touches a key.
            </li>
            <li>
              The signed transaction crosses back and is broadcast. Signatures do not
              generalise to a different transaction, so what leaves the cold layer cannot be
              edited in transit and remain valid.
            </li>
          </ol>
          <p>
            The security property is that no party needs to be trusted absolutely. The online
            tier can request but cannot compel. Only a signature made in the cold layer moves
            coins, and only the destination chain can confirm the result.
          </p>

          <h2 id="what-backs">What backs a balance</h2>
          <!-- MISSING (M8): the brief asks for "the asset-backed grid": what backs it, how it
               settles. Nothing in this product or its schema is backed by an external asset —
               balances are real on-chain coins under the user's own key, not claims on a pool
               or a backing instrument. Defining a backing asset would mean inventing one.
               MISSING: confirmation of whether an "asset-backed grid" applies to this product
               at all, and if so what backs it. -->
          <p>
            A Qblockx balance is not a claim on a shared pool and not a token backed by
            something else in a vault. It is a real coin or token on its own blockchain, held
            at an address whose spend condition includes your key. When the platform shows
            1.5 BTC, that is 1.5 BTC on the Bitcoin chain, checkable by anyone with the
            address, on a public ledger, without asking us.
          </p>
          <p>
            That distinction is the whole reason this page exists. On a pooled platform your
            balance is an IOU against a pot that might be lent out, and "backed" means the
            operator promises it is there. Here, "backed" would be redundant: the asset is the
            asset, and the only question of substance is who can authorise moving it, which
            <a href="/docs/custody">the custody document</a> answers.
          </p>

          <h2 id="settles">How it settles</h2>
          <p>
            <strong>Deposits</strong> settle when the source blockchain confirms the
            transaction into a block. Qblockx credits after the confirmations that chain
            requires, not on first sight, because a transaction visible in the mempool is not
            yet final. Cold storage is not a barrier on the way in; no signature of yours is
            needed to receive.
          </p>
          <p>
            <strong>Withdrawals</strong> settle outward through the bridge sequence above and
            are final when the destination chain confirms them. Between those moments the
            platform's ledger is a record of what the chains report, not a parallel system of
            credit that has to be redeemed later.
          </p>
          <p>
            The exception, stated for completeness: value you deliberately deploys, mining
            rewards awaiting claim and investment positions, is not a direct on-chain holding.
            It is a claim on Qblockx, with our credit risk attached.
          </p>

          <h2 id="chains">Which chains, and how a transfer is verified</h2>
          <p>Withdrawals move on these networks, matching the product's withdraw flow:</p>
          <ul>
            <li><strong>USDT on Tron (TRC-20)</strong> — default, normally fastest and cheapest.</li>
            <li><strong>USDT on Ethereum (ERC-20)</strong></li>
            <li><strong>Bitcoin (BTC)</strong></li>
            <li><strong>Ethereum (ETH)</strong></li>
            <li><strong>BNB on BNB Smart Chain (BEP-20 / BSC)</strong></li>
          </ul>
          <p>
            A transfer is verified twice, by parties that do not rely on each other. Before
            anything is signed, the cold layer re-checks the request against its own view of
            the chain, and a request that fails is rejected rather than passed through. After
            release, the network verifies it: every node checks the signature, and a replayed
            or edited transaction fails validation and is dropped.
          </p>

          <h2 id="cold">What "cold storage" means here, and where the keys live</h2>
          <p>
            In this product, cold storage means <strong>key material held in a signing
            environment with no inbound network exposure</strong>. Not a metaphor for
            "slow". Not a label for a database with a password on it. Concretely: no
            listening service an off-box attacker can query, and no path from the web tier
            into key storage. The only thing that ever leaves is a signed transaction, which
            is public anyway once broadcast.
          </p>
          <p>
            <strong>Where the cold keys live:</strong> in that air-gapped signing layer,
            created there at wallet setup from a cryptographically secure entropy source, and
            never transmitted to the connected layer afterwards. The connected layer keeps the
            public derivation information it needs to compute your receiving addresses and
            display balances. Knowing an address and a balance confers no ability to spend.
            Access to the signing environment is restricted and logged, and is separate from
            the credentials that administer the web application, so an administrative password
            reveals the dashboard rather than the keys. The full control model, including the
            multisig threshold and the option to keep your phrase entirely offline, is in
            <a href="/docs/custody#where-keys-live">Custody &amp; Security</a>.
          </p>

          <h2 id="cycle">Mining cycle length</h2>
          <!-- MISSING (M7): the brief requires an explicit cycle length ("each cycle runs
               N days"). None exists: mining_sessions has no cycle, term or duration column,
               and api/user-dashboard/mining.php accrues continuously with no expiry. Do not
               publish a number until the team sets one — a stated cycle the code does not
               enforce is the kind of claim that gets money pulled.
               MISSING: mining cycle length in days, or written confirmation there is none. -->
          <p>
            As implemented today there is <strong>no fixed mining cycle</strong>. A rig accrues
            at a fixed rate per day from the moment it starts, and you claim into your wallet
            whenever you choose. There is no N-day term, no maturity date, and nothing that
            has to complete before earnings are yours. Rates are on
            <a href="/mining">the mining page</a>.
          </p>
          <p>
            If a fixed cycle is introduced it will be published here as a number of days
            <em>before</em> it is sold, with the rate table and claim mechanics updated in the
            same change. Until then the accurate answer to "how long is a cycle" is that the
            product has none, and we would rather say that than print a figure the system does
            not enforce.
          </p>

          <h2 id="tiers">Storage versus deployed value</h2>
          <p>
            Your dashboard shows things the landing page does not mention: a premium virtual
            card, mining rewards, investments, gated behind paid tiers. Those are not storage
            tiers. Reading the two sets of claims as one is the commonest way to misunderstand
            this platform.
          </p>
          <div class="section-dark qbx-reconcile" data-appear>
            <span class="section-label" style="color:rgba(211,216,233,0.70);">THE DISTINCTION</span>
            <h2 class="section-title">Custody of your keys is not control of your account</h2>
            <div class="qbx-reconcile-cols">
              <div class="qbx-reconcile-col">
                <p class="qbx-reconcile-kicker">Storage</p>
                <h3>Holding, offline</h3>
                <p>
                  In a storage plan your asset rests in the cold layer. Qblockx does not hold a
                  signing set that can move it without your authorisation.
                </p>
                <p>
                  The trade is control for unreachability. Lose your recovery shares and there
                  is no support desk that can sign for you.
                </p>
              </div>
              <div class="qbx-reconcile-col qbx-reconcile-col--accent">
                <p class="qbx-reconcile-kicker">Products</p>
                <h3>Deployed, on purpose</h3>
                <p>
                  Mining rewards and investments are not cold storage. You elect to place value
                  in a yield-bearing arrangement with Qblockx as counterparty.
                </p>
                <p>
                  Deployed value follows the product's terms, not the protections of the air
                  gap. None of it is a deposit account or government guaranteed.
                </p>
              </div>
            </div>
            <p class="qbx-reconcile-note">
              If you are offered a plan that also earns a return, ask which of these two you are
              buying. The answer determines whether your asset is reachable by us, and it should
              be in writing before you pay.
            </p>
          </div>

          <div class="qbx-related" data-appear>
            <a href="/docs/custody">
              <span class="qbx-related-title">Custody &amp; Security</span>
              <span class="qbx-related-body">Where keys live, multisig thresholds, segregation, counterparty risk.</span>
            </a>
            <a href="/docs/withdrawals">
              <span class="qbx-related-title">Withdrawals &amp; Liquidity</span>
              <span class="qbx-related-body">Steps, fees, processing windows, and the freeze scenario.</span>
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
