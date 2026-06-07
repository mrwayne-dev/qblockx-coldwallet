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

  // ── Section Routing ─────────────────────────────────────────

  var sectionTitles = {
    overview: 'Overview', users: 'Users', kyc: 'KYC Approvals',
    transactions: 'Transactions', cards: 'Virtual Cards', wallets: 'User Wallets',
    support: 'Support Tickets', mining: 'Mining', settings: 'Settings'
  };

  var sectionLoaders = {
    overview: loadOverview, users: loadUsers, kyc: loadKyc,
    transactions: loadTransactions, cards: loadCards, wallets: loadWallets,
    support: loadSupport, mining: loadMining, settings: loadSettings
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
        'swap-count-30d': s.swap_count_30d, 'open-tickets': s.open_tickets
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
      var r = await adminAction('approve_kyc', { kyc_id: id });
      if (r.success) loadKyc();
    } else {
      var reason = prompt('Rejection reason:');
      if (reason !== null) {
        var r2 = await adminAction('reject_kyc', { kyc_id: id, reason: reason });
        if (r2.success) loadKyc();
      }
    }
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

  window.activateCard = async function (id) {
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

  window.promptReplyTicket = async function (id, ref) {
    var body = prompt('Reply to ' + ref + ':');
    if (body) {
      var r = await adminAction('reply_ticket', { ticket_id: id, body: body });
      if (r.success) loadSupport();
    }
  };

  window.closeTicket = async function (id) {
    if (confirm('Close this ticket?')) {
      var r = await adminAction('close_ticket', { ticket_id: id });
      if (r.success) loadSupport();
    }
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

  // ── Credit/Debit Prompt ─────────────────────────────────────

  window.promptCreditDebit = function (userId, email, currencyId) {
    var type = prompt('Action for ' + email + ':\nType "credit" or "debit"');
    if (!type || (type !== 'credit' && type !== 'debit')) return;

    var cid = currencyId || prompt('Currency ID (e.g. 4 for BTC, 5 for ETH):');
    if (!cid) return;

    var amount = prompt('Amount:');
    if (!amount || isNaN(amount) || parseFloat(amount) <= 0) return;

    var notes = prompt('Notes (optional):') || '';

    adminAction('credit_debit', {
      user_id: userId, currency_id: parseInt(cid),
      amount: parseFloat(amount), type: 'admin_' + type, notes: notes
    }).then(function (r) {
      if (r.success) {
        loadOverview();
        loadWallets();
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

    // Filter selects
    var txFilter = document.getElementById('txTypeFilter');
    if (txFilter) txFilter.addEventListener('change', function () { loadTransactions(1); });

    var ticketFilter = document.getElementById('ticketFilter');
    if (ticketFilter) ticketFilter.addEventListener('change', function () { loadSupport(); });

    // Initial load
    activateSection('overview');
  });

})();
