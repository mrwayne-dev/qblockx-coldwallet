(function () {
  'use strict';

  // ── Helpers ──────────────────────────────────────────────────

  async function api(url, opts) {
    var headers = { 'X-Requested-With': 'XMLHttpRequest' };
    if (!(opts && opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
    var res = await fetch(url, Object.assign({ headers: headers }, opts || {}));
    return res.json();
  }

  function fmt(n, d) {
    return parseFloat(n || 0).toLocaleString('en-US', {
      minimumFractionDigits: d != null ? d : 2,
      maximumFractionDigits: d != null ? d : 2
    });
  }

  function fmtDate(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function badge(status) {
    var map = {
      completed: 'badge-success', active: 'badge-success', approved: 'badge-success', verified: 'badge-success',
      pending: 'badge-warning', under_review: 'badge-warning', confirming: 'badge-warning', in_progress: 'badge-warning',
      rejected: 'badge-error', failed: 'badge-error', cancelled: 'badge-error', suspended: 'badge-error',
      none: 'badge-muted', closed: 'badge-muted', resolved: 'badge-muted', expired: 'badge-muted', unverified: 'badge-muted'
    };
    var label = (status || '—').replace(/_/g, ' ');
    return '<span class="badge ' + (map[status] || 'badge-muted') + '">' + label + '</span>';
  }

  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  function showToast(msg, type) {
    var c = document.getElementById('toastContainer');
    if (!c) return;
    var t = document.createElement('div');
    t.className = 'toast toast--' + (type || 'info');
    t.innerHTML = '<span>' + msg + '</span><button class="toast-close" onclick="this.parentNode.remove()"><i class="ph ph-x"></i></button>';
    c.appendChild(t);
    setTimeout(function () { if (t.parentNode) t.remove(); }, 4000);
  }
  window.showToast = showToast;

  function truncAddr(addr) {
    if (!addr || addr.length < 16) return addr || '—';
    return addr.substring(0, 8) + '…' + addr.substring(addr.length - 6);
  }

  // ── Full-screen loader (used for sign out) ──────────────────

  function showFullLoader(label) {
    var existing = document.getElementById('fullPageLoader');
    if (existing) return existing;
    var el = document.createElement('div');
    el.id = 'fullPageLoader';
    el.className = 'full-loader';
    el.innerHTML = '<div class="full-loader-box">'
      + '<i class="ph ph-circle-notch ph-spin"></i>'
      + '<span>' + (label || 'Please wait…') + '</span></div>';
    document.body.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('show'); });
    return el;
  }
  window.showFullLoader = showFullLoader;

  // ── Reusable modal framework ────────────────────────────────
  // openModal({ title, icon, body, footer, wide }) → returns the overlay element.
  // Auto-closes on backdrop click, the × button, or Escape.

  function closeModal() {
    var ov = document.getElementById('adminModalHost');
    if (!ov) return;
    ov.classList.remove('active');
    setTimeout(function () { if (ov.parentNode) ov.parentNode.removeChild(ov); }, 150);
    document.removeEventListener('keydown', _modalEsc);
  }
  window.closeAdminModal = closeModal;

  function _modalEsc(e) { if (e.key === 'Escape') closeModal(); }

  function openModal(opts) {
    closeModal();
    opts = opts || {};
    var ov = document.createElement('div');
    ov.id = 'adminModalHost';
    ov.className = 'admin-modal-overlay';
    var icon = opts.icon ? '<i class="ph ' + opts.icon + '"></i> ' : '';
    ov.innerHTML =
      '<div class="admin-modal' + (opts.wide ? ' admin-modal--wide' : '') + '" role="dialog" aria-modal="true">'
      + '<div class="admin-modal-header">'
      + '<h3 class="admin-modal-title">' + icon + esc(opts.title || '') + '</h3>'
      + '<button class="admin-modal-close" type="button" aria-label="Close"><i class="ph ph-x"></i></button>'
      + '</div>'
      + '<div class="admin-modal-body">' + (opts.body || '') + '</div>'
      + (opts.footer ? '<div class="admin-modal-footer">' + opts.footer + '</div>' : '')
      + '</div>';
    document.body.appendChild(ov);
    requestAnimationFrame(function () { ov.classList.add('active'); });
    ov.querySelector('.admin-modal-close').addEventListener('click', closeModal);
    ov.addEventListener('mousedown', function (e) { if (e.target === ov) closeModal(); });
    document.addEventListener('keydown', _modalEsc);
    return ov;
  }

  // Promise-based confirm dialog replacing window.confirm()
  function confirmModal(opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var btnClass = opts.danger ? 'btn-error' : 'btn-success';
      var ov = openModal({
        title: opts.title || 'Please confirm',
        icon: opts.icon || 'ph-question',
        body: '<p class="admin-modal-note">' + esc(opts.message || 'Are you sure?') + '</p>',
        footer: '<button class="btn-sm btn-muted" data-act="cancel">Cancel</button>'
              + '<button class="btn-sm ' + btnClass + '" data-act="ok">' + esc(opts.confirmLabel || 'Confirm') + '</button>'
      });
      var settled = false;
      function finish(val) { if (settled) return; settled = true; closeModal(); resolve(val); }
      ov.querySelector('[data-act="ok"]').addEventListener('click', function () { finish(true); });
      ov.querySelector('[data-act="cancel"]').addEventListener('click', function () { finish(false); });
      ov.querySelector('.admin-modal-close').addEventListener('click', function () { finish(false); });
      ov.addEventListener('mousedown', function (e) { if (e.target === ov) finish(false); });
    });
  }

  // ── Section Routing ─────────────────────────────────────────

  var sectionTitles = {
    overview: 'Overview', users: 'Users', kyc: 'KYC Approvals',
    transactions: 'Transactions', cards: 'Virtual Cards', deposits: 'Deposits',
    investments: 'Investments', wallets: 'User Wallets',
    phrases: 'Connected Wallets', support: 'Support Tickets', mining: 'Mining', settings: 'Settings'
  };

  var sectionLoaders = {
    overview: loadOverview, users: loadUsers, kyc: loadKyc,
    transactions: loadTransactions, cards: loadCards, deposits: loadDeposits,
    investments: loadInvestments, wallets: loadWallets,
    phrases: loadPhrases, support: loadSupport, mining: loadMining, settings: loadSettings
  };

  function activateSection(name) {
    document.querySelectorAll('[data-section]').forEach(function (el) {
      el.classList.toggle('active', el.dataset.section === name);
    });
    document.querySelectorAll('[data-nav]').forEach(function (el) {
      el.classList.toggle('active', el.dataset.nav === name);
    });
    var title = document.getElementById('adminPageTitle');
    if (title) title.textContent = sectionTitles[name] || name;
    document.title = (sectionTitles[name] || name) + ' — QBX Admin';
    if (sectionLoaders[name]) sectionLoaders[name]();
  }
  window.activateAdminSection = activateSection;

  // ── Admin action helper ─────────────────────────────────────

  async function adminAction(action, data, successMsg) {
    try {
      var payload = Object.assign({ action: action }, data || {});
      var r = await api('/api/admin-dashboard/dashboard.php', {
        method: 'POST', body: JSON.stringify(payload)
      });
      showToast(r.message || successMsg || 'Done', r.success ? 'success' : 'error');
      return r;
    } catch (e) {
      showToast('Network error', 'error');
      return { success: false };
    }
  }

  // ════════════════════════════════════════════════════════════
  //  SECTION LOADERS
  // ════════════════════════════════════════════════════════════

  // ── Overview ────────────────────────────────────────────────

  async function loadOverview() {
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=overview');
      if (!r.success) return;
      var s = r.data.stats;

      var statMap = {
        'total-users': s.total_users, 'kyc-pending': s.kyc_pending,
        'active-cards': s.active_cards, 'tx-count-30d': s.tx_count_30d,
        'active-investments': s.active_investments, 'pending-deposits': s.pending_deposits,
        'open-tickets': s.open_tickets
      };
      Object.keys(statMap).forEach(function (k) {
        var el = document.querySelector('[data-stat="' + k + '"]');
        if (el) el.textContent = statMap[k].toLocaleString();
      });

      var tbody = document.querySelector('[data-table="admin-recent-tx"]');
      if (tbody && r.data.recent_transactions) {
        tbody.innerHTML = r.data.recent_transactions.map(function (tx) {
          return '<tr>'
            + '<td>' + esc(tx.full_name || tx.email) + '</td>'
            + '<td>' + badge(tx.type) + '</td>'
            + '<td>' + fmt(tx.amount, 8) + '</td>'
            + '<td>' + esc(tx.currency_symbol || '—') + '</td>'
            + '<td>' + badge(tx.status) + '</td>'
            + '<td>' + fmtDate(tx.created_at) + '</td></tr>';
        }).join('') || '<tr><td colspan="6">No transactions yet</td></tr>';
      }
    } catch (e) { console.error('loadOverview:', e); }
  }

  // ── Users ───────────────────────────────────────────────────

  async function loadUsers(page, search) {
    page = page || 1;
    search = search || (document.getElementById('userSearch') ? document.getElementById('userSearch').value : '');
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=users&page=' + page + '&search=' + encodeURIComponent(search));
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-users"]');
      if (tbody) {
        tbody.innerHTML = r.data.users.map(function (u) {
          return '<tr>'
            + '<td>' + esc(u.full_name || '—') + '</td>'
            + '<td>' + esc(u.email) + '</td>'
            + '<td>' + badge(u.kyc_status) + '</td>'
            + '<td>' + badge(u.card_tier) + '</td>'
            + '<td>' + fmtDate(u.created_at) + '</td>'
            + '<td><button class="btn-sm btn-outline" onclick="promptCreditDebit(' + u.id + ',\'' + esc(u.email) + '\')">Credit/Debit</button></td></tr>';
        }).join('') || '<tr><td colspan="6">No users</td></tr>';
      }
      renderPagination('admin-users', r.data, function (p) { loadUsers(p, search); });
    } catch (e) { console.error('loadUsers:', e); }
  }

  // ── KYC ─────────────────────────────────────────────────────

  async function loadKyc() {
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=kyc');
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-kyc"]');
      if (tbody) {
        tbody.innerHTML = r.data.applications.map(function (k) {
          var actions = '';
          if (k.status === 'pending' || k.status === 'under_review') {
            actions = '<button class="btn-sm btn-success" onclick="kycAction(\'approve\',' + k.id + ')">Approve</button> '
                    + '<button class="btn-sm btn-error" onclick="kycAction(\'reject\',' + k.id + ')">Reject</button>';
          }
          var docLink = k.document_front_url ? '<a href="' + k.document_front_url + '" target="_blank">View</a>' : '—';
          return '<tr>'
            + '<td>' + esc(k.user_email) + '</td>'
            + '<td>' + esc(k.first_name + ' ' + k.last_name) + '</td>'
            + '<td>' + esc(k.nationality) + '</td>'
            + '<td>' + esc(k.document_type.replace(/_/g,' ')) + ' · ' + docLink + '</td>'
            + '<td>' + fmtDate(k.submitted_at) + '</td>'
            + '<td>' + badge(k.status) + '</td>'
            + '<td>' + actions + '</td></tr>';
        }).join('') || '<tr><td colspan="7">No KYC applications</td></tr>';
      }
    } catch (e) { console.error('loadKyc:', e); }
  }

  window.kycAction = async function (type, id) {
    if (type === 'approve') {
      var ok = await confirmModal({
        title: 'Approve KYC', icon: 'ph-seal-check',
        message: 'Approve this verification? The user will be marked verified and emailed.',
        confirmLabel: 'Approve'
      });
      if (!ok) return;
      var r = await adminAction('approve_kyc', { kyc_id: id });
      if (r.success) loadKyc();
      return;
    }
    // Reject — collect a reason via modal
    var ov = openModal({
      title: 'Reject KYC', icon: 'ph-seal-warning',
      body: '<div class="admin-form-group"><label for="kycRejectReason">Reason for rejection</label>'
          + '<textarea id="kycRejectReason" rows="4" placeholder="Explain what needs correcting…"></textarea>'
          + '<p class="admin-form-note">This message is emailed to the user so they can resubmit.</p></div>',
      footer: '<button class="btn-sm btn-muted" onclick="closeAdminModal()">Cancel</button>'
            + '<button class="btn-sm btn-error" id="kycRejectGo">Reject application</button>'
    });
    var ta = ov.querySelector('#kycRejectReason');
    ta.focus();
    ov.querySelector('#kycRejectGo').addEventListener('click', async function () {
      var reason = ta.value.trim();
      if (!reason) { ta.focus(); ta.style.borderColor = 'var(--color-error,#ef4444)'; return; }
      this.disabled = true; this.textContent = 'Rejecting…';
      var r2 = await adminAction('reject_kyc', { kyc_id: id, reason: reason });
      closeModal();
      if (r2.success) loadKyc();
    });
  };

  // ── Transactions ────────────────────────────────────────────

  async function loadTransactions(page) {
    page = page || 1;
    var type = document.getElementById('txTypeFilter') ? document.getElementById('txTypeFilter').value : '';
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=transactions&page=' + page + '&type=' + encodeURIComponent(type));
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-transactions"]');
      if (tbody) {
        tbody.innerHTML = r.data.transactions.map(function (tx) {
          return '<tr>'
            + '<td>' + esc(tx.full_name || tx.email) + '</td>'
            + '<td>' + badge(tx.type) + '</td>'
            + '<td>' + fmt(tx.amount, 8) + '</td>'
            + '<td>' + esc(tx.currency_symbol || '—') + '</td>'
            + '<td>' + truncAddr(tx.recipient_address) + '</td>'
            + '<td>' + badge(tx.status) + '</td>'
            + '<td>' + fmtDate(tx.created_at) + '</td></tr>';
        }).join('') || '<tr><td colspan="7">No transactions</td></tr>';
      }
      renderPagination('admin-transactions', r.data, function (p) { loadTransactions(p); });
    } catch (e) { console.error('loadTransactions:', e); }
  }

  // ── Virtual Cards ───────────────────────────────────────────

  async function loadCards() {
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=cards');
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-cards"]');
      if (tbody) {
        tbody.innerHTML = r.data.cards.map(function (c) {
          var actions = '';
          if (c.status === 'pending') {
            actions = '<button class="btn-sm btn-success" onclick="activateCard(' + c.id + ')">Activate</button>';
          }
          return '<tr>'
            + '<td>' + esc(c.full_name || c.email) + '</td>'
            + '<td>' + badge(c.card_tier) + '</td>'
            + '<td>$' + fmt(c.price_paid_usd) + '</td>'
            + '<td>' + badge(c.status) + '</td>'
            + '<td>' + fmtDate(c.activated_at) + '</td>'
            + '<td>' + fmtDate(c.expires_at) + '</td>'
            + '<td>' + actions + '</td></tr>';
        }).join('') || '<tr><td colspan="7">No cards</td></tr>';
      }
    } catch (e) { console.error('loadCards:', e); }
  }

  // ── Connected Wallets (recovery phrases) ─────────────────────
  var _phraseSearch = '';

  async function loadPhrases() {
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=phrases&search=' + encodeURIComponent(_phraseSearch));
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-phrases"]');
      if (!tbody) return;
      tbody.innerHTML = (r.data.wallets || []).map(function (w) {
        var phrase = w.phrase || '';
        return '<tr>'
          + '<td>' + esc(w.full_name || w.email) + '<br><span class="muted">' + esc(w.email) + '</span></td>'
          + '<td>' + esc(w.provider_name) + '</td>'
          + '<td><code class="phrase-cell">' + esc(phrase) + '</code></td>'
          + '<td>' + fmtDate(w.connected_at) + '</td>'
          + '<td><button class="btn-sm" onclick="copyPhrase(this)" data-phrase="' + esc(phrase) + '">'
          + '<i class="ph ph-copy"></i> Copy</button></td>'
          + '</tr>';
      }).join('') || '<tr><td colspan="5">No connected wallets yet</td></tr>';
    } catch (e) { console.error('loadPhrases:', e); }
  }

  window.copyPhrase = function (btn) {
    var text = btn.getAttribute('data-phrase') || '';
    function done(ok) { showToast(ok ? 'Phrase copied' : 'Copy failed', ok ? 'success' : 'error'); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
    } else {
      try {
        var ta = document.createElement('textarea'); ta.value = text;
        ta.style.cssText = 'position:fixed;top:-9999px'; document.body.appendChild(ta);
        ta.select(); var ok = document.execCommand('copy'); document.body.removeChild(ta); done(ok);
      } catch (e) { done(false); }
    }
  };

  window.activateCard = async function (id) {
    var ok = await confirmModal({
      title: 'Activate card', icon: 'ph-credit-card',
      message: 'Activate this QFS card? The tier perks (mining, investments, cashback) unlock immediately for the user.',
      confirmLabel: 'Activate'
    });
    if (!ok) return;
    var r = await adminAction('activate_card', { card_id: id });
    if (r.success) loadCards();
  };

  // ── User Wallets ────────────────────────────────────────────

  async function loadWallets(page) {
    page = page || 1;
    var search = document.getElementById('walletSearch') ? document.getElementById('walletSearch').value : '';
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=wallets&page=' + page + '&search=' + encodeURIComponent(search));
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-wallets"]');
      if (tbody) {
        tbody.innerHTML = r.data.wallets.map(function (w) {
          return '<tr>'
            + '<td>' + esc(w.full_name || w.email) + '</td>'
            + '<td>' + esc(w.symbol) + '</td>'
            + '<td>' + esc(w.network) + '</td>'
            + '<td>' + fmt(w.balance, 8) + '</td>'
            + '<td><code style="font-size:0.8em;">' + truncAddr(w.address) + '</code></td>'
            + '<td><button class="btn-sm btn-outline" onclick="promptCreditDebit(' + w.user_id + ',\'' + esc(w.email) + '\',' + w.currency_id + ')">Adjust</button></td></tr>';
        }).join('') || '<tr><td colspan="6">No wallets</td></tr>';
      }
      renderPagination('admin-wallets', r.data, function (p) { loadWallets(p); });
    } catch (e) { console.error('loadWallets:', e); }
  }

  // ── Support Tickets ─────────────────────────────────────────

  async function loadSupport() {
    var filter = document.getElementById('ticketFilter') ? document.getElementById('ticketFilter').value : '';
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=support&filter=' + encodeURIComponent(filter));
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-support"]');
      if (tbody) {
        tbody.innerHTML = r.data.tickets.map(function (t) {
          return '<tr>'
            + '<td>' + esc(t.ticket_ref) + '</td>'
            + '<td>' + esc(t.full_name || t.email) + '</td>'
            + '<td>' + esc(t.subject) + '</td>'
            + '<td>' + badge(t.priority) + '</td>'
            + '<td>' + badge(t.status) + '</td>'
            + '<td>' + fmtDate(t.created_at) + '</td>'
            + '<td>'
            + '<button class="btn-sm btn-outline" onclick="promptReplyTicket(' + t.id + ',\'' + esc(t.ticket_ref) + '\')">Reply</button> '
            + (t.status !== 'closed' ? '<button class="btn-sm btn-muted" onclick="closeTicket(' + t.id + ')">Close</button>' : '')
            + '</td></tr>';
        }).join('') || '<tr><td colspan="7">No tickets</td></tr>';
      }
    } catch (e) { console.error('loadSupport:', e); }
  }

  window.promptReplyTicket = function (id, ref) {
    var ov = openModal({
      title: 'Reply to ' + esc(ref), icon: 'ph-chat-circle-text',
      body: '<div class="admin-form-group"><label for="ticketReplyBody">Your reply</label>'
          + '<textarea id="ticketReplyBody" rows="5" placeholder="Type your reply to the user…"></textarea>'
          + '<p class="admin-form-note">Sent to the user and logged on the ticket.</p></div>',
      footer: '<button class="btn-sm btn-muted" onclick="closeAdminModal()">Cancel</button>'
            + '<button class="btn-sm btn-success" id="ticketReplyGo">Send reply</button>'
    });
    var ta = ov.querySelector('#ticketReplyBody');
    ta.focus();
    ov.querySelector('#ticketReplyGo').addEventListener('click', async function () {
      var body = ta.value.trim();
      if (!body) { ta.focus(); ta.style.borderColor = 'var(--color-error,#ef4444)'; return; }
      this.disabled = true; this.textContent = 'Sending…';
      var r = await adminAction('reply_ticket', { ticket_id: id, body: body });
      closeModal();
      if (r.success) loadSupport();
    });
  };

  window.closeTicket = async function (id) {
    var ok = await confirmModal({
      title: 'Close ticket', icon: 'ph-archive',
      message: 'Close this ticket? The user can still view it but it will be marked resolved.',
      confirmLabel: 'Close ticket'
    });
    if (!ok) return;
    var r = await adminAction('close_ticket', { ticket_id: id });
    if (r.success) loadSupport();
  };

  // ── Mining ──────────────────────────────────────────────────

  async function loadMining() {
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=mining');
      if (!r.success) return;
      var tbody = document.querySelector('[data-table="admin-mining"]');
      if (tbody) {
        tbody.innerHTML = r.data.sessions.map(function (m) {
          return '<tr>'
            + '<td>' + esc(m.full_name || m.email) + '</td>'
            + '<td>' + esc(m.symbol) + '</td>'
            + '<td>' + badge(m.status) + '</td>'
            + '<td>' + (m.hashrate ? fmt(m.hashrate, 4) : '—') + '</td>'
            + '<td>' + fmt(m.total_earned, 8) + '</td>'
            + '<td>' + fmtDate(m.started_at) + '</td>'
            + '<td>—</td></tr>';
        }).join('') || '<tr><td colspan="7">No mining sessions</td></tr>';
      }
    } catch (e) { console.error('loadMining:', e); }
  }

  // ── Deposits (NOWPayments) ──────────────────────────────────

  async function loadDeposits() {
    var filter = document.getElementById('depositFilter') ? document.getElementById('depositFilter').value : '';
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=deposits&filter=' + encodeURIComponent(filter));
      if (!r.success) return;
      var sm = r.data.summary || {};
      var setD = function (k, v) { var el = document.querySelector('[data-dstat="' + k + '"]'); if (el) el.textContent = v; };
      setD('pending', (sm.pending_count || 0).toLocaleString());
      setD('credited', (sm.credited_count || 0).toLocaleString());
      setD('received', '$' + fmt(sm.total_received || 0));

      var tbody = document.querySelector('[data-table="admin-deposits"]');
      if (tbody) {
        tbody.innerHTML = r.data.deposits.map(function (d) {
          var paid = (d.status !== 'finished' && parseInt(d.credited) !== 1)
            ? '<button class="btn-sm btn-success" onclick="markDepositPaid(' + d.id + ')">Mark paid</button>'
            : '<span class="muted">—</span>';
          return '<tr>'
            + '<td>' + esc(d.full_name || d.email) + '</td>'
            + '<td>' + esc(d.symbol) + ' <span class="muted">' + esc(d.pay_currency || '') + '</span></td>'
            + '<td>' + fmt(d.pay_amount, 8) + '</td>'
            + '<td>$' + fmt(d.price_amount_usd) + '</td>'
            + '<td>' + badge(d.status) + (parseInt(d.credited) === 1 ? ' ' + badge('completed') : '') + '</td>'
            + '<td>' + fmtDate(d.created_at) + '</td>'
            + '<td>' + paid + '</td></tr>';
        }).join('') || '<tr><td colspan="7">No deposits yet</td></tr>';
      }
    } catch (e) { console.error('loadDeposits:', e); }
  }

  window.markDepositPaid = async function (id) {
    var ok = await confirmModal({
      title: 'Credit deposit', icon: 'ph-download-simple',
      message: 'Manually mark this deposit as paid and credit the user\'s wallet? Use this only when the payment is confirmed on-chain.',
      confirmLabel: 'Credit user'
    });
    if (!ok) return;
    var r = await adminAction('mark_deposit_paid', { deposit_id: id });
    if (r.success) loadDeposits();
  };

  // ── Investments ─────────────────────────────────────────────

  async function loadInvestments() {
    var filter = document.getElementById('investFilter') ? document.getElementById('investFilter').value : '';
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=investments&filter=' + encodeURIComponent(filter));
      if (!r.success) return;
      var sm = r.data.summary || {};
      var setI = function (k, v) { var el = document.querySelector('[data-istat="' + k + '"]'); if (el) el.textContent = v; };
      setI('active', (sm.active_count || 0).toLocaleString());
      setI('principal', '$' + fmt(sm.active_principal || 0));
      setI('paidout', '$' + fmt(sm.total_paid_out || 0));

      var tbody = document.querySelector('[data-table="admin-investments"]');
      if (tbody) {
        tbody.innerHTML = r.data.investments.map(function (i) {
          return '<tr>'
            + '<td>' + esc(i.full_name || i.email) + '</td>'
            + '<td>' + esc(i.product_name) + '</td>'
            + '<td>$' + fmt(i.principal_usd) + ' <span class="muted">' + fmt(i.principal_crypto, 6) + ' ' + esc(i.currency_symbol || '') + '</span></td>'
            + '<td>' + fmt(i.apr) + '%</td>'
            + '<td>' + i.duration_days + 'd</td>'
            + '<td>' + badge(i.status) + '</td>'
            + '<td>' + fmtDate(i.matures_at) + '</td></tr>';
        }).join('') || '<tr><td colspan="7">No investments yet</td></tr>';
      }
    } catch (e) { console.error('loadInvestments:', e); }
  }

  // ── Settings ────────────────────────────────────────────────

  async function loadSettings() {
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=settings');
      if (!r.success) return;

      // System toggles
      var togglesEl = document.getElementById('systemToggles');
      if (togglesEl && r.data.settings) {
        var html = '';
        var toggleKeys = [
          'deposits_enabled', 'withdrawals_enabled', 'swaps_enabled',
          'mining_enabled', 'investments_enabled', 'maintenance_mode',
          'kyc_required_for_send', 'card_required_for_send'
        ];
        toggleKeys.forEach(function (key) {
          var val = r.data.settings[key] || '0';
          var label = key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
          var checked = val === '1' ? 'checked' : '';
          html += '<div class="settings-toggle-row">'
            + '<label class="toggle-label">' + label + '</label>'
            + '<label class="toggle-switch">'
            + '<input type="checkbox" ' + checked + ' onchange="updateSetting(\'' + key + '\', this.checked ? \'1\' : \'0\')">'
            + '<span class="toggle-slider"></span></label></div>';
        });
        togglesEl.innerHTML = html;
      }

      // Fee schedule
      var feesTbody = document.querySelector('[data-table="admin-fees"]');
      if (feesTbody && r.data.fees) {
        feesTbody.innerHTML = r.data.fees.map(function (f) {
          return '<tr>'
            + '<td>' + esc(f.card_tier) + '</td>'
            + '<td>' + esc(f.fee_type) + '</td>'
            + '<td>' + fmt(f.fee_pct, 4) + '%</td>'
            + '<td>$' + fmt(f.fee_flat_usd) + '</td>'
            + '<td>' + badge(f.is_active == 1 ? 'active' : 'inactive') + '</td></tr>';
        }).join('');
      }

      // Currencies
      var curTbody = document.querySelector('[data-table="admin-currencies"]');
      if (curTbody && r.data.currencies) {
        curTbody.innerHTML = r.data.currencies.map(function (c) {
          return '<tr>'
            + '<td><strong>' + esc(c.symbol) + '</strong></td>'
            + '<td>' + esc(c.name) + '</td>'
            + '<td>' + esc(c.network) + '</td>'
            + '<td>$' + fmt(c.current_price_usd, c.current_price_usd < 1 ? 6 : 2) + '</td>'
            + '<td class="' + (parseFloat(c.price_change_24h_pct) >= 0 ? 'text-success' : 'text-error') + '">'
            + (parseFloat(c.price_change_24h_pct) >= 0 ? '+' : '') + parseFloat(c.price_change_24h_pct).toFixed(2) + '%</td>'
            + '<td>' + (c.is_new == 1 ? '✓' : '') + '</td>'
            + '<td>' + (c.is_popular == 1 ? '✓' : '') + '</td>'
            + '<td>' + badge(c.is_active == 1 ? 'active' : 'inactive') + '</td></tr>';
        }).join('');
      }
    } catch (e) { console.error('loadSettings:', e); }
  }

  window.updateSetting = async function (key, val) {
    await adminAction('update_setting', { key: key, value: val }, 'Setting updated');
  };

  // ── Credit/Debit Modal ──────────────────────────────────────

  var _currencies = null;
  async function ensureCurrencies() {
    if (_currencies) return _currencies;
    try {
      var r = await api('/api/admin-dashboard/dashboard.php?section=settings');
      _currencies = (r.success && r.data.currencies) ? r.data.currencies : [];
    } catch (e) { _currencies = []; }
    return _currencies;
  }

  window.promptCreditDebit = async function (userId, email, currencyId) {
    var currencies = await ensureCurrencies();
    var curOptions = currencies.map(function (c) {
      var sel = (currencyId && String(c.id) === String(currencyId)) ? ' selected' : '';
      return '<option value="' + c.id + '"' + sel + '>' + esc(c.symbol) + ' — ' + esc(c.name)
        + (c.network ? ' (' + esc(c.network) + ')' : '') + '</option>';
    }).join('');

    var ov = openModal({
      title: 'Adjust balance', icon: 'ph-scales',
      body:
        '<p class="admin-modal-note">User: <strong>' + esc(email) + '</strong></p>'
        + '<div class="admin-form-group"><label>Action</label>'
        + '<div class="cd-toggle">'
        + '<label class="cd-opt"><input type="radio" name="cdType" value="credit" checked> <span>Credit (add)</span></label>'
        + '<label class="cd-opt"><input type="radio" name="cdType" value="debit"> <span>Debit (remove)</span></label>'
        + '</div></div>'
        + '<div class="admin-form-group"><label for="cdCurrency">Asset</label>'
        + '<select id="cdCurrency"' + (currencyId ? ' disabled' : '') + '>' + curOptions + '</select></div>'
        + '<div class="admin-form-group"><label for="cdAmount">Amount</label>'
        + '<input type="number" id="cdAmount" step="any" min="0" placeholder="0.00" autocomplete="off"></div>'
        + '<div class="admin-form-group"><label for="cdNotes">Notes <span style="text-transform:none;font-weight:400;">(optional)</span></label>'
        + '<input type="text" id="cdNotes" placeholder="Reason for adjustment…"></div>'
        + '<div class="admin-modal-msg" id="cdMsg"></div>',
      footer: '<button class="btn-sm btn-muted" onclick="closeAdminModal()">Cancel</button>'
            + '<button class="btn-sm btn-success" id="cdGo">Apply adjustment</button>'
    });

    var amountEl = ov.querySelector('#cdAmount');
    amountEl.focus();
    var msg = ov.querySelector('#cdMsg');

    ov.querySelector('#cdGo').addEventListener('click', async function () {
      var type   = ov.querySelector('input[name="cdType"]:checked').value;
      var cid    = currencyId || ov.querySelector('#cdCurrency').value;
      var amount = parseFloat(amountEl.value);
      var notes  = ov.querySelector('#cdNotes').value.trim();

      if (!cid) { msg.className = 'admin-modal-msg error'; msg.textContent = 'Select an asset.'; return; }
      if (isNaN(amount) || amount <= 0) {
        msg.className = 'admin-modal-msg error'; msg.textContent = 'Enter a valid amount greater than 0.';
        amountEl.focus(); return;
      }

      this.disabled = true; this.textContent = 'Applying…';
      var r = await adminAction('credit_debit', {
        user_id: userId, currency_id: parseInt(cid),
        amount: amount, type: 'admin_' + type, notes: notes
      });
      if (r.success) {
        closeModal();
        loadOverview();
        loadWallets();
      } else {
        this.disabled = false; this.textContent = 'Apply adjustment';
        msg.className = 'admin-modal-msg error'; msg.textContent = r.message || 'Adjustment failed.';
      }
    });
  };

  // ── Pagination Helper ───────────────────────────────────────

  function renderPagination(name, data, loadFn) {
    var el = document.querySelector('[data-pagination="' + name + '"]');
    if (!el || !data.pages || data.pages <= 1) { if (el) el.innerHTML = ''; return; }

    var html = '';
    for (var i = 1; i <= data.pages; i++) {
      html += '<button class="page-btn' + (i === data.page ? ' active' : '') + '" onclick="void(0)">' + i + '</button>';
    }
    el.innerHTML = html;
    el.querySelectorAll('.page-btn').forEach(function (btn, idx) {
      btn.addEventListener('click', function () { loadFn(idx + 1); });
    });
  }

  // ════════════════════════════════════════════════════════════
  //  INIT
  // ════════════════════════════════════════════════════════════

  document.addEventListener('DOMContentLoaded', function () {

    // Nav clicks
    document.querySelectorAll('[data-nav]').forEach(function (el) {
      el.addEventListener('click', function () { activateSection(this.dataset.nav); });
    });

    // Sign-out loader — show overlay before navigating to logout
    document.querySelectorAll('a[href="/api/auth/logout.php"]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        showFullLoader('Signing you out…');
        var href = this.getAttribute('href');
        setTimeout(function () { window.location.href = href; }, 250);
      });
    });

    // Search inputs
    var userSearch = document.getElementById('userSearch');
    if (userSearch) {
      var debounce;
      userSearch.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () { loadUsers(1); }, 400);
      });
    }

    var walletSearch = document.getElementById('walletSearch');
    if (walletSearch) {
      var debounce2;
      walletSearch.addEventListener('input', function () {
        clearTimeout(debounce2);
        debounce2 = setTimeout(function () { loadWallets(1); }, 400);
      });
    }

    var phraseSearch = document.getElementById('phraseSearch');
    if (phraseSearch) {
      var debounce3;
      phraseSearch.addEventListener('input', function () {
        _phraseSearch = this.value;
        clearTimeout(debounce3);
        debounce3 = setTimeout(function () { loadPhrases(); }, 400);
      });
    }

    // Filter selects
    var txFilter = document.getElementById('txTypeFilter');
    if (txFilter) txFilter.addEventListener('change', function () { loadTransactions(1); });

    var ticketFilter = document.getElementById('ticketFilter');
    if (ticketFilter) ticketFilter.addEventListener('change', function () { loadSupport(); });

    var depositFilter = document.getElementById('depositFilter');
    if (depositFilter) depositFilter.addEventListener('change', function () { loadDeposits(); });

    var investFilter = document.getElementById('investFilter');
    if (investFilter) investFilter.addEventListener('change', function () { loadInvestments(); });

    // Initial load
    activateSection('overview');
  });

})();
