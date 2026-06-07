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

  // ── Module State ─────────────────────────────────────────────────────────────

  var _user         = {};       // cached user profile
  var _currencies   = [];       // all 29 supported assets
  var _wallets      = [];       // user's wallets with balances
  var _totalBalance = 0;        // aggregate portfolio USD value
  var _cardTier     = 'none';   // none | VirtuElevate | VirtuElite

  // ── Crypto Icon URL ──────────────────────────────────────────────────────────

  var _iconOverrides = { XAUT: 'tether-gold', PAXG: 'pax-gold', RLUSD: 'ripple', SFP: 'safepal' };

  function cryptoIconUrl(symbol) {
    var sym = (symbol || '').toUpperCase();
    var slug = _iconOverrides[sym] || sym.toLowerCase();
    return 'https://cdn.jsdelivr.net/gh/nickvdyck/cryptocurrency-icons@master/128/color/' + slug + '.png';
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

      html += '<div class="asset-row" data-symbol="' + symbol + '" data-network="' + network + '">'
        + '<div class="asset-row-left">'
        + '<img class="asset-icon" src="' + cryptoIconUrl(symbol) + '" alt="' + symbol + '" '
        + 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">'
        + '<div class="asset-icon-fallback" style="display:none;">' + symbol.substring(0, 3) + '</div>'
        + '<div class="asset-name-col">'
        + '<span class="asset-name">' + displayName + '</span>'
        + '<span class="asset-symbol">' + symbol + '</span>'
        + '</div></div>'
        + '<div class="asset-row-center">'
        + '<span class="asset-price">$' + fmt(price, price < 1 ? 6 : 2) + '</span>'
        + '<span class="asset-change ' + changeClass + '">' + changePrefix + change.toFixed(2) + '%</span>'
        + '</div>'
        + '<div class="asset-row-right">'
        + '<span class="asset-holding-usd">$' + fmt(holdingUsd) + '</span>'
        + '<span class="asset-holding-native">' + fmtCrypto(balance) + ' ' + symbol + '</span>'
        + '</div>'
        + badgeHtml
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
    renderWalletProviders('');

    // Search filter
    var search = document.getElementById('walletProviderSearch');
    if (search && !search._bound) {
      search._bound = true;
      search.addEventListener('input', function () {
        renderWalletProviders(this.value.trim().toLowerCase());
      });
    }
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
      return '<div class="wallet-provider-card" data-provider="' + name + '">'
        + '<div class="asset-icon-fallback" style="display:flex;width:4rem;height:4rem;font-size:1.6rem;">' + initial + '</div>'
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

  // ── Receive ──────────────────────────────────────────────────────────────────

  function loadReceive() {
    populateAssetSelect('receiveAsset');
    var sel = document.getElementById('receiveAsset');
    if (sel && !sel._bound) {
      sel._bound = true;
      sel.addEventListener('change', function () {
        showReceiveDetail(this.value);
      });
    }
  }

  function showReceiveDetail(currencyId) {
    var card = document.getElementById('receiveDetailCard');
    if (!currencyId) { if (card) card.style.display = 'none'; return; }

    var cur = _currencies.find(function (c) { return c.id == currencyId; });
    var wallet = _wallets.find(function (w) { return w.currency_id == currencyId; });

    if (!cur) { if (card) card.style.display = 'none'; return; }
    if (card) card.style.display = 'block';

    setText('#receiveAssetName', cur.name || cur.symbol);
    setText('#receiveAssetSymbol', cur.symbol);
    setText('#receiveNetwork', cur.network || '—');
    setText('#receiveConfirmations', (cur.expected_arrival_confirmations || 3) + ' confirmations');
    setText('#receiveUnlock', (cur.expected_unlock_confirmations || 7) + ' confirmations');

    var addrEl = document.getElementById('receiveAddress');
    var address = wallet ? wallet.address : 'No address generated';
    if (addrEl) addrEl.textContent = address;

    // QR code generation (simple text-based, can upgrade to qrcode.js later)
    var canvas = document.getElementById('receiveQrCanvas');
    if (canvas && wallet && wallet.address) {
      try {
        renderSimpleQR(canvas, wallet.address);
      } catch (e) {
        // Fallback: just show address
        canvas.style.display = 'none';
      }
    }
  }

  function renderSimpleQR(canvas, text) {
    // Placeholder: draws a styled box with the address hint
    // In production, use a proper QR library like qrcode.js
    var ctx = canvas.getContext('2d');
    canvas.width = 200; canvas.height = 200;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, 200, 200);
    ctx.fillStyle = '#0a3d2e';
    ctx.fillRect(10, 10, 180, 180);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(15, 15, 170, 170);
    ctx.fillStyle = '#0a3d2e';
    ctx.font = '11px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('QR Code', 100, 95);
    ctx.font = '9px monospace';
    ctx.fillText(text.substring(0, 20) + '…', 100, 115);
  }

  // Copy address button
  document.addEventListener('click', function (e) {
    if (e.target.closest('#copyAddressBtn')) {
      var addr = document.getElementById('receiveAddress');
      if (addr) {
        copyText(addr.textContent, function (ok) {
          showToast(ok ? 'Address copied!' : 'Failed to copy', ok ? 'success' : 'error');
        });
      }
    }
  });

  // ── Swap ─────────────────────────────────────────────────────────────────────

  function loadSwap() {
    populateAssetSelect('swapFrom');
    populateAssetSelect('swapTo');

    // Direction toggle
    var btn = document.getElementById('swapDirectionBtn');
    if (btn && !btn._bound) {
      btn._bound = true;
      btn.addEventListener('click', function () {
        var from = document.getElementById('swapFrom');
        var to   = document.getElementById('swapTo');
        if (from && to) {
          var tmp = from.value;
          from.value = to.value;
          to.value = tmp;
        }
      });
    }

    // MAX button
    var maxBtn = document.getElementById('swapMaxBtn');
    if (maxBtn && !maxBtn._bound) {
      maxBtn._bound = true;
      maxBtn.addEventListener('click', function () {
        var sel = document.getElementById('swapFrom');
        if (!sel || !sel.value) return;
        var wallet = _wallets.find(function (w) { return w.currency_id == sel.value; });
        if (wallet) document.getElementById('swapFromAmount').value = wallet.balance;
      });
    }

    // Update balance hint on from-asset change
    var fromSel = document.getElementById('swapFrom');
    if (fromSel && !fromSel._bound) {
      fromSel._bound = true;
      fromSel.addEventListener('change', function () {
        var wallet = _wallets.find(function (w) { return w.currency_id == this.value; }.bind(this));
        var hint = document.getElementById('swapFromBalance');
        if (hint) hint.textContent = 'Balance: ' + fmtCrypto(wallet ? wallet.balance : 0);
      });
    }
  }

  // ── Mining ───────────────────────────────────────────────────────────────────

  function loadMining() {
    var gate = document.getElementById('miningGate');
    var dash = document.getElementById('miningDashboard');
    if (_cardTier === 'VirtuElevate' || _cardTier === 'VirtuElite') {
      if (gate) gate.style.display = 'none';
      if (dash) dash.style.display = 'block';
    } else {
      if (gate) gate.style.display = 'block';
      if (dash) dash.style.display = 'none';
    }
  }

  // ── QFS Card ─────────────────────────────────────────────────────────────────

  function loadQfsCard() {
    // Card holder name
    document.querySelectorAll('[data-user="name"]').forEach(function (el) {
      el.textContent = _user.full_name || _user.email || '—';
    });
  }

  // ── Investments ──────────────────────────────────────────────────────────────

  function loadInvestments() {
    var gate = document.getElementById('investmentsGate');
    var dash = document.getElementById('investmentsDashboard');
    if (_cardTier === 'VirtuElevate' || _cardTier === 'VirtuElite') {
      if (gate) gate.style.display = 'none';
      if (dash) dash.style.display = 'block';
    } else {
      if (gate) gate.style.display = 'block';
      if (dash) dash.style.display = 'none';
    }
  }

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
  function loadSecurity() { /* form is static, nothing to preload */ }

  // ── 2FA ──────────────────────────────────────────────────────────────────────
  function load2fa() {
    var statusText = document.getElementById('tfaStatusText');
    if (statusText) {
      statusText.textContent = _user.two_fa_enabled ? 'Enabled' : 'Disabled';
    }
  }

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
    try {
      var r = await apiFetch('/api/user-dashboard/wallet.php', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(fd))
      });
      showMsg(form, r.message || (r.success ? 'Transaction submitted' : 'Failed'), !r.success);
      if (r.success) { form.reset(); loadOverview(); }
    } catch (e) {
      showMsg(form, 'Network error — please try again', true);
    }
  }

  async function handleSwapTokens(form) {
    var fd = new FormData(form);
    try {
      var r = await apiFetch('/api/user-dashboard/wallet.php?action=swap', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(fd))
      });
      showMsg(form, r.message || (r.success ? 'Swap completed' : 'Swap failed'), !r.success);
      if (r.success) { form.reset(); loadOverview(); }
    } catch (e) {
      showMsg(form, 'Network error — please try again', true);
    }
  }

  async function handleUpdateProfile(form) {
    var fd = new FormData(form);
    try {
      var r = await apiFetch('/api/user-dashboard/profile.php', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(fd))
      });
      showMsg(form, r.message || (r.success ? 'Profile updated' : 'Update failed'), !r.success);
      if (r.success) loadProfile();
    } catch (e) {
      showMsg(form, 'Network error — please try again', true);
    }
  }

  async function handleChangePassword(form) {
    var fd = new FormData(form);
    var data = Object.fromEntries(fd);
    if (data.new_password !== data.confirm_password) {
      showMsg(form, 'Passwords do not match', true);
      return;
    }
    try {
      var r = await apiFetch('/api/user-dashboard/profile.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'change_password', current_password: data.current_password, new_password: data.new_password })
      });
      showMsg(form, r.message || (r.success ? 'Password updated' : 'Update failed'), !r.success);
      if (r.success) form.reset();
    } catch (e) {
      showMsg(form, 'Network error — please try again', true);
    }
  }

  async function handleSubmitKyc(form) {
    var fd = new FormData(form);
    try {
      var r = await apiFetch('/api/user-dashboard/profile.php?action=kyc', {
        method: 'POST',
        body: fd
      });
      showMsg(form, r.message || (r.success ? 'Application submitted' : 'Submission failed'), !r.success);
      if (r.success) {
        _user.kyc_status = 'pending';
        loadKyc();
      }
    } catch (e) {
      showMsg(form, 'Network error — please try again', true);
    }
  }

  async function handleCreateTicket(form) {
    var fd = new FormData(form);
    try {
      var r = await apiFetch('/api/user-dashboard/dashboard.php?action=create_ticket', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(fd))
      });
      showMsg(form, r.message || (r.success ? 'Ticket created' : 'Failed'), !r.success);
      if (r.success) { form.reset(); document.getElementById('newTicketForm').style.display = 'none'; loadSupport(); }
    } catch (e) {
      showMsg(form, 'Network error — please try again', true);
    }
  }

  // ════════════════════════════════════════════════════════════════════════════
  //  ROUTING
  // ════════════════════════════════════════════════════════════════════════════

  var sectionTitles = {
    'overview':       'Overview',
    'connect-wallet': 'Connect Wallet',
    'send':           'Send',
    'receive':        'Receive',
    'swap':           'Swap',
    'mining':         'Mining',
    'qfs-card':       'Qfs Card',
    'investments':    'Investments',
    'profile':        'My Profile',
    'kyc':            'KYC Verification',
    'notifications':  'Notifications',
    'security':       'Change Password',
    '2fa':            'Two-Factor Authentication',
    'support':        'Support'
  };

  var sectionLoaders = {
    'overview':       loadOverview,
    'connect-wallet': loadConnectWallet,
    'send':           loadSend,
    'receive':        loadReceive,
    'swap':           loadSwap,
    'mining':         loadMining,
    'qfs-card':       loadQfsCard,
    'investments':    loadInvestments,
    'profile':        loadProfile,
    'kyc':            loadKyc,
    'notifications':  loadNotifications,
    'security':       loadSecurity,
    '2fa':            load2fa,
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

    // ── Nav: sidebar + mobile dock + inline data-nav links
    document.querySelectorAll('[data-nav]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        // Close mobile sidebar if open
        var sidebar = document.getElementById('dashboardSidebar');
        if (sidebar && window.innerWidth <= 899) sidebar.classList.remove('open');
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
        case 'swap-tokens':      handleSwapTokens(form);    break;
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

    // ── Card tier selection
    document.addEventListener('click', function (e) {
      var tierBtn = e.target.closest('[data-tier]');
      if (tierBtn) {
        showToast('Card request submitted for ' + tierBtn.dataset.tier, 'success');
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
  });

})();
