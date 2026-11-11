<?php
/**
 * Project: Qblockx
 * Page: How It Works — the mechanism page.
 *
 * This is the page that has to answer three questions a sceptic asks: what does
 * "cold storage" concretely mean here, how does an asset actually move, and how can
 * the same platform say both "we cannot touch your funds" and "buy a $25,000 card
 * to unlock mining". The last one is answered head-on below rather than avoided.
 */
$pageTitle       = 'How It Works';
$pageDescription = 'How Qblockx cold storage works: air-gapped key generation, the bridge transfer between cold and connected layers, multi-signature offline signing, and what each storage tier actually controls.';
$pageKeywords    = 'Qblockx how it works, cold wallet architecture, air-gapped signing, bridge transfer, multisig cold storage';
require_once '../../includes/head.php';
$navTheme = 'light';
require_once '../../includes/header.php';
require_once '../../includes/company-blocks.php';
?>

<main>

  <!-- ── Hero ───────────────────────────────────────────────────────── -->
  <section class="legal-hero">
    <div class="legal-hero-inner" data-appear>
      <span class="section-label">How it works</span>
      <h1 class="section-title">What "cold storage" means<br>on this platform</h1>
      <p class="section-subtitle">
        The phrase gets used loosely. Below is the specific mechanism Qblockx uses,
        the exact path an asset takes when you move it, and what we do and do not
        control about your funds.
      </p>
    </div>
  </section>

  <!-- ── Body ───────────────────────────────────────────────────────── -->
  <section class="legal-page-section">
    <div class="legal-page-container">
      <div class="qbx-doc-layout">

        <aside class="qbx-doc-aside" data-appear>
          <p class="qbx-doc-aside-title">On this page</p>
          <nav class="qbx-doc-toc" aria-label="Page sections">
            <a href="#two-layers">Two layers, one gap</a>
            <a href="#bridge">The bridge transfer</a>
            <a href="#signing">Signing without connecting</a>
            <a href="#deposit-withdraw">Deposits and withdrawals</a>
            <a href="#tiers">Tiers, mining and investments</a>
            <a href="#responsibility">Who is responsible for what</a>
            <a href="#limits">What this does not protect against</a>
          </nav>
        </aside>

        <!--
          No data-appear on this wrapper: [data-appear] sets opacity:0 until the
          IntersectionObserver adds .is-visible, so a tall article container can
          render blank. Reveal is applied per block instead (hero, TOC, panels).
        -->
        <div class="qbx-prose">

          <h2 id="two-layers">Two layers, one gap</h2>
          <p>
            A wallet is not "cold" because it is slow or inconvenient. It is cold because
            the private key has no network path to a remote attacker. Qblockx separates the
            system into two layers with different jobs and different trust:
          </p>
          <ul>
            <li>
              <strong>The cold layer</strong> holds private keys and signs transactions. It has
              no listening network services. Nothing can reach in from outside to ask it for a key,
              because there is nothing there to answer.
            </li>
            <li>
              <strong>The connected layer</strong> is what you see in your browser: balances,
              history, requests, the interface. It holds no private keys, so compromising it
              exposes data, not funds.
            </li>
          </ul>
          <p>
            The two layers exchange one thing and one thing only: an <em>unsigned</em>
            transaction going in, and an <em>already-signed</em> transaction coming out. The key
            moves in neither direction.
          </p>
          <div class="qbx-plain">
            <span class="qbx-plain-label">In plain terms</span>
            <p>
              Your online account knows how much you have. It cannot spend it. The part that
              can spend is not on the internet, and the only message it accepts is a request
              that you authorised.
            </p>
          </div>

          <h2 id="bridge">The bridge transfer</h2>
          <p>
            "Bridging" is where most cold-storage claims get vague, so here is the actual
            sequence. A bridge transfer moves value between the cold layer and a connected
            context without ever exporting a private key:
          </p>
          <ol>
            <li>
              The connected layer builds an unsigned transaction: from your cold address, to a
              destination, for an amount, with a fee. It is incomplete and harmless.
            </li>
            <li>
              The unsigned transaction crosses to the cold layer as data. One-way presentation;
              the cold layer does not open a return channel.
            </li>
            <li>
              The cold layer re-validates it against its own view of the ledger: correct balance,
              correct destination, fee within your configured ceiling. A tampered request is
              rejected here, and rejection is the safe default.
            </li>
            <li>
              The required signatures are applied inside the cold layer. This is the only step
              that touches a key.
            </li>
            <li>
              The signed transaction crosses back and is broadcast. Signatures are not reusable
              for a different transaction, so what leaves the cold layer cannot be edited
              en route and still be valid.
            </li>
          </ol>
          <div class="qbx-plain">
            <span class="qbx-plain-label">In plain terms</span>
            <p>
              The online side writes a cheque. The offline side checks it, signs it, and hands
              back the signed cheque, never the pen.
            </p>
          </div>

          <h2 id="signing">Signing without connecting</h2>
          <p>
            Every outbound transfer is authorised by a signature produced in the cold layer,
            under a multi-signature policy. Multi-signature means a threshold of separate keys
            must agree before value moves, so no single stolen credential and no single insider
            can drain a balance alone.
          </p>
          <p>
            The specific threshold, which parties hold which keys, and the recovery procedure
            are stated on the
            <a href="/custody">custody and security page</a>, because those facts belong with
            the entity that holds the keys and not with the explanation of the mechanism.
          </p>

          <h2 id="deposit-withdraw">Deposits and withdrawals</h2>
          <p>
            <strong>Depositing</strong> requires no signature at all. You send from wherever you
            already hold the asset to a Qblockx-issued address, and the asset is credited once
            the source network confirms it. Cold storage is not a barrier on the way in.
          </p>
          <p>
            <strong>Withdrawing</strong> is where the cold layer is engaged, following the bridge
            sequence above. Expect this to be slower than an exchange withdrawal: an extra
            validation and signing step is the price of the air gap, not a backlog.
          </p>

          <h2 id="tiers">Tiers, mining and investments</h2>
          <p>
            Your account dashboard shows features that the landing page does not mention: a
            premium virtual card, mining rewards, and an investments section, gated behind paid
            tiers. Those are not storage tiers. They are separate products, and reading the two
            sets of claims as one is the most common way to misunderstand this platform.
          </p>

          <div class="section-dark qbx-reconcile" data-appear>
            <span class="section-label" style="color:rgba(211,216,233,0.70);">THE DISTINCTION</span>
            <h2 class="section-title">Custody of your keys is not the same as control of your account</h2>
            <div class="qbx-reconcile-cols">
              <div class="qbx-reconcile-col">
                <p class="qbx-reconcile-kicker">Storage</p>
                <h3>Holding, offline</h3>
                <p>
                  In a storage plan your asset rests in the cold layer. Qblockx does not hold a
                  key that lets it move that asset without your authorisation, which is what
                  <a href="/terms">our Terms</a> and
                  <a href="/custody">custody policy</a> describe.
                </p>
                <p>
                  The trade you are making is control for unreachability. If you lose your
                  recovery shares, there is no support desk that can sign on your behalf.
                </p>
              </div>
              <div class="qbx-reconcile-col qbx-reconcile-col--accent">
                <p class="qbx-reconcile-kicker">Products</p>
                <h3>Deployed, on purpose</h3>
                <p>
                  Mining rewards and investments are not cold storage. You are electing to put
                  value into a yield-bearing arrangement with Qblockx as the counterparty, which
                  is a different risk than holding.
                </p>
                <p>
                  Once deployed, that value is subject to the terms of the product you bought,
                  not the protections of the air gap. Nothing here is a deposit account and
                  none of it is government guaranteed.
                </p>
              </div>
            </div>
            <p class="qbx-reconcile-note">
              If you are offered a storage plan that also earns a return, ask which of the two
              above you are buying. The honest answer determines whether your asset is reachable
              by us or not, and that answer should be in writing before you pay.
            </p>
          </div>

          <h2 id="responsibility">Who is responsible for what</h2>
          <p>Clear allocation, stated plainly, because most losses are allocation failures:</p>
          <ul>
            <li><strong>You hold</strong> your recovery shares and account credentials. These are what authorise signing.</li>
            <li><strong>You hold</strong> the decision to approve each outbound transaction.</li>
            <li><strong>Qblockx holds</strong> the cold-layer infrastructure, its key-management procedure, and the accuracy of the ledger you see.</li>
            <li><strong>Qblockx does not hold</strong> a standing ability to move your stored assets without your authorisation.</li>
          </ul>
          <p>
            We will never ask for your recovery phrase. Anyone who does, including in a message
            that appears to come from us, is attempting to take your assets.
          </p>

          <h2 id="limits">What this does not protect against</h2>
          <p>Architecture removes some risks and leaves others untouched. It is worth knowing
            which is which:</p>
          <ul>
            <li><strong>Price risk.</strong> The asset you hold can fall in value. Cold storage does not hedge.</li>
            <li><strong>Key loss.</strong> Lose your recovery shares and stored assets are unrecoverable by anyone.</li>
            <li><strong>Upstream failure.</strong> Assets issued or operated by another network or issuer carry that operator's risk, not ours.</li>
            <li><strong>Counterparty risk.</strong> Any deployed, yield-bearing product depends on Qblockx remaining solvent and able to pay. See <a href="/custody">custody and security</a>.</li>
            <li><strong>You, mistaken.</strong> A signed transaction sending to the wrong address is final.</li>
          </ul>

          <div class="qbx-related" data-appear>
            <a href="/custody">
              <span class="qbx-related-title">Custody and security</span>
              <span class="qbx-related-body">Where keys live, who can move assets, and what happens if Qblockx stops trading.</span>
            </a>
            <a href="/company">
              <span class="qbx-related-title">The company behind this</span>
              <span class="qbx-related-body">Registered entity and what you can and cannot verify about us.</span>
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
