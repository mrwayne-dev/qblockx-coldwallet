<?php
/**
 * Project: qblockx
 * Modal: Credit / Debit User Wallet
 *
 * Admin enters user email, amount, type (credit|debit), notes
 * → POST /api/admin-dashboard/dashboard.php (action=credit_debit)
 * Creates a transaction record + adjusts wallet balance
 */
?>

<div id="modal-credit-debit" class="admin-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="creditDebitModalTitle">
  <div class="admin-modal">

    <div class="admin-modal-header">
      <h2 class="admin-modal-title" id="creditDebitModalTitle">
        <i class="ph ph-arrows-left-right" aria-hidden="true"></i>
        Credit / Debit User
      </h2>
      <button class="admin-modal-close" data-close-modal="modal-credit-debit" aria-label="Close">
        <i class="ph ph-x"></i>
      </button>
    </div>

    <div class="admin-modal-body">

      <p class="admin-modal-note">
        Directly credit or debit a user wallet. A transaction record is created for the adjustment.
      </p>

      <div class="admin-form-group">
        <label for="cdUserEmail">User Email</label>
        <input id="cdUserEmail" class="admin-input" type="email"
               placeholder="user@example.com" autocomplete="off">
      </div>

      <div class="admin-form-group">
        <label for="cdAmount">Amount (USD)</label>
        <input id="cdAmount" class="admin-input" type="number"
               min="0.01" step="0.01" placeholder="0.00">
      </div>

      <div class="admin-form-group">
        <label for="cdType">Type</label>
        <select id="cdType" class="admin-input">
          <option value="credit">Credit — add funds to wallet</option>
          <option value="debit">Debit — remove funds from wallet</option>
        </select>
      </div>

      <div class="admin-form-group">
        <label for="cdNotes">Notes <span style="color:var(--color-text-muted);font-weight:400;">(required)</span></label>
        <input id="cdNotes" class="admin-input" type="text"
               placeholder="Reason for adjustment" maxlength="255" autocomplete="off">
      </div>

      <div id="cdMsg" class="admin-modal-msg" style="display:none;"></div>

    </div><!-- .admin-modal-body -->

    <div class="admin-modal-footer">
      <button class="btn-sm" data-close-modal="modal-credit-debit" type="button">Cancel</button>
      <button class="btn-sm btn-accent" onclick="submitCreditDebit()" type="button" id="cdSubmitBtn">
        <i class="ph ph-check"></i> Confirm
      </button>
    </div>

  </div><!-- .admin-modal -->
</div><!-- #modal-credit-debit -->
