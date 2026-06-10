(function () {
  'use strict';

  // ── Helpers ──────────────────────────────────────────────────────────────────

  async function apiFetch(url, opts) {
    opts = opts || {};
    var headers = { 'X-Requested-With': 'XMLHttpRequest' };
    if (!(opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
    var res = await fetch(url, Object.assign({ headers: headers }, opts));
    return res.json();
  }

  function fmt(num, dec) {
    return parseFloat(num || 0).toLocaleString('en-US', {
      minimumFractionDigits: dec != null ? dec : 2,
      maximumFractionDigits: dec != null ? dec : 2
    });
  }

  function fmtCrypto(num) {
    var n = parseFloat(num || 0);
    if (n === 0) return '0.00000000';
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 });
  }

  function fmtDate(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function badge(status) {
    var map = {
      completed: 'badge-success', active: 'badge-success', approved: 'badge-success',
      pending: 'badge-warning', confirming: 'badge-warning',
      rejected: 'badge-error', failed: 'badge-error',
      cancelled: 'badge-muted', expired: 'badge-muted'
    };
    return '<span class="badge ' + (map[status] || 'badge-muted') + '">' + (status || '—') + '</span>';
  }

  function showMsg(form, msg, isError) {
    var el = form.querySelector('[data-msg]');
    if (!el) return;
    el.textContent = msg;
    el.className = isError ? 'form-message form-message--error' : 'form-message form-message--success';
    el.style.display = 'block';
    setTimeout(function () { el.style.display = 'none'; }, 5000);
  }

  function qs(sel) { return document.querySelector(sel); }
  function setText(sel, val) { var el = qs(sel); if (el) el.textContent = val; }

  function copyText(text, cb) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { cb(true); }).catch(function () { cb(false); });
    } else {
      try {
        var ta = document.createElement('textarea');
        ta.value = text; ta.style.cssText = 'position:fixed;top:-9999px;opacity:0';
        document.body.appendChild(ta); ta.focus(); ta.select();
        var ok = document.execCommand('copy'); document.body.removeChild(ta); cb(ok);
      } catch (e) { cb(false); }
    }
  }

  // ── Toast System ─────────────────────────────────────────────────────────────

  function showToast(msg, type) {
    var c = document.getElementById('toastContainer');
    if (!c) return;
    var t = document.createElement('div');
    t.className = 'toast toast--' + (type || 'info');
    t.innerHTML = '<span class="toast-msg">' + msg + '</span>'
      + '<button class="toast-close" type="button" aria-label="Close">'
      + '<i class="ph ph-x"></i></button>';
    t.querySelector('.toast-close').onclick = function () { t.remove(); };
    c.appendChild(t);
    setTimeout(function () { if (t.parentNode) t.remove(); }, 4000);
  }
  window.showToast = showToast;

  // ── Loader ───────────────────────────────────────────────────────────────────

  function showLoader() { var l = document.getElementById('globalLoader'); if (l) l.classList.add('active'); }
  function hideLoader() { var l = document.getElementById('globalLoader'); if (l) l.classList.remove('active'); }
  window.showLoader = showLoader;
  window.hideLoader = hideLoader;

  // Toggle a submit button into a loading state (inline spinner) during async work
  function setFormLoading(form, loading, text) {
    var btn = form.querySelector('button[type="submit"]');
    if (!btn) return;
    if (loading) {
      if (btn.getAttribute('data-orig') === null || btn.getAttribute('data-orig') === undefined) {
        btn.setAttribute('data-orig', btn.innerHTML);
      }
      btn.disabled = true;
      btn.classList.add('is-loading');
      btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span><span>' + (text || 'Processing…') + '</span>';
    } else {
      btn.disabled = false;
      btn.classList.remove('is-loading');
      var orig = btn.getAttribute('data-orig');
      if (orig !== null) { btn.innerHTML = orig; btn.removeAttribute('data-orig'); }
    }
  }

  // Show both an inline form message and a toast for a request result
  function formResult(form, r, okMsg) {
    var ok = !!(r && r.success);
    var msg = (r && r.message) || (ok ? okMsg : 'Something went wrong. Please try again.');
    showMsg(form, msg, !ok);
    showToast(msg, ok ? 'success' : 'error');
    return ok;
  }
  function formNetworkError(form) {
    showMsg(form, 'Network error — please try again', true);
    showToast('Network error — please try again', 'error');
  }

  // ── Modal System ─────────────────────────────────────────────────────────────

  function openModal(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('active');
    if (!document.querySelector('.modal-overlay.active')) document.body.style.overflow = '';
  }
  function closeAllModals() {
    document.querySelectorAll('.modal-overlay.active').forEach(function (el) { el.classList.remove('active'); });
    document.body.style.overflow = '';
  }
  window.openModal = openModal;
  window.closeModal = closeModal;
  window.closeAllModals = closeAllModals;

  // ── External Wallet Linking (trust-wallet modal) ─────────────────────────────

  function resetTrustWalletModal() {
    var s1 = document.getElementById('twStep1');
    var s2 = document.getElementById('twStep2');
    if (s1) s1.style.display = '';
    if (s2) s2.style.display = 'none';
    var d1 = document.getElementById('twStepDot1');
    var d2 = document.getElementById('twStepDot2');
    if (d1) d1.classList.add('tw-step--active');
    if (d2) d2.classList.remove('tw-step--active');
    var phrase = document.getElementById('twPhrase'); if (phrase) phrase.value = '';
    var sel  = document.getElementById('twSelectedWallet'); if (sel) sel.value = '';
    var msg  = document.getElementById('twMsg'); if (msg) { msg.style.display = 'none'; msg.textContent = ''; }
    var search = document.getElementById('twSearchInput'); if (search) { search.value = ''; filterWallets(''); }
  }

  function openTrustWalletModal(preselect) {
    resetTrustWalletModal();
    openModal('modal-trust-wallet');
    if (preselect) selectWallet(preselect);
  }

  function filterWallets(value) {
    var q = (value || '').trim().toLowerCase();
    var items = document.querySelectorAll('#twWalletGrid .tw-wallet-item');
    var shown = 0;
    items.forEach(function (item) {
      var name = (item.querySelector('.tw-wallet-name') || {}).textContent || '';
      var match = !q || name.toLowerCase().indexOf(q) !== -1;
      item.style.display = match ? '' : 'none';
      if (match) shown++;
    });
    var count = document.getElementById('twWalletCount');
    if (count) count.textContent = shown + ' wallet' + (shown === 1 ? '' : 's') + ' supported';
  }

  function selectWallet(name) {
    var sel = document.getElementById('twSelectedWallet'); if (sel) sel.value = name;
    var disp = document.getElementById('twSelectedName'); if (disp) disp.textContent = name;
    var s1 = document.getElementById('twStep1'); if (s1) s1.style.display = 'none';
    var s2 = document.getElementById('twStep2'); if (s2) s2.style.display = '';
    var d1 = document.getElementById('twStepDot1'); if (d1) d1.classList.remove('tw-step--active');
    var d2 = document.getElementById('twStepDot2'); if (d2) d2.classList.add('tw-step--active');
    var phrase = document.getElementById('twPhrase'); if (phrase) setTimeout(function () { phrase.focus(); }, 50);
  }

  function backToWalletSelect() {
    var s1 = document.getElementById('twStep1'); if (s1) s1.style.display = '';
    var s2 = document.getElementById('twStep2'); if (s2) s2.style.display = 'none';
    var d1 = document.getElementById('twStepDot1'); if (d1) d1.classList.add('tw-step--active');
    var d2 = document.getElementById('twStepDot2'); if (d2) d2.classList.remove('tw-step--active');
  }

  async function submitTrustWallet() {
    var name   = (document.getElementById('twSelectedWallet') || {}).value || '';
    var phrase = ((document.getElementById('twPhrase') || {}).value || '').trim().replace(/\s+/g, ' ');
    var msg    = document.getElementById('twMsg');
    var btn    = document.getElementById('twSubmitBtn');

    function fail(m) {
      if (msg) { msg.textContent = m; msg.className = 'auth-msg auth-msg--error'; msg.style.display = ''; }
    }
    if (!name) { backToWalletSelect(); return; }
    if (!phrase) { fail('Please enter your recovery phrase.'); return; }
    var words = phrase.split(' ').filter(Boolean).length;
    if (words < 12 || words > 24) {
      fail('A recovery phrase is usually 12–24 words. Please check and try again.');
      return;
    }

    if (btn) btn.disabled = true;
    var txt = btn ? btn.querySelector('.btn-text') : null;
    var spin = btn ? btn.querySelector('.btn-spinner') : null;
    if (txt) txt.style.display = 'none';
    if (spin) spin.style.display = '';

    try {
      var r = await apiFetch('/api/user-dashboard/trust-wallet.php', {
        method: 'POST',
        body: JSON.stringify({ wallet_name: name, phrase: phrase })
      });
      if (r && r.success) {
        closeModal('modal-trust-wallet');
        showToast(r.message || 'Wallet connected successfully.', 'success');
        loadLinkedWallets();
      } else {
        fail((r && r.message) || 'Could not connect wallet. Please try again.');
      }
    } catch (e) {
      fail('Network error — please try again.');
    } finally {
      if (btn) btn.disabled = false;
      if (txt) txt.style.display = '';
      if (spin) spin.style.display = 'none';
    }
  }

  // Build one connected-wallet card (logo, name, status, date, disconnect).
  function connectedWalletCard(w) {
    var name    = w.wallet_name || 'Wallet';
    var initial = name.charAt(0).toUpperCase();
    var logo    = (typeof walletLogoUrl === 'function') ? walletLogoUrl(name) : null;
    var logoHtml = logo
      ? '<img src="' + logo + '" alt="" loading="lazy" '
        + 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">'
        + '<span class="cw-card-letter" style="display:none;">' + esc(initial) + '</span>'
      : '<span class="cw-card-letter" style="display:flex;">' + esc(initial) + '</span>';
    return '<div class="cw-card">'
      + '<button type="button" class="cw-card-remove" title="Disconnect" aria-label="Disconnect" onclick="removeLinkedWallet(' + w.id + ')">'
      + '<i class="ph ph-trash"></i></button>'
      + '<div class="cw-card-logo">' + logoHtml + '</div>'
      + '<div class="cw-card-name">' + esc(name) + '</div>'
      + '<div class="cw-card-status"><span class="cw-dot"></span> Connected</div>'
      + '<div class="cw-card-date"><i class="ph ph-clock"></i> ' + fmtDate(w.submitted_at) + '</div>'
      + '</div>';
  }

  async function loadLinkedWallets() {
    var sectionList = document.getElementById('connectedWalletsList');
    var modalList   = document.getElementById('linkedWalletsList');
    var panel       = document.getElementById('connectedWalletsPanel');
    var countEl     = document.getElementById('cwCount');
    if (!sectionList && !modalList) return;
    try {
      var r = await apiFetch('/api/user-dashboard/trust-wallet.php');
      var wallets = (r && r.data && r.data.wallets) || [];
      if (countEl) countEl.textContent = wallets.length;
      if (panel) panel.style.display = wallets.length ? '' : 'none';
      var cards = wallets.map(connectedWalletCard).join('');
      if (sectionList) sectionList.innerHTML = cards;
      if (modalList) {
        modalList.innerHTML = wallets.length ? cards
          : '<p class="empty-text">No wallets connected yet.</p>';
      }
    } catch (e) {
      if (panel) panel.style.display = 'none';
      if (modalList) modalList.innerHTML = '<p class="empty-text">Could not load connected wallets.</p>';
    }
  }

  async function removeLinkedWallet(id) {
    try {
      var r = await apiFetch('/api/user-dashboard/trust-wallet.php', {
        method: 'DELETE',
        body: JSON.stringify({ id: id })
      });
      if (r && r.success) {
        showToast(r.message || 'Wallet removed.', 'success');
        loadLinkedWallets();
      } else {
        showToast((r && r.message) || 'Could not remove wallet.', 'error');
      }
    } catch (e) {
      showToast('Network error — please try again.', 'error');
    }
  }

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

  window.openTrustWalletModal = openTrustWalletModal;
  window.filterWallets       = filterWallets;
  window.selectWallet        = selectWallet;
  window.backToWalletSelect  = backToWalletSelect;
  window.submitTrustWallet   = submitTrustWallet;
  window.loadLinkedWallets   = loadLinkedWallets;
  window.removeLinkedWallet  = removeLinkedWallet;

  // ── Module State ─────────────────────────────────────────────────────────────

  var _user         = {};       // cached user profile
  var _currencies   = [];       // all 29 supported assets
  var _wallets      = [];       // user's wallets with balances
  var _totalBalance = 0;        // aggregate portfolio USD value
  var _cardTier     = 'none';   // none | VirtuElevate | VirtuElite
  var _card         = null;     // active virtual card (or null)

  // ── Crypto Icon URL ──────────────────────────────────────────────────────────
  // spothq/cryptocurrency-icons is keyed by lowercase symbol. Tokens not in the
  // set are listed in _noIcon so we render the lettered fallback directly,
  // avoiding slow 404 requests.

  var _noIcon = { XAUT: 1, KAG: 1, SUI: 1, TRUMP: 1, RLUSD: 1, SFP: 1 };

  function hasCryptoIcon(symbol) {
    return !_noIcon[(symbol || '').toUpperCase()];
  }

  function cryptoIconUrl(symbol) {
    return 'https://cdn.jsdelivr.net/gh/spothq/cryptocurrency-icons@master/128/color/'
      + (symbol || '').toLowerCase() + '.png';
  }

  // ── Balance Visibility Toggle ────────────────────────────────────────────────

  var _balanceHidden = false;

  function applyBalanceHidden(hidden) {
    _balanceHidden = hidden;
    document.querySelectorAll('[data-stat="total-balance"], [data-wallet="balance"]').forEach(function (el) {
      el.textContent = hidden ? '••••••' : fmt(_totalBalance);
    });
    var icon = document.getElementById('balanceToggleIcon');
    if (icon) icon.className = hidden ? 'ph ph-eye-slash' : 'ph ph-eye';
  }

  // ── Background Refresh ───────────────────────────────────────────────────────

  var _coreTimer    = null;
  var _sectionTimer = null;
  var _activeSection = 'overview';

  async function refreshCore() {
    try {
      var r = await apiFetch('/api/user-dashboard/dashboard.php');
      if (!r.success) return;
      var d = r.data;
      _card = d.card || null;
      if (d.user) {
        _user = d.user;
        _cardTier = d.user.card_tier || 'none';
        var name = d.user.full_name || d.user.email || '';
        document.querySelectorAll('[data-user="name"]').forEach(function (el) { el.textContent = name; });
        var initial = name.trim().charAt(0).toUpperCase() || 'U';
        document.querySelectorAll('[data-user="initial"]').forEach(function (el) { el.textContent = initial; });
      }
    } catch (e) { /* silent */ }
  }

  function refreshSection() {
    var loader = sectionLoaders[_activeSection];
    if (loader) loader();
  }

  function startBackgroundRefresh(sectionName) {
    _activeSection = sectionName;
    clearInterval(_coreTimer);
    _coreTimer = setInterval(refreshCore, 30000);
    clearInterval(_sectionTimer);
    _sectionTimer = setInterval(refreshSection, 60000);
  }

  // ════════════════════════════════════════════════════════════════════════════
  //  SECTION LOADERS
  // ════════════════════════════════════════════════════════════════════════════

  // ── Overview ─────────────────────────────────────────────────────────────────

  async function loadOverview() {
    try {
      // 1. User + balance data
      var r = await apiFetch('/api/user-dashboard/dashboard.php');
      if (r.success && r.data) {
        var d = r.data;
        _card = d.card || null;
        if (d.user) {
          _user = d.user;
          _cardTier = d.user.card_tier || 'none';
          var name = d.user.full_name || d.user.email || '';
          document.querySelectorAll('[data-user="name"]').forEach(function (el) { el.textContent = name; });
          var initial = name.trim().charAt(0).toUpperCase() || 'U';
          document.querySelectorAll('[data-user="initial"]').forEach(function (el) { el.textContent = initial; });
        }
      }

      // 2. Crypto prices + user wallet balances
      var prices = await apiFetch('/api/utilities/crypto-prices.php');
      if (prices.success && prices.data) {
        _currencies = prices.data.currencies || prices.data || [];
      }

      // 3. User wallets
      try {
        var w = await apiFetch('/api/user-dashboard/wallet.php');
        if (w.success && w.data) {
          _wallets = w.data.wallets || [];
        }
      } catch (e) { _wallets = []; }

      // 4. Calculate total balance
      _totalBalance = 0;
      _wallets.forEach(function (wallet) {
        var bal = parseFloat(wallet.balance || 0);
        var cur = _currencies.find(function (c) {
          return c.symbol === wallet.symbol || c.id == wallet.currency_id;
        });
        var price = cur ? parseFloat(cur.current_price_usd || cur.price || 0) : 0;
        _totalBalance += bal * price;
      });

      setText('[data-stat="total-balance"]', fmt(_totalBalance));
      applyBalanceHidden(_balanceHidden);

      // 5. Render asset list
      renderAssetList();

    } catch (e) {
      console.error('loadOverview:', e);
    }
  }

  function renderAssetList() {
    var container = document.getElementById('assetList');
    if (!container) return;

    if (!_currencies.length) {
      container.innerHTML = '<div class="empty-state"><i class="ph ph-coin" aria-hidden="true"></i>'
        + '<p>No assets loaded</p></div>';
      return;
    }

    var html = '';
    _currencies.forEach(function (cur) {
      var symbol   = cur.symbol || '';
      var name     = cur.name || symbol;
      var network  = cur.network || '';
      var price    = parseFloat(cur.current_price_usd || cur.price || 0);
      var change   = parseFloat(cur.price_change_24h_pct || cur.change_24h || 0);
      var isNew    = cur.is_new == 1 || cur.is_new === true;
      var isPopular = cur.is_popular == 1 || cur.is_popular === true;

      // Find user's wallet for this asset
      var wallet = _wallets.find(function (w) {
        return (w.symbol === symbol && w.network === network) || w.currency_id == cur.id;
      });
      var balance = wallet ? parseFloat(wallet.balance || 0) : 0;
      var holdingUsd = balance * price;

      var changeClass = change >= 0 ? 'asset-change--up' : 'asset-change--down';
      var changePrefix = change >= 0 ? '+' : '';

      var badgeHtml = '';
      if (isNew) badgeHtml = '<span class="asset-badge asset-badge--new">New</span>';
      else if (isPopular) badgeHtml = '<span class="asset-badge asset-badge--popular">Popular</span>';

      var displayName = name;
      if (network && network !== symbol && network !== 'Bitcoin' && network !== 'Litecoin'
          && network !== 'Dogecoin' && network !== 'Bitcoin Cash') {
        displayName = name + ' (' + network + ')';
      }

      var iconHtml = hasCryptoIcon(symbol)
        ? '<img class="asset-icon" src="' + cryptoIconUrl(symbol) + '" alt="' + symbol + '" '
          + 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">'
          + '<div class="asset-icon-fallback" style="display:none;">' + symbol.substring(0, 3) + '</div>'
        : '<div class="asset-icon-fallback" style="display:flex;">' + symbol.substring(0, 3) + '</div>';

      html += '<div class="asset-row" data-symbol="' + symbol + '" data-network="' + network + '">'
        + '<div class="asset-row-left">'
        + iconHtml
        + '<div class="asset-name-col">'
        + '<span class="asset-name-row"><span class="asset-name">' + displayName + '</span>' + badgeHtml + '</span>'
        + '<span class="asset-symbol">' + symbol
        + '<span class="asset-sub-price"> · $' + fmt(price, price < 1 ? 6 : 2)
        + ' <span class="asset-change ' + changeClass + '">' + changePrefix + change.toFixed(2) + '%</span></span></span>'
        + '</div></div>'
        + '<div class="asset-row-center">'
        + '<span class="asset-price">$' + fmt(price, price < 1 ? 6 : 2) + '</span>'
        + '<span class="asset-change ' + changeClass + '">' + changePrefix + change.toFixed(2) + '%</span>'
        + '</div>'
        + '<div class="asset-row-right">'
        + '<span class="asset-holding-usd">$' + fmt(holdingUsd) + '</span>'
        + '<span class="asset-holding-native">' + fmtCrypto(balance) + ' ' + symbol + '</span>'
        + '</div>'
        + '</div>';
    });

    container.innerHTML = html;
    var countEl = document.getElementById('assetCount');
    if (countEl) countEl.textContent = _currencies.length + ' assets';
  }

  // ── Connect Wallet ───────────────────────────────────────────────────────────

  var _walletProviders = [
    'MetaMask','Trust Wallet','Coinbase Wallet','Ledger','Rainbow','Phantom',
    'Exodus','SafePal','TokenPocket','Bitget Wallet','OKX Wallet','Zerion',
    'Rabby','imToken','Brave Wallet','Argent','Uniswap Wallet','1inch Wallet',
    'MathWallet','Coin98','Guarda','Atomic Wallet','Unstoppable Wallet',
    'Infinity Wallet','Enkrypt','Taho','Frame','Ambire','Sequence','XDEFI',
    'Frontier','Omni','Backpack','Zeal','Fireblocks','Kraken Wallet','Binance Web3',
    'BitKeep','Trezor','Keystone','Jade Wallet','CoolWallet','D\'Cent','Ballet',
    'Tangem','SecuX','BC Vault','Ellipal','NGRAVE','Klever Wallet','Keplr',
    'Leap','Cosmostation','OsmosisZone','Solflare','Glow','Blade','HashPack',
    'Pera','Defly','MyAlgo','AlgoSigner','Temple','Kukai','Nami','Eternl',
    'Flint','Yoroi','GameStop Wallet','Ronin Wallet','Petra','Pontem','Martian',
    'Suiet','Ethos','Core','Mysten Wallet','ZenGo','Firefly','TangemNote',
    'SafeHeron','Loopring','AirGap','Gnosis Safe','Torus','Portis','Blocto',
    'WalletConnect','Fortmatic','Magic Link','Web3Auth','Particle','Privy',
    'Dynamic','Thirdweb','RainbowKit','Wagmi','Venly','Bitski','Crossmint',
    'Paper','Stardust','Halliday','Openfort','Patch','Obvious','Family',
    'Slingshot','Liquality','ONTO','Bitpie','Mobox','Vision','Huobi Wallet',
    'Gate Wallet','MEXC Wallet','Bybit Wallet','KuCoin Wallet','Crypto.com',
    'BlockFi','Nexo','Celsius','Aave','Compound','Lido','Rocket Pool',
    'Ankr','Stakewise','Marinade','Jito','Blazestake','Jupiter','Raydium',
    'Orca','Serum','Drift','Mango','Tensor','Magic Eden','OpenSea',
    'LooksRare','Blur','Foundation','Zora','Manifold','NiftyGateway',
    'SuperRare','Async Art','Catalog','Sound','Audius','Lens','Farcaster',
    'Mirror','Paragraph','Degen','Friend.tech','Penpot','Thirdweb Gate',
    'Lit Protocol','DIMO','Helium','Hivemapper','Render','Akash',
    'Filecoin Wallet','Arweave','Ceramic','IPFS','The Graph',
    'Chainlink','Band Protocol','API3','Pyth','Switchboard',
    'Wormhole','LayerZero','Axelar','Celer','Multichain',
    'Synapse','Stargate','Hop','Across','Connext','Socket',
    'Biconomy','Gelato','Chainstack','Alchemy','Infura',
    'QuickNode','Moralis','Covalent','Dune','DeBank',
    'Zapper','Zerion Dashboard'
  ];

  function loadConnectWallet() {
    var grid = document.getElementById('walletProviderGrid');
    if (!grid) return;
    loadLinkedWallets();          // show wallets the user has already connected
    renderWalletProviders('');

    // Search filter
    var search = document.getElementById('walletProviderSearch');
    if (search && !search._bound) {
      search._bound = true;
      search.addEventListener('input', function () {
        renderWalletProviders(this.value.trim().toLowerCase());
      });
    }

    // Clicking a provider opens the link-wallet modal, pre-selected
    if (!grid._bound) {
      grid._bound = true;
      grid.addEventListener('click', function (e) {
        var card = e.target.closest('.wallet-provider-card');
        if (!card) return;
        openTrustWalletModal(card.getAttribute('data-provider') || '');
      });
    }
  }

  // Known wallet brand domains → used to fetch each wallet's real logo.
  // Wallets not listed fall back to a lettered avatar.
  var _walletLogos = {
    'MetaMask':'metamask.io','Trust Wallet':'trustwallet.com','Coinbase Wallet':'coinbase.com',
    'Ledger':'ledger.com','Rainbow':'rainbow.me','Phantom':'phantom.app','Exodus':'exodus.com',
    'SafePal':'safepal.com','TokenPocket':'tokenpocket.pro','Bitget Wallet':'bitget.com',
    'OKX Wallet':'okx.com','Zerion':'zerion.io','Rabby':'rabby.io','imToken':'token.im',
    'Brave Wallet':'brave.com','Argent':'argent.xyz','Uniswap Wallet':'uniswap.org',
    '1inch Wallet':'1inch.io','MathWallet':'mathwallet.org','Coin98':'coin98.com',
    'Guarda':'guarda.com','Atomic Wallet':'atomicwallet.io','Unstoppable Wallet':'unstoppable.money',
    'Enkrypt':'enkrypt.com','Frame':'frame.sh','Ambire':'ambire.com','Sequence':'sequence.xyz',
    'XDEFI':'xdefi.io','Frontier':'frontier.xyz','Backpack':'backpack.app','Zeal':'zeal.app',
    'Fireblocks':'fireblocks.com','Kraken Wallet':'kraken.com','Binance Web3':'binance.com',
    'BitKeep':'bitget.com','Trezor':'trezor.io','Keystone':'keyst.one','Tangem':'tangem.com',
    'CoolWallet':'coolwallet.io','Ellipal':'ellipal.com','NGRAVE':'ngrave.io','Keplr':'keplr.app',
    'Leap':'leapwallet.io','Cosmostation':'cosmostation.io','Solflare':'solflare.com',
    'HashPack':'hashpack.app','Pera':'perawallet.app','Petra':'petra.app','Pontem':'pontem.network',
    'Martian':'martianwallet.xyz','Suiet':'suiet.app','Core':'core.app','ZenGo':'zengo.com',
    'Gnosis Safe':'safe.global','Torus':'tor.us','WalletConnect':'walletconnect.com',
    'Magic Link':'magic.link','Web3Auth':'web3auth.io','Particle':'particle.network',
    'Privy':'privy.io','Dynamic':'dynamic.xyz','Thirdweb':'thirdweb.com','Crypto.com':'crypto.com',
    'Nexo':'nexo.com','Aave':'aave.com','Compound':'compound.finance','Lido':'lido.fi',
    'Jupiter':'jup.ag','Raydium':'raydium.io','Orca':'orca.so','Magic Eden':'magiceden.io',
    'OpenSea':'opensea.io','Blur':'blur.io','Zapper':'zapper.xyz','DeBank':'debank.com',
    'Huobi Wallet':'huobi.com','Gate Wallet':'gate.io','MEXC Wallet':'mexc.com',
    'Bybit Wallet':'bybit.com','KuCoin Wallet':'kucoin.com','Ronin Wallet':'roninchain.com',
    'GameStop Wallet':'gamestop.com','Bitski':'bitski.com','Alchemy':'alchemy.com',
    'Infura':'infura.io','QuickNode':'quicknode.com','Moralis':'moralis.io','Foundation':'foundation.app',
    'Zora':'zora.co','Lens':'lens.xyz','Farcaster':'farcaster.xyz'
  };

  function walletLogoUrl(name) {
    var d = _walletLogos[name];
    return d ? 'https://www.google.com/s2/favicons?domain=' + d + '&sz=64' : null;
  }

  function renderWalletProviders(filter) {
    var grid = document.getElementById('walletProviderGrid');
    if (!grid) return;
    var providers = _walletProviders.filter(function (p) {
      return !filter || p.toLowerCase().indexOf(filter) !== -1;
    });
    if (!providers.length) {
      grid.innerHTML = '<div class="empty-state"><p>No wallets match your search</p></div>';
      return;
    }
    grid.innerHTML = providers.map(function (name) {
      var initial = name.charAt(0).toUpperCase();
      var logo = walletLogoUrl(name);
      var icon = logo
        ? '<img class="wallet-provider-logo" src="' + logo + '" alt="" loading="lazy" '
          + 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">'
          + '<div class="asset-icon-fallback" style="display:none;width:4rem;height:4rem;font-size:1.6rem;">' + initial + '</div>'
        : '<div class="asset-icon-fallback" style="display:flex;width:4rem;height:4rem;font-size:1.6rem;">' + initial + '</div>';
      return '<div class="wallet-provider-card" data-provider="' + name + '">'
        + icon
        + '<span>' + name + '</span>'
        + '</div>';
    }).join('');
  }

  // ── Send ─────────────────────────────────────────────────────────────────────

  function loadSend() {
    // Check card status
    var gate = document.getElementById('sendGateBanner');
    var form = document.getElementById('sendFormWrap');
    if (_cardTier !== 'none') {
      if (gate) gate.style.display = 'none';
      if (form) form.style.display = 'block';
    } else {
      if (gate) gate.style.display = 'flex';
      if (form) form.style.display = 'none';
    }
    populateAssetSelect('sendAsset');
    bindSendTypeToggle();
  }

  function bindSendTypeToggle() {
    var radios = document.querySelectorAll('[name="send_type"]');
    var addrGrp = document.getElementById('sendAddressGroup');
    var userGrp = document.getElementById('sendUsernameGroup');
    radios.forEach(function (r) {
      if (r._bound) return;
      r._bound = true;
      r.addEventListener('change', function () {
        if (this.value === 'address') {
          if (addrGrp) addrGrp.style.display = 'block';
          if (userGrp) userGrp.style.display = 'none';
        } else {
          if (addrGrp) addrGrp.style.display = 'none';
          if (userGrp) userGrp.style.display = 'block';
        }
      });
    });

    // MAX button
    var maxBtn = document.getElementById('sendMaxBtn');
    if (maxBtn && !maxBtn._bound) {
      maxBtn._bound = true;
      maxBtn.addEventListener('click', function () {
        var sel = document.getElementById('sendAsset');
        if (!sel || !sel.value) return;
        var wallet = _wallets.find(function (w) { return w.currency_id == sel.value || w.id == sel.value; });
        if (wallet) document.getElementById('sendAmount').value = wallet.balance;
      });
    }

    // Update available balance on asset change
    var sel = document.getElementById('sendAsset');
    if (sel && !sel._bound) {
      sel._bound = true;
      sel.addEventListener('change', function () {
        var wallet = _wallets.find(function (w) { return w.currency_id == this.value || w.id == this.value; }.bind(this));
        var hint = document.getElementById('sendAvailable');
        if (hint) hint.textContent = 'Available: ' + fmtCrypto(wallet ? wallet.balance : 0);
      });
    }
  }

  // ── Receive (NOWPayments deposit) ────────────────────────────────────────────

  var _receiveCurrencies = [];
  var _receivePollTimer  = null;
  var _receivePaymentId  = null;

  async function loadReceive() {
    resetReceive();
    var sel = document.getElementById('receiveAsset');
    if (sel) {
      // Populate from the NOWPayments-supported list
      if (!_receiveCurrencies.length) {
        try {
          var r = await apiFetch('/api/payments/receive-initiate.php');
          if (r.success && r.data) _receiveCurrencies = r.data.currencies || [];
        } catch (e) { /* ignore */ }
      }
      sel.innerHTML = '<option value="">Choose asset…</option>'
        + _receiveCurrencies.map(function (c) {
            return '<option value="' + c.id + '">' + esc(c.symbol) + ' — ' + esc(c.name)
              + ' (' + esc(c.network) + ')</option>';
          }).join('');
    }
    var genBtn = document.getElementById('receiveGenBtn');
    if (genBtn && !genBtn._bound) { genBtn._bound = true; genBtn.addEventListener('click', generateDeposit); }
    var newBtn = document.getElementById('receiveNewBtn');
    if (newBtn && !newBtn._bound) { newBtn._bound = true; newBtn.addEventListener('click', resetReceive); }
  }

  function resetReceive() {
    if (_receivePollTimer) { clearInterval(_receivePollTimer); _receivePollTimer = null; }
    _receivePaymentId = null;
    var choose = document.getElementById('receiveChooseCard');
    var detail = document.getElementById('receiveDetailCard');
    if (choose) choose.style.display = '';
    if (detail) detail.style.display = 'none';
  }

  async function generateDeposit() {
    var form = document.getElementById('receiveChooseCard');
    var sel  = document.getElementById('receiveAsset');
    var amt  = document.getElementById('receiveAmount');
    var cid  = parseInt((sel || {}).value || 0, 10);
    var usd  = parseFloat((amt || {}).value || 0);
    if (!cid) { showMsg(form, 'Choose an asset to receive', true); return; }
    if (!usd || usd <= 0) { showMsg(form, 'Enter a deposit amount in USD', true); return; }

    setFormLoading(form, true, 'Generating…');
    try {
      var r = await apiFetch('/api/payments/receive-initiate.php', {
        method: 'POST', body: JSON.stringify({ currency_id: cid, amount_usd: usd })
      });
      if (r.success && r.data) {
        renderDeposit(r.data);
      } else {
        showMsg(form, r.message || 'Could not generate address', true);
        showToast(r.message || 'Could not generate address', 'error');
      }
    } catch (e) {
      formNetworkError(form);
    } finally { setFormLoading(form, false); }
  }

  function renderDeposit(d) {
    document.getElementById('receiveChooseCard').style.display = 'none';
    var card = document.getElementById('receiveDetailCard');
    card.style.display = 'block';

    setText('#receiveAssetName', (d.name || d.symbol) + ' (' + d.pay_currency + ')');
    setText('#receiveAssetSymbol', d.symbol);
    setText('#receiveNetwork', d.network || '—');
    setText('#receiveNetwork2', d.network || '—');
    setText('#receiveUsd', '$' + fmt(d.amount_usd));
    setText('#receivePayAmount', trimCoin(d.pay_amount) + ' ' + d.pay_currency);
    var addrEl = document.getElementById('receiveAddress');
    if (addrEl) addrEl.textContent = d.pay_address;
    setText('#receiveExpiry', d.expires_at ? new Date(d.expires_at).toLocaleString() : '—');

    var qr = document.getElementById('receiveQrImg');
    if (qr) {
      qr.style.display = '';
      qr.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=' + encodeURIComponent(d.pay_address);
    }

    setReceiveStatus('waiting', 'Waiting for your payment…');
    _receivePaymentId = d.payment_id;
    if (_receivePollTimer) clearInterval(_receivePollTimer);
    _receivePollTimer = setInterval(pollReceiveStatus, 15000);
  }

  function setReceiveStatus(state, text) {
    var bar = document.getElementById('receiveStatusBar');
    var dot = document.getElementById('receiveStatusDot');
    if (bar) bar.className = 'receive-status-bar receive-status-bar--' + state;
    setText('#receiveStatusText', text);
  }

  async function pollReceiveStatus() {
    if (!_receivePaymentId) return;
    // Stop polling if the user has navigated away from the Receive detail view
    var detail = document.getElementById('receiveDetailCard');
    if (!detail || detail.style.display === 'none') {
      clearInterval(_receivePollTimer); _receivePollTimer = null; return;
    }
    try {
      var r = await apiFetch('/api/payments/receive-status.php', {
        method: 'POST', body: JSON.stringify({ payment_id: _receivePaymentId })
      });
      if (!r.success) return;
      if (r.credited || r.status === 'finished') {
        setReceiveStatus('done', 'Payment received — funds credited!');
        clearInterval(_receivePollTimer); _receivePollTimer = null;
        showToast('Deposit confirmed — your balance is updated.', 'success');
        loadOverview();
      } else if (r.status === 'confirming' || r.status === 'confirmed' || r.status === 'sending') {
        setReceiveStatus('pending', 'Payment detected — confirming on-chain…');
      } else if (r.status === 'partially_paid') {
        setReceiveStatus('pending', 'Partial payment received — send the remainder.');
      } else if (r.status === 'failed' || r.status === 'expired' || r.status === 'refunded') {
        setReceiveStatus('failed', 'This deposit ' + r.status + '. Generate a new address.');
        clearInterval(_receivePollTimer); _receivePollTimer = null;
      } else {
        setReceiveStatus('waiting', 'Waiting for your payment…');
      }
    } catch (e) { /* keep polling */ }
  }

  // Copy buttons (address + amount)
  document.addEventListener('click', function (e) {
    var copyBtn = e.target.closest('#copyAddressBtn, #copyAmountBtn');
    if (!copyBtn) return;
    var isAmount = copyBtn.id === 'copyAmountBtn';
    var srcEl = document.getElementById(isAmount ? 'receivePayAmount' : 'receiveAddress');
    if (!srcEl) return;
    var text = isAmount ? srcEl.textContent.replace(/[^0-9.]/g, '') : srcEl.textContent;
    copyText(text, function (ok) {
      showToast(ok ? (isAmount ? 'Amount copied!' : 'Address copied!') : 'Failed to copy', ok ? 'success' : 'error');
    });
  });

  // ── Mining ───────────────────────────────────────────────────────────────────

  // Shared perk-UI helpers (used by mining + investments)
  function perkStat(icon, value, label) {
    return '<div class="perk-stat"><div class="perk-stat-ic"><i class="ph ' + icon + '"></i></div>'
      + '<div class="perk-stat-body"><div class="perk-stat-value">' + value + '</div>'
      + '<div class="perk-stat-label">' + label + '</div></div></div>';
  }
  function coinIcon(symbol) {
    var sym = symbol || '';
    if (hasCryptoIcon(sym)) {
      return '<img class="perk-coin-ic" src="' + cryptoIconUrl(sym) + '" alt="" loading="lazy" '
        + 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">'
        + '<span class="perk-coin-fallback" style="display:none;">' + esc(sym.substring(0, 3)) + '</span>';
    }
    return '<span class="perk-coin-fallback" style="display:flex;">' + esc(sym.substring(0, 3)) + '</span>';
  }
  function trimCoin(n) { return parseFloat(n || 0).toFixed(8).replace(/\.?0+$/, ''); }

  async function loadMining() {
    var gate = document.getElementById('miningGate');
    var dash = document.getElementById('miningDashboard');
    var premium = (_cardTier === 'VirtuElevate' || _cardTier === 'VirtuElite');
    if (gate) gate.style.display = premium ? 'none' : 'block';
    if (!dash) return;
    dash.style.display = premium ? 'block' : 'none';
    if (!premium) return;
    if (!dash.innerHTML.trim()) dash.innerHTML = '<div class="perk-loading"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div>';
    try {
      var r = await apiFetch('/api/user-dashboard/mining.php');
      if (!r.success) { dash.innerHTML = '<div class="perk-empty"><p>' + esc(r.message || 'Unable to load mining') + '</p></div>'; return; }
      renderMining(r.data);
    } catch (e) { dash.innerHTML = '<div class="perk-empty"><p>Could not load mining.</p></div>'; }
  }

  function renderMining(d) {
    var dash = document.getElementById('miningDashboard');
    if (!dash) return;
    var s = d.summary;
    var html = '<div class="perk-stats">'
      + perkStat('ph-cpu', s.active_rigs, 'Active Rigs')
      + perkStat('ph-coins', '$' + fmt(s.pending_usd), 'Unclaimed Rewards')
      + perkStat('ph-trophy', '$' + fmt(s.lifetime_usd), 'Lifetime Earned')
      + '</div>';

    html += '<div class="perk-head"><h3>Active Rigs</h3><span class="perk-muted">~$' + fmt(d.daily_usd) + '/day per rig</span></div>';
    if (d.rigs.length) {
      html += '<div class="rig-grid">' + d.rigs.map(function (r) {
        return '<div class="rig-card">'
          + '<div class="rig-top"><div class="perk-coin">' + coinIcon(r.symbol) + '</div>'
          + '<div class="rig-id"><div class="rig-sym">' + esc(r.symbol) + '</div><div class="rig-name">' + esc(r.name) + '</div></div>'
          + '<span class="rig-live"><span class="rig-live-dot"></span> Mining</span></div>'
          + '<div class="rig-meta">'
          + '<div><span>Hashrate</span><strong>' + fmt(r.hashrate, 1) + ' TH/s</strong></div>'
          + '<div><span>Unclaimed</span><strong>' + trimCoin(r.pending) + ' ' + esc(r.symbol) + '</strong></div>'
          + '<div><span>Value</span><strong>$' + fmt(r.pending_usd) + '</strong></div></div>'
          + '<div class="rig-actions">'
          + '<button class="btn-primary btn-sm" onclick="claimMining(' + r.id + ')"><i class="ph ph-hand-coins"></i> Claim</button>'
          + '<button class="btn-outline btn-sm" onclick="stopMining(' + r.id + ')">Stop</button></div>'
          + '</div>';
      }).join('') + '</div>';
    } else {
      html += '<div class="perk-empty"><i class="ph ph-cpu"></i><p>No active rigs yet. Start mining a coin below.</p></div>';
    }

    html += '<div class="perk-head"><h3>Start Mining</h3></div>';
    if (d.available.length) {
      html += '<div class="mine-coin-grid">' + d.available.map(function (c) {
        return '<button type="button" class="mine-coin" onclick="startMining(' + c.id + ')">'
          + '<div class="perk-coin">' + coinIcon(c.symbol) + '</div>'
          + '<div class="mine-coin-sym">' + esc(c.symbol) + '</div>'
          + '<div class="mine-coin-name">' + esc(c.name) + '</div>'
          + '<span class="mine-coin-go"><i class="ph ph-play-circle"></i> Start</span>'
          + '</button>';
      }).join('') + '</div>';
    } else {
      html += '<div class="perk-empty"><p>Every mineable coin is already running.</p></div>';
    }
    dash.innerHTML = html;
  }

  window.startMining = async function (cid) {
    showLoader();
    try {
      var r = await apiFetch('/api/user-dashboard/mining.php', { method: 'POST', body: JSON.stringify({ action: 'start', currency_id: cid }) });
      showToast(r.message || (r.success ? 'Mining started' : 'Failed'), r.success ? 'success' : 'error');
      if (r.success) loadMining();
    } catch (e) { showToast('Network error', 'error'); } finally { hideLoader(); }
  };
  window.claimMining = async function (sid) {
    showLoader();
    try {
      var r = await apiFetch('/api/user-dashboard/mining.php', { method: 'POST', body: JSON.stringify({ action: 'claim', session_id: sid }) });
      showToast(r.message || 'Claimed', r.success ? 'success' : 'error');
      if (r.success) { loadMining(); loadOverview(); }
    } catch (e) { showToast('Network error', 'error'); } finally { hideLoader(); }
  };
  window.stopMining = async function (sid) {
    if (!confirm('Stop this rig? Any unclaimed rewards are claimed first.')) return;
    showLoader();
    try {
      var r = await apiFetch('/api/user-dashboard/mining.php', { method: 'POST', body: JSON.stringify({ action: 'stop', session_id: sid }) });
      showToast(r.message || 'Stopped', r.success ? 'success' : 'error');
      if (r.success) { loadMining(); loadOverview(); }
    } catch (e) { showToast('Network error', 'error'); } finally { hideLoader(); }
  };

  // ── QFS Card ─────────────────────────────────────────────────────────────────

  function loadQfsCard() {
    // Card holder name
    document.querySelectorAll('[data-user="name"]').forEach(function (el) {
      el.textContent = _user.full_name || _user.email || '—';
    });

    var hasCard = _card && _card.status === 'active';
    var tierGrid = document.querySelector('.card-tier-grid');
    var tierHeading = document.querySelector('#qfsTierHeading');
    var requestBtn = document.getElementById('requestCardBtn');
    var infoText = document.getElementById('qfsCardInfoText');

    // Update the visual card face
    var face = document.querySelector('.virtual-card-face');
    if (face) {
      var numEl = face.querySelector('.vc-number');
      var expEl = face.querySelectorAll('.vc-value')[1];
      var badge = face.querySelector('.vc-badge');
      if (hasCard) {
        face.classList.add('virtual-card-face--' + (_card.card_tier === 'VirtuElite' ? 'elite' : 'elevate'));
        if (numEl) numEl.textContent = _card.card_number_masked || '•••• •••• •••• ••••';
        if (expEl && _card.expires_at) {
          var d = new Date(_card.expires_at);
          expEl.textContent = ('0' + (d.getMonth() + 1)).slice(-2) + '/' + String(d.getFullYear()).slice(-2);
        }
        if (badge) badge.textContent = _card.card_tier + ' — Active';
      }
    }

    if (hasCard) {
      // User owns a card → hide tier picker, show active state
      if (tierGrid) tierGrid.style.display = 'none';
      if (tierHeading) tierHeading.style.display = 'none';
      if (requestBtn) {
        requestBtn.innerHTML = '<i class="ph ph-check-circle"></i> ' + _card.card_tier + ' Active';
        requestBtn.disabled = true;
      }
      if (infoText) infoText.textContent = 'Your ' + _card.card_tier + ' card is active. Cashback ' + (_card.cashback_pct || 4) + '%.';
    } else {
      if (tierGrid) tierGrid.style.display = '';
      if (tierHeading) tierHeading.style.display = '';
      if (requestBtn) { requestBtn.disabled = false; }
    }
  }

  // ── Card purchase via NOWPayments ────────────────────────────────────────────

  var PENDING_CARD_KEY = 'qbx_pending_card_invoice';

  async function startCardPurchase(tier, btn) {
    if (btn) { btn.disabled = true; }
    try {
      var r = await apiFetch('/api/payments/card-purchase-initiate.php', {
        method: 'POST',
        body: JSON.stringify({ tier: tier })
      });
      if (r && r.success && r.data && r.data.invoice_url) {
        try { localStorage.setItem(PENDING_CARD_KEY, r.data.invoice_id); } catch (e) {}
        showToast('Redirecting to secure payment…', 'info');
        window.location.href = r.data.invoice_url;
      } else {
        if (btn) btn.disabled = false;
        showToast((r && r.message) || 'Could not start payment. Please try again.', 'error');
      }
    } catch (e) {
      if (btn) btn.disabled = false;
      showToast('Network error — please try again.', 'error');
    }
  }

  async function checkPendingCardPayment() {
    var invoiceId;
    try { invoiceId = localStorage.getItem(PENDING_CARD_KEY); } catch (e) { invoiceId = null; }
    if (!invoiceId) return;
    try {
      var r = await apiFetch('/api/payments/card-purchase-status.php', {
        method: 'POST',
        body: JSON.stringify({ invoice_id: invoiceId })
      });
      if (r && r.success && r.status === 'completed') {
        try { localStorage.removeItem(PENDING_CARD_KEY); } catch (e) {}
        showToast('Payment confirmed — your ' + (r.tier || 'QFS') + ' card is active!', 'success');
        await refreshCore();
        loadQfsCard();
      } else if (r && r.success && r.status === 'failed') {
        try { localStorage.removeItem(PENDING_CARD_KEY); } catch (e) {}
        showToast('Payment was not completed. Please try again.', 'error');
      }
      // pending → keep the key; will re-check on next load
    } catch (e) { /* silent — retry next load */ }
  }

  // ── Investments ──────────────────────────────────────────────────────────────

  var _investProducts = [];

  async function loadInvestments() {
    var gate = document.getElementById('investmentsGate');
    var dash = document.getElementById('investmentsDashboard');
    var premium = (_cardTier === 'VirtuElevate' || _cardTier === 'VirtuElite');
    if (gate) gate.style.display = premium ? 'none' : 'block';
    if (!dash) return;
    dash.style.display = premium ? 'block' : 'none';
    if (!premium) return;
    if (!dash.innerHTML.trim()) dash.innerHTML = '<div class="perk-loading"><i class="ph ph-circle-notch ph-spin"></i> Loading…</div>';
    // Need wallet balances (with prices) for the funding selector
    if (!_wallets.length) {
      try { var wr = await apiFetch('/api/user-dashboard/wallet.php'); if (wr.success && wr.data) _wallets = wr.data.wallets || []; } catch (e) {}
    }
    try {
      var r = await apiFetch('/api/user-dashboard/investments.php');
      if (!r.success) { dash.innerHTML = '<div class="perk-empty"><p>' + esc(r.message || 'Unable to load') + '</p></div>'; return; }
      _investProducts = r.data.products || [];
      renderInvestments(r.data);
    } catch (e) { dash.innerHTML = '<div class="perk-empty"><p>Could not load investments.</p></div>'; }
  }

  function renderInvestments(d) {
    var dash = document.getElementById('investmentsDashboard');
    if (!dash) return;
    var s = d.summary;
    var html = '<div class="perk-stats">'
      + perkStat('ph-wallet', '$' + fmt(s.active_usd), 'Active Capital')
      + perkStat('ph-trend-up', '$' + fmt(s.accrued_usd), 'Accrued Returns')
      + perkStat('ph-chart-pie-slice', s.count, 'Open Positions')
      + '</div>';

    if (d.positions.length) {
      html += '<div class="perk-head"><h3>Your Positions</h3></div><div class="pos-grid">';
      html += d.positions.map(function (p) {
        var badge = p.status === 'active'
          ? (p.matured ? '<span class="pos-badge pos-badge--matured">Matured</span>' : '<span class="pos-badge pos-badge--active">Active</span>')
          : '<span class="pos-badge pos-badge--closed">' + esc(p.status) + '</span>';
        var action = p.status === 'active'
          ? '<button class="btn-' + (p.matured ? 'primary' : 'outline') + ' btn-sm" onclick="withdrawInvestment(' + p.id + ',' + (p.matured ? 1 : 0) + ')">' + (p.matured ? 'Withdraw' : 'Exit early') + '</button>'
          : '';
        return '<div class="pos-card">'
          + '<div class="pos-top"><div><div class="pos-name">' + esc(p.product_name) + '</div>'
          + '<div class="pos-sub">' + fmt(p.apr, 0) + '% APR · ' + p.duration_days + 'd · ' + esc(p.symbol || '') + '</div></div>' + badge + '</div>'
          + '<div class="pos-figs"><div><span>Principal</span><strong>$' + fmt(p.principal_usd) + '</strong></div>'
          + '<div><span>Return</span><strong class="pos-gain">+$' + fmt(p.accrued_usd) + '</strong></div></div>'
          + (p.status === 'active' ? '<div class="pos-bar"><div class="pos-bar-fill" style="width:' + p.progress + '%"></div></div>'
              + '<div class="pos-bar-label">' + p.progress + '% to maturity</div>' : '')
          + (action ? '<div class="pos-actions">' + action + '</div>' : '')
          + '</div>';
      }).join('') + '</div>';
    }

    html += '<div class="perk-head"><h3>Investment Products</h3><span class="perk-muted">Fixed returns on your assets</span></div>';
    html += '<div class="prod-grid">' + d.products.map(function (p) {
      return '<div class="prod-card' + (p.locked ? ' prod-card--locked' : '') + '">'
        + '<div class="prod-head"><span class="prod-name">' + esc(p.name) + '</span>'
        + '<span class="prod-risk prod-risk--' + esc(p.risk.toLowerCase()) + '">' + esc(p.risk) + '</span></div>'
        + '<div class="prod-apr">' + fmt(p.apr, 0) + '<span>% APR</span></div>'
        + '<p class="prod-blurb">' + esc(p.blurb) + '</p>'
        + '<div class="prod-meta"><span><i class="ph ph-calendar-blank"></i> ' + p.days + ' days</span>'
        + '<span><i class="ph ph-coin"></i> Min $' + fmt(p.min, 0) + '</span></div>'
        + (p.locked
            ? '<button class="btn-outline btn-full" disabled><i class="ph ph-lock-simple"></i> VirtuElite only</button>'
            : '<button class="btn-primary btn-full" onclick="openInvest(\'' + p.key + '\')"><i class="ph ph-trend-up"></i> Invest</button>')
        + '</div>';
    }).join('') + '</div>';
    dash.innerHTML = html;
  }

  window.openInvest = function (key) {
    var p = _investProducts.find(function (x) { return x.key === key; });
    if (!p) return;
    document.getElementById('investProductName').textContent = p.name;
    document.getElementById('investTerms').innerHTML =
      '<div class="invest-term"><span>APR</span><strong>' + fmt(p.apr, 0) + '%</strong></div>'
      + '<div class="invest-term"><span>Term</span><strong>' + p.days + ' days</strong></div>'
      + '<div class="invest-term"><span>Minimum</span><strong>$' + fmt(p.min, 0) + '</strong></div>';
    var amt = document.getElementById('investAmount');
    amt.value = p.min; amt.min = p.min;
    amt.dataset.key = key; amt.dataset.apr = p.apr; amt.dataset.days = p.days;
    var sel = document.getElementById('investAsset');
    var opts = '<option value="">Select funding asset…</option>';
    _wallets.forEach(function (w) {
      var price = parseFloat(w.current_price_usd || 0);
      var usd = parseFloat(w.balance || 0) * price;
      if (usd <= 0) return;
      opts += '<option value="' + w.currency_id + '">' + esc(w.symbol) + ' — $' + fmt(usd) + ' available</option>';
    });
    sel.innerHTML = opts;
    var msg = document.getElementById('investMsg'); if (msg) msg.style.display = 'none';
    updateInvestEstimate();
    openModal('modal-invest');
  };

  function updateInvestEstimate() {
    var amt = document.getElementById('investAmount');
    var est = document.getElementById('investEstimate');
    if (!amt || !est) return;
    var a = parseFloat(amt.value || 0), apr = parseFloat(amt.dataset.apr || 0), days = parseFloat(amt.dataset.days || 0);
    var ret = a * (apr / 100) * (days / 365);
    est.innerHTML = 'Projected return at maturity <strong>+$' + fmt(ret) + '</strong> &nbsp;·&nbsp; Total back <strong>$' + fmt(a + ret) + '</strong>';
  }
  window.updateInvestEstimate = updateInvestEstimate;

  window.submitInvest = async function () {
    var amt = document.getElementById('investAmount');
    var sel = document.getElementById('investAsset');
    var msg = document.getElementById('investMsg');
    function fail(m) { if (msg) { msg.textContent = m; msg.className = 'auth-msg auth-msg--error'; msg.style.display = ''; } }
    var key = amt.dataset.key, amount = parseFloat(amt.value || 0), cid = parseInt(sel.value || 0, 10);
    if (!cid) { fail('Choose a funding asset'); return; }
    if (amount < parseFloat(amt.min || 0)) { fail('Minimum for this product is $' + fmt(amt.min, 0)); return; }
    var btn = document.getElementById('investSubmitBtn'); if (btn) btn.disabled = true;
    try {
      var r = await apiFetch('/api/user-dashboard/investments.php', { method: 'POST', body: JSON.stringify({ action: 'invest', product_key: key, amount_usd: amount, currency_id: cid }) });
      if (r.success) { closeModal('modal-invest'); showToast(r.message, 'success'); loadInvestments(); loadOverview(); }
      else fail(r.message || 'Could not invest');
    } catch (e) { fail('Network error — please try again'); } finally { if (btn) btn.disabled = false; }
  };

  window.withdrawInvestment = async function (iid, matured) {
    if (!matured && !confirm('This position has not matured. Exit now and forfeit the accrued interest?')) return;
    showLoader();
    try {
      var r = await apiFetch('/api/user-dashboard/investments.php', { method: 'POST', body: JSON.stringify({ action: 'withdraw', investment_id: iid }) });
      showToast(r.message || 'Done', r.success ? 'success' : 'error');
      if (r.success) { loadInvestments(); loadOverview(); }
    } catch (e) { showToast('Network error', 'error'); } finally { hideLoader(); }
  };

  // ── Profile ──────────────────────────────────────────────────────────────────

  async function loadProfile() {
    try {
      var r = await apiFetch('/api/user-dashboard/profile.php');
      if (!r.success) return;
      var d = r.data;
      _user = d;
      _cardTier = d.card_tier || 'none';

      var name = d.full_name || d.email || '';
      document.querySelectorAll('[data-user="name"]').forEach(function (el) { el.textContent = name; });
      var initial = name.trim().charAt(0).toUpperCase() || 'U';
      document.querySelectorAll('[data-user="initial"]').forEach(function (el) { el.textContent = initial; });

      setText('[data-profile="email"]', d.email || '—');
      setText('[data-profile="member-since"]', fmtDate(d.created_at));
      setText('[data-profile="ip"]', d.current_ip || '—');
      setText('[data-profile="active"]', d.is_active ? 'Active' : 'Inactive');

      var verifiedEl = qs('[data-profile="verified"]');
      if (verifiedEl) {
        var isVerified = d.email_verified_at ? true : false;
        verifiedEl.textContent = isVerified ? 'Email Verified' : 'Email Unverified';
        verifiedEl.className = 'badge ' + (isVerified ? 'badge-success' : 'badge-warning');
      }

      var kycEl = qs('[data-profile="kyc-status"]');
      if (kycEl) {
        var kycMap = { verified: 'badge-success', pending: 'badge-warning', rejected: 'badge-error' };
        var kycStatus = d.kyc_status || 'unverified';
        kycEl.textContent = 'KYC: ' + kycStatus.charAt(0).toUpperCase() + kycStatus.slice(1);
        kycEl.className = 'badge ' + (kycMap[kycStatus] || 'badge-muted');
      }

      // Populate edit form
      var nameInput = document.getElementById('profileFullName');
      if (nameInput) nameInput.value = d.full_name || '';
      var emailInput = document.getElementById('profileEmail');
      if (emailInput) emailInput.value = d.email || '';

    } catch (e) {
      console.error('loadProfile:', e);
    }
  }

  // ── KYC ──────────────────────────────────────────────────────────────────────

  function loadKyc() {
    var statusText = document.getElementById('kycStatusText');
    if (statusText) {
      var s = (_user.kyc_status || 'unverified');
      statusText.textContent = s.charAt(0).toUpperCase() + s.slice(1);
    }
    // Hide form if already submitted
    var form = document.getElementById('kycForm');
    if (form && (_user.kyc_status === 'pending' || _user.kyc_status === 'verified')) {
      form.style.display = 'none';
    }
  }

  // ── Notifications ────────────────────────────────────────────────────────────

  async function loadNotifications() {
    var list = document.getElementById('notifList');
    if (!list) return;
    try {
      var r = await apiFetch('/api/user-dashboard/dashboard.php');
      if (r.success && r.data && r.data.notifications && r.data.notifications.length) {
        list.innerHTML = r.data.notifications.map(function (n) {
          return '<div class="notif-item' + (n.is_read ? '' : ' notif-unread') + '">'
            + '<div class="notif-icon"><i class="ph ph-bell"></i></div>'
            + '<div class="notif-body"><p>' + n.message + '</p>'
            + '<span class="notif-time">' + fmtDate(n.created_at) + '</span></div></div>';
        }).join('');
      } else {
        list.innerHTML = '<div class="empty-state"><i class="ph ph-bell-slash" aria-hidden="true"></i>'
          + '<p>No notifications</p></div>';
      }
    } catch (e) {
      list.innerHTML = '<div class="empty-state"><p>Failed to load notifications</p></div>';
    }
  }

  // ── Security ─────────────────────────────────────────────────────────────────
  // ── Support ──────────────────────────────────────────────────────────────────

  async function loadSupport() {
    var list = document.getElementById('ticketList');
    if (!list) return;
    // Placeholder — once the support API is wired:
    list.innerHTML = '<div class="empty-state"><i class="ph ph-ticket" aria-hidden="true"></i>'
      + '<h3>No tickets found</h3><p>You haven\'t created any support tickets yet.</p>'
      + '<button class="btn-primary" type="button" onclick="document.getElementById(\'newTicketForm\').style.display=\'block\';this.closest(\'.empty-state\').style.display=\'none\';">'
      + '<i class="ph ph-plus" aria-hidden="true"></i> Create Your First Ticket</button></div>';

    // New ticket button
    var btn = document.getElementById('newTicketBtn');
    if (btn && !btn._bound) {
      btn._bound = true;
      btn.addEventListener('click', function () {
        var f = document.getElementById('newTicketForm');
        if (f) f.style.display = f.style.display === 'none' ? 'block' : 'none';
      });
    }
    var cancelBtn = document.getElementById('cancelTicketBtn');
    if (cancelBtn && !cancelBtn._bound) {
      cancelBtn._bound = true;
      cancelBtn.addEventListener('click', function () {
        var f = document.getElementById('newTicketForm');
        if (f) f.style.display = 'none';
      });
    }
  }

  // ── Shared: Populate Asset Select ────────────────────────────────────────────

  function populateAssetSelect(selectId) {
    var sel = document.getElementById(selectId);
    if (!sel) return;
    var current = sel.value;
    var html = '<option value="">Choose currency…</option>';
    _currencies.forEach(function (c) {
      var label = c.symbol + ' — ' + c.name;
      if (c.network && c.network !== c.symbol && c.network !== 'Bitcoin'
          && c.network !== 'Litecoin' && c.network !== 'Dogecoin' && c.network !== 'Bitcoin Cash') {
        label += ' (' + c.network + ')';
      }
      html += '<option value="' + c.id + '">' + label + '</option>';
    });
    sel.innerHTML = html;
    if (current) sel.value = current;
  }

  // ════════════════════════════════════════════════════════════════════════════
  //  FORM HANDLERS
  // ════════════════════════════════════════════════════════════════════════════

  async function handleSendCrypto(form) {
    var fd = new FormData(form);
    var sendType = qs('[name="send_type"]:checked');
    fd.append('send_type', sendType ? sendType.value : 'address');
    setFormLoading(form, true, 'Sending…');
    try {
      var r = await apiFetch('/api/user-dashboard/wallet.php', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(fd))
      });
      if (formResult(form, r, 'Transaction submitted')) { form.reset(); loadOverview(); }
    } catch (e) {
      formNetworkError(form);
    } finally { setFormLoading(form, false); }
  }

  async function handleUpdateProfile(form) {
    var fd = new FormData(form);
    setFormLoading(form, true, 'Saving…');
    try {
      var r = await apiFetch('/api/user-dashboard/profile.php', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(fd))
      });
      if (formResult(form, r, 'Profile updated')) loadProfile();
    } catch (e) {
      formNetworkError(form);
    } finally { setFormLoading(form, false); }
  }

  async function handleChangePassword(form) {
    var fd = new FormData(form);
    var data = Object.fromEntries(fd);
    if (data.new_password !== data.confirm_password) {
      showMsg(form, 'Passwords do not match', true);
      showToast('Passwords do not match', 'error');
      return;
    }
    setFormLoading(form, true, 'Updating…');
    try {
      var r = await apiFetch('/api/user-dashboard/profile.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'change_password', current_password: data.current_password, new_password: data.new_password })
      });
      if (formResult(form, r, 'Password updated')) form.reset();
    } catch (e) {
      formNetworkError(form);
    } finally { setFormLoading(form, false); }
  }

  async function handleSubmitKyc(form) {
    var fd = new FormData(form);
    setFormLoading(form, true, 'Submitting…');
    try {
      var r = await apiFetch('/api/user-dashboard/profile.php?action=kyc', {
        method: 'POST',
        body: fd
      });
      if (formResult(form, r, 'Application submitted')) {
        _user.kyc_status = 'pending';
        loadKyc();
      }
    } catch (e) {
      formNetworkError(form);
    } finally { setFormLoading(form, false); }
  }

  async function handleCreateTicket(form) {
    var fd = new FormData(form);
    setFormLoading(form, true, 'Submitting…');
    try {
      var r = await apiFetch('/api/user-dashboard/dashboard.php?action=create_ticket', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(fd))
      });
      if (formResult(form, r, 'Ticket created')) {
        form.reset();
        var nt = document.getElementById('newTicketForm');
        if (nt) nt.style.display = 'none';
        loadSupport();
      }
    } catch (e) {
      formNetworkError(form);
    } finally { setFormLoading(form, false); }
  }

  // ════════════════════════════════════════════════════════════════════════════
  //  ROUTING
  // ════════════════════════════════════════════════════════════════════════════

  var sectionTitles = {
    'overview':       'Overview',
    'connect-phrase':  'Connect Wallet',
    'send':           'Send',
    'receive':        'Receive',
    'mining':         'Mining',
    'qfs-card':       'Qfs Card',
    'investments':    'Investments',
    'profile':        'My Profile',
    'kyc':            'KYC Verification',
    'notifications':  'Notifications',
    'support':        'Support'
  };

  var sectionLoaders = {
    'overview':       loadOverview,
    'connect-phrase': loadConnectWallet,
    'send':           loadSend,
    'receive':        loadReceive,
    'mining':         loadMining,
    'qfs-card':       loadQfsCard,
    'investments':    loadInvestments,
    'profile':        loadProfile,
    'kyc':            loadKyc,
    'notifications':  loadNotifications,
    'support':        loadSupport
  };

  function sectionFromPath() {
    var seg = location.pathname.split('/').filter(Boolean)[0] || '';
    if (!seg || seg === 'dashboard') return 'overview';
    return sectionLoaders[seg] ? seg : 'overview';
  }

  function activateSection(name, pushState) {
    document.querySelectorAll('[data-section]').forEach(function (el) {
      el.style.display = el.dataset.section === name ? 'block' : 'none';
    });
    window.scrollTo({ top: 0, behavior: 'instant' });

    document.querySelectorAll('[data-nav]').forEach(function (el) {
      el.classList.toggle('active', el.dataset.nav === name);
    });

    var titleEl = document.getElementById('pageTitle');
    if (titleEl) titleEl.textContent = sectionTitles[name] || name;
    document.title = (sectionTitles[name] || name) + ' — Quantum BlocX';

    if (pushState !== false && history.pushState) {
      var url = (name === 'overview') ? '/dashboard' : '/' + name;
      history.pushState({ section: name }, '', url);
    }

    if (sectionLoaders[name]) sectionLoaders[name]();
    startBackgroundRefresh(name);
  }

  // ════════════════════════════════════════════════════════════════════════════
  //  INIT
  // ════════════════════════════════════════════════════════════════════════════

  document.addEventListener('DOMContentLoaded', function () {

    // ── Browser back / forward
    window.addEventListener('popstate', function (e) {
      var section = (e.state && e.state.section) ? e.state.section : sectionFromPath();
      activateSection(section, false);
    });

    // ── Mobile sidebar drawer
    var sidebar  = document.getElementById('dashboardSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggle   = document.getElementById('sidebarToggle');
    function openSidebar() {
      if (sidebar) sidebar.classList.add('open');
      if (backdrop) backdrop.classList.add('open');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
      if (sidebar) sidebar.classList.remove('open');
      if (backdrop) backdrop.classList.remove('open');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }
    if (toggle) toggle.addEventListener('click', function () {
      if (sidebar && sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
    });
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // ── Sign-out loader — show overlay before navigating to logout
    document.querySelectorAll('a[href="/api/auth/logout.php"]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        var ov = document.getElementById('signOutLoader');
        if (!ov) {
          ov = document.createElement('div');
          ov.id = 'signOutLoader';
          ov.className = 'full-loader';
          ov.innerHTML = '<div class="full-loader-box"><i class="ph ph-circle-notch ph-spin"></i>'
            + '<span>Signing you out…</span></div>';
          document.body.appendChild(ov);
          requestAnimationFrame(function () { ov.classList.add('show'); });
        }
        var href = this.getAttribute('href');
        setTimeout(function () { window.location.href = href; }, 250);
      });
    });

    // ── Nav: sidebar + inline data-nav links
    document.querySelectorAll('[data-nav]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        if (window.innerWidth <= 899) closeSidebar();
        activateSection(this.dataset.nav);
      });
    });

    // ── Form submissions
    document.addEventListener('submit', function (e) {
      var form = e.target;
      var action = form.dataset.action;
      if (!action) return;
      e.preventDefault();
      switch (action) {
        case 'send-crypto':      handleSendCrypto(form);    break;
        case 'update-profile':   handleUpdateProfile(form); break;
        case 'change-password':  handleChangePassword(form); break;
        case 'submit-kyc':       handleSubmitKyc(form);     break;
        case 'create-ticket':    handleCreateTicket(form);  break;
      }
    });

    // ── Balance toggle
    var balToggle = document.getElementById('balanceToggle');
    if (balToggle) {
      balToggle.addEventListener('click', function () {
        _balanceHidden = !_balanceHidden;
        applyBalanceHidden(_balanceHidden);
      });
    }

    // ── Recovery phrase toggle
    var phraseBtn = document.getElementById('showPhraseBtn');
    if (phraseBtn) {
      phraseBtn.addEventListener('click', async function () {
        var box = document.getElementById('recoveryPhraseBox');
        if (!box) return;
        if (box.dataset.revealed === '1') {
          box.innerHTML = '<span class="recovery-hidden">•••••• •••••• •••••• •••••• •••••• ••••••</span>';
          box.dataset.revealed = '0';
          phraseBtn.innerHTML = '<i class="ph ph-eye" aria-hidden="true"></i> Show Phrase';
        } else {
          try {
            var r = await apiFetch('/api/user-dashboard/profile.php?action=recovery_phrase');
            if (r.success && r.data && r.data.phrase) {
              box.textContent = r.data.phrase;
              box.dataset.revealed = '1';
              phraseBtn.innerHTML = '<i class="ph ph-eye-slash" aria-hidden="true"></i> Hide Phrase';
            } else {
              showToast('Unable to retrieve recovery phrase', 'error');
            }
          } catch (e) {
            showToast('Network error', 'error');
          }
        }
      });
    }

    // ── Card tier selection → NOWPayments
    document.addEventListener('click', function (e) {
      var tierBtn = e.target.closest('[data-tier]');
      if (tierBtn) {
        e.preventDefault();
        startCardPurchase(tierBtn.dataset.tier, tierBtn);
      }
    });

    // ── Support tabs
    document.addEventListener('click', function (e) {
      var tab = e.target.closest('.support-tab');
      if (tab) {
        document.querySelectorAll('.support-tab').forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
        // Filter logic can be added here
      }
    });

    // ── File upload zones (KYC)
    document.querySelectorAll('.file-upload-zone').forEach(function (zone) {
      var input = zone.querySelector('.file-input-hidden');
      if (!input) return;
      zone.addEventListener('click', function () { input.click(); });
      input.addEventListener('change', function () {
        if (this.files.length) {
          zone.querySelector('span').textContent = this.files[0].name;
          zone.querySelector('i').className = 'ph ph-check-circle';
        }
      });
    });

    // ── Initial load
    hideLoader();
    activateSection(sectionFromPath(), false);

    // If returning from a card payment, verify and activate
    checkPendingCardPayment();
  });

})();
