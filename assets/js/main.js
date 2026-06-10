/**
 * Project: qblockx
 * File: assets/js/main.js
 * Public-facing JS: nav pill, ticker, crypto prices, scroll animations
 */

document.addEventListener('DOMContentLoaded', function () {

  /* ── Google Translate ──────────────────────────────────────── */
  window.googleTranslateElementInit = function () {
    new google.translate.TranslateElement({
      pageLanguage: 'en',
      includedLanguages: 'af,am,ar,az,be,bg,bn,bs,ca,cs,cy,da,de,el,en,es,et,fa,fi,fr,ga,gl,gu,ha,he,hi,hr,ht,hu,hy,id,is,it,ja,jw,ka,kk,km,kn,ko,la,lo,lt,lv,mk,ml,mn,mr,ms,mt,my,ne,nl,no,pa,pl,pt,ro,ru,sd,si,sk,sl,so,sq,sr,sv,sw,ta,te,th,tl,tr,uk,ur,uz,vi,yi,yo,zh-CN,zh-TW,zu',
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
      autoDisplay: false
    }, 'google-translate-element');
  };

  function loadGoogleTranslate() {
    if (document.getElementById('google-translate-element')) {
      var script   = document.createElement('script');
      script.src   = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
      script.async = true;
      document.body.appendChild(script);
    }
  }



  /* ── Nav Pill Toggle ───────────────────────────────────────── */
  function initNavToggle() {
    var drawer   = document.querySelector('.nav-mobile-drawer');
    var togglers = document.querySelectorAll('[data-nav-toggler]');

    if (!drawer || !togglers.length) return;

    togglers.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var isOpen = drawer.classList.toggle('open');
        drawer.setAttribute('aria-hidden', String(!isOpen));
        // Update all toggle buttons' aria-expanded
        togglers.forEach(function (t) {
          t.setAttribute('aria-expanded', String(isOpen));
        });
        // Lock body scroll when drawer open
        document.body.style.overflow = isOpen ? 'hidden' : '';
      });
    });

    // Close drawer when a mobile nav link is clicked
    drawer.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        togglers.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
        document.body.style.overflow = '';
      });
    });

    // Close on ESC key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && drawer.classList.contains('open')) {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        togglers.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
        document.body.style.overflow = '';
      }
    });
  }

  /* ── Scroll-based nav pill shrink ─────────────────────────── */
  function initNavScroll() {
    var nav = document.querySelector('[data-header]');
    if (!nav) return;
    var threshold = 60;
    window.addEventListener('scroll', function () {
      if (window.scrollY > threshold) {
        nav.classList.add('scrolled');
      } else {
        nav.classList.remove('scrolled');
      }
    }, { passive: true });
  }

  /* ── Plan Tab Toggle ───────────────────────────────────────── */
  function initPlanTabs() {
    var tabs   = document.querySelectorAll('[data-plan-tab]');
    var panels = document.querySelectorAll('.plan-panel');
    if (!tabs.length || !panels.length) return;

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = tab.dataset.planTab;
        tabs.forEach(function (t) {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        panels.forEach(function (p) { p.classList.remove('active'); });
        var targetPanel = document.getElementById('panel-' + target);
        if (targetPanel) targetPanel.classList.add('active');
      });
    });
  }

  /* ── How It Works Tab Toggle ──────────────────────────────── */
  function initHiwTabs() {
    var tabs   = document.querySelectorAll('[data-hiw-tab]');
    var panels = document.querySelectorAll('.hiw-panel');
    if (!tabs.length || !panels.length) return;

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = tab.dataset.hiwTab;
        tabs.forEach(function (t) {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        panels.forEach(function (p) { p.classList.remove('active'); });
        var targetPanel = document.getElementById('hiw-' + target);
        if (targetPanel) targetPanel.classList.add('active');
      });
    });
  }

  /* ── [data-appear] Scroll Animation ───────────────────────── */
  function initAppearOnScroll() {
    var elements = document.querySelectorAll('[data-appear]');
    if (!elements.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    elements.forEach(function (el) { observer.observe(el); });
  }

  /* ── Crypto Price Updates ──────────────────────────────────── */
  async function updateCryptoPrices() {
    // Map from data-coin attr value to our currency symbol
    var symbolMap = {
      'bitcoin':     'BTC',
      'ethereum':    'ETH',
      'binancecoin': 'BNB',
      'usd-coin':    'USDC',
      'solana':      'SOL',
      'ripple':      'XRP',
      'tether':      'USDT'
    };
    var url = '/api/utilities/crypto-prices.php';

    try {
      var res = await fetch(url);
      if (!res.ok) throw new Error('API error');
      var json = await res.json();

      // Endpoint returns { data: { currencies: [ { symbol, current_price_usd, price_change_24h_pct } ] } }
      var currencies = (json.data && json.data.currencies) || [];
      var bySymbol = {};
      currencies.forEach(function (c) { if (!bySymbol[c.symbol]) bySymbol[c.symbol] = c; });

      // Update [data-coin] elements (both stock list and ticker)
      document.querySelectorAll('[data-coin]').forEach(function (el) {
        var sym   = symbolMap[el.dataset.coin];
        var asset = sym && bySymbol[sym];
        if (!asset) return;

        var price  = parseFloat(asset.current_price_usd);
        var change = parseFloat(asset.price_change_24h_pct);
        if (!price) return;
        var formatted = '$' + price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // If it's inside a ticker, show price + change
        if (el.closest('.ticker-track')) {
          var sign   = change >= 0 ? '+' : '';
          var chgStr = sign + change.toFixed(2) + '%';
          el.innerHTML = formatted + ' <small style="color:' + (change >= 0 ? 'var(--color-success)' : 'var(--color-error)') + '">' + chgStr + '</small>';
        } else {
          el.textContent = formatted;
        }
      });

      // Duplicate ticker items with updated prices (for seamless loop)
      syncTickerDuplicates();

    } catch (err) {
      console.warn('[Qblockx] Crypto price fetch failed:', err);
    }
  }

  /* Sync second half of ticker with first half prices */
  function syncTickerDuplicates() {
    var track = document.querySelector('.ticker-track');
    if (!track) return;
    var items = track.querySelectorAll('.ticker-item');
    var half  = Math.floor(items.length / 2);
    for (var i = 0; i < half; i++) {
      var src = items[i].querySelector('.ticker-value');
      var dst = items[i + half]?.querySelector('.ticker-value');
      if (src && dst) dst.innerHTML = src.innerHTML;
    }
  }

  /* ── Button Redirects ─────────────────────────────────────── */
  function setupButtonRedirects() {
    var openAcctBtn = document.getElementById('openacct-btn');
    if (openAcctBtn) {
      openAcctBtn.addEventListener('click', function () {
        window.location.href = '/login';
      });
    }

    var supportBtn = document.getElementById('support-btn');
    if (supportBtn) {
      supportBtn.addEventListener('click', function () {
        window.location.href = '/contact';
      });
    }
  }

  /* ── Ticker Pause on Hover ─────────────────────────────────── */
  function initTickerHover() {
    var ticker = document.querySelector('.metrics-ticker');
    var track  = document.querySelector('.ticker-track');
    if (!ticker || !track) return;

    ticker.addEventListener('mouseenter', function () {
      track.style.animationPlayState = 'paused';
    });
    ticker.addEventListener('mouseleave', function () {
      track.style.animationPlayState = 'running';
    });
  }

  /* ── Hero Carousel ────────────────────────────────────────── */
  function initHeroCarousel() {
    var section  = document.getElementById('heroCarousel');
    if (!section) return;

    var contents = section.querySelectorAll('.hero-content');
    var widgets  = section.querySelectorAll('.hero-widget');
    var steps    = section.querySelectorAll('.hero-step');
    if (!contents.length || contents.length < 2) return;

    var current = 0;
    var total   = contents.length;
    var timer;

    function goTo(index) {
      /* deactivate current */
      contents[current].classList.remove('active');
      if (widgets[current]) widgets[current].classList.remove('active');
      if (steps[current]) {
        steps[current].classList.remove('active');
        steps[current].setAttribute('aria-selected', 'false');
      }

      current = (index + total) % total;

      /* activate new — re-adding .active restarts all CSS animations */
      contents[current].classList.add('active');
      if (widgets[current]) widgets[current].classList.add('active');
      if (steps[current]) {
        steps[current].classList.add('active');
        steps[current].setAttribute('aria-selected', 'true');
      }
    }

    function next() { goTo(current + 1); }
    function startAutoplay() { timer = setInterval(next, 6000); }
    function stopAutoplay()  { clearInterval(timer); }

    /* Step click — jump to that slide, restart timer */
    steps.forEach(function (step, i) {
      step.addEventListener('click', function () {
        stopAutoplay();
        goTo(i);
        startAutoplay();
      });
    });

    /* Pause on hover, resume on leave */
    section.addEventListener('mouseenter', stopAutoplay);
    section.addEventListener('mouseleave', startAutoplay);

    startAutoplay();
  }

  /* ── LightRays WebGL Hero Effect ──────────────────────────── */
  function initLightRays() {
    var container = document.getElementById('heroRays');
    if (!container || typeof window.LightRays === 'undefined') return;
    window.LightRays.init(container, {
      rayPos:         [0.5, 0.0],
      lightSpread:    0.35,
      rayLength:      0.85,
      raysColor:      [0.15, 0.38, 0.92],
      mouseInfluence: 0.08,
      noiseAmount:    0.06,
      distortion:     0.03,
      opacity:        0.18
    });
  }

  /* ── Counter Animations ────────────────────────────────────── */
  function initCounters() {
    var els = document.querySelectorAll('[data-counter]');
    if (!els.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el       = entry.target;
        var target   = parseFloat(el.dataset.counter);
        var prefix   = el.dataset.counterPrefix || '';
        var suffix   = el.dataset.counterSuffix || '';
        var decimals = (target % 1 !== 0) ? 1 : 0;
        var duration = 1800;
        var start    = performance.now();

        function step(now) {
          var elapsed = Math.min((now - start) / duration, 1);
          /* ease-out-cubic */
          var ease    = 1 - Math.pow(1 - elapsed, 3);
          var value   = (target * ease).toFixed(decimals);
          el.textContent = prefix + value + suffix;
          if (elapsed < 1) {
            requestAnimationFrame(step);
          } else {
            el.textContent = prefix + target.toFixed(decimals) + suffix;
          }
        }

        requestAnimationFrame(step);
        observer.unobserve(el);
      });
    }, { threshold: 0.5 });

    els.forEach(function (el) { observer.observe(el); });
  }

  /* ── Auth: Shared Message Helper ──────────────────────────── */
  function showAuthMsg(elementId, msg, isError, isHtml) {
    var el = document.getElementById(elementId);
    if (!el) return;
    if (isHtml) {
      el.innerHTML = msg;
    } else {
      el.textContent = msg;
    }
    el.className     = 'auth-msg ' + (isError ? 'auth-msg--error' : 'auth-msg--success');
    el.style.display = '';
  }

  /* ── Auth: Password Toggle ─────────────────────────────────── */
  function initPasswordToggles() {
    document.querySelectorAll('.input-toggle-pw').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var wrap  = btn.closest('.input-icon-wrap');
        var input = wrap ? wrap.querySelector('input') : null;
        if (!input) return;
        var icon = btn.querySelector('i');
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) icon.className = 'ph ph-eye-slash';
        } else {
          input.type = 'password';
          if (icon) icon.className = 'ph ph-eye';
        }
      });
    });
  }

  /* ── Auth: Login Form ──────────────────────────────────────── */
  function initLoginForm() {
    var form = document.getElementById('loginForm');
    if (!form) return;

    // Show signed-out toast when redirected from logout
    (function () {
      if (new URLSearchParams(window.location.search).get('loggedout') === '1') {
        showToast("You've been signed out.", 'info');
        if (history.replaceState) history.replaceState(null, '', '/login');
      }
    })();

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var btn = document.getElementById('loginBtn');
      if (btn) { btn.disabled = true; btn.textContent = 'Signing in…'; }
      showPageLoader();

      var data = {
        email:    document.getElementById('email').value.trim(),
        password: document.getElementById('password').value
      };

      try {
        var res    = await fetch('/api/auth/user-login.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(data)
        });
        var result = await res.json();

        if (result.success) {
          showToast('Welcome back!', 'success');
          // Let the toast render before navigating away
          setTimeout(function () { window.location.href = '/dashboard'; }, 900);
        } else {
          hidePageLoader();
          if (btn) { btn.disabled = false; btn.textContent = 'Sign In'; }
          if (result.unverified) {
            try { sessionStorage.setItem('pendingVerifyEmail', data.email); } catch (ignore) {}
            var msg = (result.message || 'Please verify your email.') +
              ' <a href="/verify-email" style="color:inherit;font-weight:600;text-decoration:underline;">Go to verification page →</a>';
            showAuthMsg('authMsg', msg, true, true);
          } else {
            showAuthMsg('authMsg', result.message || 'Invalid credentials. Please try again.', true);
          }
        }
      } catch (err) {
        hidePageLoader();
        if (btn) { btn.disabled = false; btn.textContent = 'Sign In'; }
        showAuthMsg('authMsg', 'A network error occurred. Please try again.', true);
      }
    });
  }

  /* ── Auth: Register Form ───────────────────────────────────── */
  function initRegisterForm() {
    var form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var password = document.getElementById('password').value;
      var confirm  = document.getElementById('confirm').value;

      if (password !== confirm) {
        showAuthMsg('authMsg', 'Passwords do not match.', true);
        return;
      }
      if (password.length < 8) {
        showAuthMsg('authMsg', 'Password must be at least 8 characters.', true);
        return;
      }

      var btn = document.getElementById('registerBtn');
      if (btn) { btn.disabled = true; btn.textContent = 'Creating account…'; }
      showPageLoader();

      var currencyEl = document.getElementById('currency');
      var data  = {
        email:     document.getElementById('email').value.trim(),
        password:  password,
        full_name: document.getElementById('full_name').value.trim(),
        currency:  currencyEl ? currencyEl.value : 'USD'
      };

      try {
        var res    = await fetch('/api/auth/user-register.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(data)
        });
        var result = await res.json();

        if (result.success) {
          try { sessionStorage.setItem('pendingVerifyEmail', data.email); } catch (ignore) {}
          showToast('Account created! Check your email for your verification code.', 'success');
          setTimeout(function () { window.location.href = '/verify-email'; }, 1200);
        } else {
          hidePageLoader();
          if (btn) { btn.disabled = false; btn.textContent = 'Create Account'; }
          showAuthMsg('authMsg', result.message || 'Registration failed. Please try again.', true);
        }
      } catch (err) {
        hidePageLoader();
        if (btn) { btn.disabled = false; btn.textContent = 'Create Account'; }
        showAuthMsg('authMsg', 'A network error occurred. Please try again.', true);
      }
    });
  }

  /* ── Auth: Forgot Password Form ────────────────────────────── */
  function initForgotPasswordForm() {
    var form = document.getElementById('forgotForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      showPageLoader();

      var data = { email: document.getElementById('email').value.trim() };

      try {
        var res    = await fetch('/api/auth/user-forgot-password.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(data)
        });
        var result = await res.json();

        hidePageLoader();
        if (result.success) {
          showToast('Reset link sent to your email.', 'info');
        } else {
          showAuthMsg('authMsg', result.message || 'Could not send reset link. Please try again.', true);
        }
      } catch (err) {
        hidePageLoader();
        showAuthMsg('authMsg', 'A network error occurred. Please try again.', true);
      }
    });
  }

  /* ── Auth: Reset Password Form ─────────────────────────────── */
  function initResetPasswordForm() {
    var form = document.getElementById('resetForm');
    if (!form) return;

    var token = new URLSearchParams(window.location.search).get('token');

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var password = document.getElementById('password').value;
      var confirm  = document.getElementById('confirm').value;

      if (password !== confirm) {
        showAuthMsg('authMsg', 'Passwords do not match.', true);
        return;
      }
      if (!token) {
        showAuthMsg('authMsg', 'Invalid or missing reset token. Please request a new reset link.', true);
        return;
      }

      showPageLoader();

      try {
        var res    = await fetch('/api/auth/user-reset-password.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ token: token, password: password })
        });
        var result = await res.json();

        hidePageLoader();
        if (result.success) {
          showToast('Password reset successfully. Please sign in.', 'success');
          setTimeout(function () { window.location.href = '/login'; }, 1200);
        } else {
          showAuthMsg('authMsg', result.message || 'Reset failed. Please try again.', true);
        }
      } catch (err) {
        hidePageLoader();
        showAuthMsg('authMsg', 'A network error occurred. Please try again.', true);
      }
    });
  }

  /* ── Auth: Verify Email Page ───────────────────────────────── */
  function initVerifyEmailPage() {
    var panel = document.getElementById('verifyPanel');
    if (!panel) return;

    var email     = '';
    var subtext   = document.getElementById('verifySubtext');
    var msgEl     = document.getElementById('verifyMsg');
    var resendMsg = document.getElementById('resendMsg');

    try { email = sessionStorage.getItem('pendingVerifyEmail') || ''; } catch (ignore) {}

    // Show the email address in the subtext if we have it
    if (email && subtext) {
      subtext.textContent = 'We sent a 6-digit code to ' + email + '. It expires in 15 minutes.';
    }

    // ── Code form submission ──────────────────────────────────
    var form = document.getElementById('verifyCodeForm');
    if (form) {
      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var code    = (document.getElementById('verifyCode') || {}).value || '';
        code = code.trim();

        // Reset message
        if (msgEl) { msgEl.style.display = 'none'; msgEl.textContent = ''; }

        if (!/^\d{6}$/.test(code)) {
          if (msgEl) {
            msgEl.textContent   = 'Please enter a valid 6-digit code.';
            msgEl.className     = 'auth-msg auth-msg--error';
            msgEl.style.display = '';
          }
          return;
        }

        if (!email) {
          if (msgEl) {
            msgEl.textContent   = 'Session expired. Please register again.';
            msgEl.className     = 'auth-msg auth-msg--error';
            msgEl.style.display = '';
          }
          return;
        }

        showPageLoader();

        try {
          var res    = await fetch('/api/auth/user-verify-email.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email: email, code: code })
          });
          var result = await res.json();

          hidePageLoader();
          if (result.success) {
            try { sessionStorage.removeItem('pendingVerifyEmail'); } catch (ignore) {}
            showToast('Email verified! Logging you in…', 'success');
            setTimeout(function () { window.location.href = '/login'; }, 1200);
          } else {
            if (msgEl) {
              msgEl.textContent   = result.message || 'Verification failed. Please try again.';
              msgEl.className     = 'auth-msg auth-msg--error';
              msgEl.style.display = '';
            }
          }
        } catch (err) {
          hidePageLoader();
          if (msgEl) {
            msgEl.textContent   = 'A network error occurred. Please try again.';
            msgEl.className     = 'auth-msg auth-msg--error';
            msgEl.style.display = '';
          }
        }
      });
    }

    // ── Resend link ───────────────────────────────────────────
    var resendLink = document.getElementById('resendLink');
    if (resendLink) {
      resendLink.addEventListener('click', async function (e) {
        e.preventDefault();
        if (resendMsg) { resendMsg.style.display = 'none'; resendMsg.textContent = ''; }

        if (!email) {
          if (resendMsg) {
            resendMsg.textContent   = 'Session expired. Please register again.';
            resendMsg.className     = 'auth-msg auth-msg--error';
            resendMsg.style.display = '';
          }
          return;
        }

        resendLink.textContent        = 'Sending…';
        resendLink.style.pointerEvents = 'none';

        try {
          var res    = await fetch('/api/auth/user-resend-verification.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email: email })
          });
          var result = await res.json();
          if (resendMsg) {
            resendMsg.textContent   = result.message;
            resendMsg.className     = 'auth-msg ' + (result.success ? 'auth-msg--success' : 'auth-msg--error');
            resendMsg.style.display = '';
          }
        } catch (err) {
          if (resendMsg) {
            resendMsg.textContent   = 'Network error. Please try again.';
            resendMsg.className     = 'auth-msg auth-msg--error';
            resendMsg.style.display = '';
          }
        }

        resendLink.textContent        = 'Resend code';
        resendLink.style.pointerEvents = '';
      });
    }
  }

  /* ── Toast Notifications ───────────────────────────────────── */
  function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
      document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'toast toast--' + type;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(8px)';
      toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      setTimeout(function () { toast.remove(); }, 300);
    }, 4000);
  }
  window.showToast = showToast;

  /* ── Page Loader ────────────────────────────────────────────── */
  function initPageLoader() {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;
    window.addEventListener('load', function () {
      loader.classList.add('loader-done');
      setTimeout(function () { loader.style.display = 'none'; }, 500);
    });
    setTimeout(function () {
      if (!loader.classList.contains('loader-done')) {
        loader.classList.add('loader-done');
        setTimeout(function () { loader.style.display = 'none'; }, 500);
      }
    }, 3000);
  }

  /* ── Page Loader: show/hide API ────────────────────────────── */
  function getOrCreateLoader() {
    var loader = document.getElementById('pageLoader');
    if (!loader) {
      loader = document.createElement('div');
      loader.id = 'pageLoader';
      loader.className = 'page-loader loader-done';
      loader.setAttribute('aria-hidden', 'true');
      loader.innerHTML = '<img src="/assets/images/logo/logoblue.png" class="loader-logo" alt="Loading Qblockx">';
      document.body.appendChild(loader);
    }
    return loader;
  }
  function showPageLoader() {
    var loader = getOrCreateLoader();
    loader.style.display = '';
    loader.style.opacity = '1';
    loader.classList.remove('loader-done');
  }
  function hidePageLoader() {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;
    loader.classList.add('loader-done');
    setTimeout(function () { loader.style.display = 'none'; }, 500);
  }
  window.showPageLoader = showPageLoader;
  window.hidePageLoader = hidePageLoader;

  /* ── Init ──────────────────────────────────────────────────── */
  // Run each initializer in isolation. A failure in one (e.g. WebGL light
  // rays, the hero carousel, Google Translate) must never prevent the
  // remaining initializers — especially the auth form handlers — from binding.
  function safeInit(fn) {
    try { fn(); }
    catch (err) { if (window.console && console.error) console.error('[init] ' + (fn.name || 'anonymous') + ' failed:', err); }
  }

  [
    initPageLoader,
    loadGoogleTranslate,
    initNavToggle,
    initNavScroll,
    initPlanTabs,
    initHiwTabs,
    initAppearOnScroll,
    initHeroCarousel,
    initLightRays,
    initCounters,
    updateCryptoPrices,
    setupButtonRedirects,
    initTickerHover,
    // Auth pages
    initPasswordToggles,
    initLoginForm,
    initRegisterForm,
    initForgotPasswordForm,
    initResetPasswordForm,
    initVerifyEmailPage
  ].forEach(safeInit);

  // Refresh prices every 60 seconds
  setInterval(updateCryptoPrices, 60000);
});
