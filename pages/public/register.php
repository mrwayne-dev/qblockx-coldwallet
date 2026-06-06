<?php
/**
 * Project: qblockx
 * Page: User Registration
 */
$pageTitle = 'Create Account';
require_once '../../includes/head.php';
?>

<div class="auth-page">
  <div class="auth-split">

    <!-- ── Left brand panel ── -->
    <div class="auth-panel">
      <a href="/" class="auth-panel-logo" aria-label="Qblockx home">
        <img src="/assets/images/logo/logowhite.png" alt="">
        <span class="auth-panel-logo-text">Qblockx</span>
      </a>

      <div class="auth-panel-body">
        <h2 class="auth-panel-heading">Your keys,<br>completely offline.</h2>
        <p class="auth-panel-sub">
          Open your account in minutes and generate an air-gapped cold wallet — your private keys stay offline, safe from remote hacks, malware, and phishing.
        </p>
        <div class="auth-panel-stats">
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">12K+</span>
            <span class="auth-panel-stat-label">Holders</span>
          </div>
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">$500M+</span>
            <span class="auth-panel-stat-label">Secured</span>
          </div>
          <div class="auth-panel-stat">
            <span class="auth-panel-stat-value">100%</span>
            <span class="auth-panel-stat-label">Offline Keys</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Right form panel ── -->
    <div class="auth-form-panel">

      <h1 class="auth-heading">Create your account</h1>
      <p class="auth-subtext">Create your free Qblockx account.</p>

      <div id="authMsg" class="auth-msg" role="alert" aria-live="polite" style="display:none;"></div>

      <form id="registerForm" novalidate>

        <div class="form-group">
          <label for="full_name">Full name</label>
          <div class="input-icon-wrap">
            <i class="ph ph-user input-icon" aria-hidden="true"></i>
            <input type="text" id="full_name" name="full_name"
                   placeholder="John Smith" autocomplete="name">
          </div>
        </div>

        <div class="form-group">
          <label for="currency">Currency <span aria-hidden="true">*</span></label>
          <div class="input-icon-wrap">
            <i class="ph ph-currency-dollar input-icon" aria-hidden="true"></i>
            <select id="currency" name="currency" required>
              <!-- Major Global -->
              <optgroup label="Major Global">
                <option value="USD" selected>USD — US Dollar ($)</option>
                <option value="EUR">EUR — Euro (€)</option>
                <option value="GBP">GBP — British Pound (£)</option>
                <option value="JPY">JPY — Japanese Yen (¥)</option>
                <option value="CHF">CHF — Swiss Franc (Fr)</option>
              </optgroup>
              <!-- Americas -->
              <optgroup label="Americas">
                <option value="AUD">AUD — Australian Dollar (A$)</option>
                <option value="CAD">CAD — Canadian Dollar (C$)</option>
                <option value="NZD">NZD — New Zealand Dollar (NZ$)</option>
                <option value="BRL">BRL — Brazilian Real (R$)</option>
                <option value="MXN">MXN — Mexican Peso (MX$)</option>
                <option value="COP">COP — Colombian Peso (Col$)</option>
                <option value="ARS">ARS — Argentine Peso (AR$)</option>
                <option value="CLP">CLP — Chilean Peso (CL$)</option>
                <option value="PEN">PEN — Peruvian Sol (S/)</option>
                <option value="UYU">UYU — Uruguayan Peso ($U)</option>
                <option value="DOP">DOP — Dominican Peso (RD$)</option>
                <option value="TTD">TTD — T&T Dollar (TT$)</option>
                <option value="JMD">JMD — Jamaican Dollar (J$)</option>
              </optgroup>
              <!-- Europe -->
              <optgroup label="Europe">
                <option value="SEK">SEK — Swedish Krona (kr)</option>
                <option value="NOK">NOK — Norwegian Krone (kr)</option>
                <option value="DKK">DKK — Danish Krone (kr)</option>
                <option value="PLN">PLN — Polish Zloty (zł)</option>
                <option value="CZK">CZK — Czech Koruna (Kč)</option>
                <option value="HUF">HUF — Hungarian Forint (Ft)</option>
                <option value="RON">RON — Romanian Leu (lei)</option>
                <option value="BGN">BGN — Bulgarian Lev (лв)</option>
                <option value="HRK">HRK — Croatian Kuna (kn)</option>
                <option value="RSD">RSD — Serbian Dinar (din)</option>
                <option value="UAH">UAH — Ukrainian Hryvnia (₴)</option>
                <option value="RUB">RUB — Russian Ruble (₽)</option>
                <option value="TRY">TRY — Turkish Lira (₺)</option>
                <option value="ISK">ISK — Icelandic Króna (kr)</option>
              </optgroup>
              <!-- Asia & Pacific -->
              <optgroup label="Asia &amp; Pacific">
                <option value="CNY">CNY — Chinese Yuan (¥)</option>
                <option value="INR">INR — Indian Rupee (₹)</option>
                <option value="SGD">SGD — Singapore Dollar (S$)</option>
                <option value="HKD">HKD — Hong Kong Dollar (HK$)</option>
                <option value="KRW">KRW — South Korean Won (₩)</option>
                <option value="TWD">TWD — Taiwan Dollar (NT$)</option>
                <option value="IDR">IDR — Indonesian Rupiah (Rp)</option>
                <option value="PHP">PHP — Philippine Peso (₱)</option>
                <option value="THB">THB — Thai Baht (฿)</option>
                <option value="MYR">MYR — Malaysian Ringgit (RM)</option>
                <option value="VND">VND — Vietnamese Dong (₫)</option>
                <option value="BDT">BDT — Bangladeshi Taka (৳)</option>
                <option value="PKR">PKR — Pakistani Rupee (₨)</option>
                <option value="LKR">LKR — Sri Lankan Rupee (₨)</option>
                <option value="NPR">NPR — Nepalese Rupee (₨)</option>
                <option value="MMK">MMK — Myanmar Kyat (K)</option>
                <option value="KHR">KHR — Cambodian Riel (៛)</option>
              </optgroup>
              <!-- Middle East -->
              <optgroup label="Middle East">
                <option value="AED">AED — UAE Dirham (د.إ)</option>
                <option value="SAR">SAR — Saudi Riyal (﷼)</option>
                <option value="QAR">QAR — Qatari Riyal (﷼)</option>
                <option value="KWD">KWD — Kuwaiti Dinar (KD)</option>
                <option value="BHD">BHD — Bahraini Dinar (BD)</option>
                <option value="OMR">OMR — Omani Rial (﷼)</option>
                <option value="JOD">JOD — Jordanian Dinar (JD)</option>
                <option value="ILS">ILS — Israeli Shekel (₪)</option>
                <option value="IQD">IQD — Iraqi Dinar (ع.د)</option>
                <option value="IRR">IRR — Iranian Rial (﷼)</option>
              </optgroup>
              <!-- Africa -->
              <optgroup label="Africa">
                <option value="NGN">NGN — Nigerian Naira (₦)</option>
                <option value="ZAR">ZAR — South African Rand (R)</option>
                <option value="KES">KES — Kenyan Shilling (KSh)</option>
                <option value="GHS">GHS — Ghanaian Cedi (₵)</option>
                <option value="EGP">EGP — Egyptian Pound (£E)</option>
                <option value="MAD">MAD — Moroccan Dirham (MAD)</option>
                <option value="TZS">TZS — Tanzanian Shilling (TSh)</option>
                <option value="UGX">UGX — Ugandan Shilling (USh)</option>
                <option value="ETB">ETB — Ethiopian Birr (Br)</option>
                <option value="DZD">DZD — Algerian Dinar (دج)</option>
                <option value="TND">TND — Tunisian Dinar (DT)</option>
                <option value="XOF">XOF — W. African CFA Franc (CFA)</option>
                <option value="XAF">XAF — C. African CFA Franc (FCFA)</option>
                <option value="RWF">RWF — Rwandan Franc (RF)</option>
                <option value="ZMW">ZMW — Zambian Kwacha (ZK)</option>
                <option value="MZN">MZN — Mozambican Metical (MT)</option>
                <option value="BWP">BWP — Botswana Pula (P)</option>
              </optgroup>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email address <span aria-hidden="true">*</span></label>
          <div class="input-icon-wrap">
            <i class="ph ph-envelope input-icon" aria-hidden="true"></i>
            <input type="email" id="email" name="email" required
                   placeholder="you@example.com" autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password <span aria-hidden="true">*</span></label>
          <div class="input-icon-wrap">
            <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password" required
                   minlength="8" placeholder="Min. 8 characters" autocomplete="new-password">
            <button type="button" class="input-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <i class="ph ph-eye" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm">Confirm password <span aria-hidden="true">*</span></label>
          <div class="input-icon-wrap">
            <i class="ph ph-lock-simple input-icon" aria-hidden="true"></i>
            <input type="password" id="confirm" name="confirm" required
                   placeholder="Repeat your password" autocomplete="new-password">
          </div>
        </div>

        <button type="submit" class="btn-primary full-width auth-submit" id="registerBtn">
          Create Account
        </button>

      </form>

      <p class="auth-footer-text">
        Already have an account?
        <a href="/login" class="auth-link">Sign in</a>
      </p>

      <p class="auth-disclaimer">
        By creating an account you agree to our
        <a href="/terms" class="auth-link">Terms of Service</a> and
        <a href="/privacy" class="auth-link">Privacy Policy</a>.
      </p>

    </div>
  </div>
</div>


</body>
</html>
