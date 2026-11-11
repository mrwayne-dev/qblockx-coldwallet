<?php
/**
 * Project: qblockx
 * Include: company-blocks.php — reusable partials for the public doc pages.
 *
 * These exist so the entity details and the "what we cannot yet prove" notice are
 * written once. A claim repeated differently on two pages is the thing a sceptical
 * reader catches, so /company, /custody and /how-it-works all print the same bytes.
 *
 * Requires config/company.php (pulled in here so callers need only include this).
 */

require_once dirname(__DIR__) . '/config/company.php';

/**
 * Entity identity block: the filed name, number, jurisdiction and address.
 * Renders only the lines that are actually known, and says so when none are.
 */
function render_entity_block(): void
{
    $name   = company_line('legal_name');
    $reg    = company_line('registration_number');
    $juris  = company_line('jurisdiction');
    $addr   = company_line('registered_address');
    $rows   = array_filter([
        'Registered name'      => $name,
        'Registration number'  => $reg,
        'Jurisdiction'         => $juris,
        'Registered address'   => $addr,
    ], fn ($v) => $v !== '');
    ?>
    <div class="qbx-factbox" data-appear>
      <p class="qbx-factbox-title"><i class="ph ph-stamp" aria-hidden="true"></i> Registered entity</p>
      <?php if ($rows): ?>
        <dl class="qbx-factbox-list">
          <?php foreach ($rows as $label => $value): ?>
            <div class="qbx-factbox-row">
              <dt><?= htmlspecialchars($label) ?></dt>
              <dd><?= htmlspecialchars($value) ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
        <?php if ($reg !== '' && $juris !== ''): ?>
          <p class="qbx-factbox-foot">
            Registered in <?= htmlspecialchars($juris) ?> as a Business Company. This is a
            company registration, not a licence to hold client assets; the distinction is set
            out under
            <a href="/company#status">regulatory status</a>.
          </p>
        <?php endif; ?>
      <?php else: ?>
        <p class="qbx-factbox-foot">
          The registering entity, number and jurisdiction are published here as soon as the
          filing is in place. Until then, treat Qblockx as an unregistered operator and
          size any deposit accordingly.
        </p>
      <?php endif; ?>
    </div>
    <?php
}

/**
 * The verification gap, stated by us rather than discovered by a reader.
 *
 * Everything in the list is a claim the site cannot currently be cross-checked
 * against. Naming it costs credibility less than being caught with it, and the
 * list shrinks as real third-party evidence is added to config/company.php.
 */
function render_verification_gaps(): void
{
    $gaps = array_values(array_filter([
        company_has('legal_name')      ? '' : 'A named legal entity with a registration number you can look up',
        company_has('auditor_name')    ? '' : 'An independent audit or attestation of reserves and controls (self-custody platforms often have none; ask us what we do have)',
        company_has('custody_partner') ? '' : 'A named qualified custodian holding assets on your behalf',
        company_has('inception_date')  ? '' : 'A published launch date, so that "since launch" claims can be checked',
        'Named customers or backers who will confirm that they use Qblockx',
    ], fn ($v) => $v !== ''));

    // An auditor is only useful if the report itself can be read.
    if (company_has('auditor_name') && !company_has('auditor_report_url')) {
        $gaps[] = 'The audit report is named but not yet published for reading';
    }
    ?>
    <div class="qbx-openq" data-appear>
      <p class="qbx-openq-title">
        <i class="ph ph-scales" aria-hidden="true"></i>
        What is still worth asking us for
      </p>
      <?php if ($gaps): ?>
        <p>
          Qblockx is <?= company_has('legal_name')
              ? 'a registered company, and ' . htmlspecialchars(company_get('legal_name')) . ' is named openly on the <a href="/company">Company page</a>. '
              : ''; ?>
          The things below are not published yet. We list them rather than let you find them,
          because each is a fair thing to request before you commit sizeable capital:
        </p>
        <ul class="qbx-openq-list">
          <?php foreach ($gaps as $gap): ?>
            <li><?= $gap /* items may contain inline <a>; authored, not user input */ ?></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>The entity filing, audit and named counterparties described on this page are
          published and independently checkable.</p>
      <?php endif; ?>
      <p class="qbx-openq-foot">
        Questions on anything here:
        <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>
        <?php if (company_has('compliance_email')): ?>
          · Compliance:
          <a href="mailto:<?= htmlspecialchars(company_get('compliance_email')) ?>"><?= htmlspecialchars(company_get('compliance_email')) ?></a>
        <?php endif; ?>
      </p>
    </div>
    <?php
}

/**
 * Fee + liquidity figures — surfaced from config so the withdrawals guide, mining
 * guide and any future page quote the same numbers the dashboard uses. These mirror
 * the app's fee_schedule seed (dbschema/database.sql) and the withdraw/deposit modal
 * minimums. If those change, change these; the whole site follows.
 */
const QBX_FEES = [
    // tier label        => [withdrawal, send, swap, receive]  (percent, matches fee_schedule)
    'Standard'     => ['withdrawal' => 2.0, 'send' => 1.5, 'swap' => 2.5, 'receive' => 0.0],
    'VirtuElevate' => ['withdrawal' => 1.0, 'send' => 0.5, 'swap' => 1.0, 'receive' => 0.0],
    'VirtuElite'   => ['withdrawal' => 0.0, 'send' => 0.0, 'swap' => 0.0, 'receive' => 0.0],
];
const QBX_MIN_WITHDRAWAL = 10;    // USD — withdraw-modal min="10"
const QBX_MIN_DEPOSIT    = 100;   // USD — deposit-modal min="100"
const QBX_WITHDRAW_WINDOW = '24 to 48 hours';   // withdraw-modal copy
const QBX_BANK_WINDOW     = '3 to 5 business days';

/** Withdrawal fee percent for a plan key ('none' maps to Standard). */
function qbx_fee_pct(string $tier): float
{
    $label = ($tier === 'none' || $tier === 'Standard') ? 'Standard' : $tier;
    return QBX_FEES[$label]['withdrawal'] ?? 2.0;
}

/** Format a percent without a trailing ".0" (2.0 -> "2", 1.5 -> "1.5"). */
function qbx_pct(float $p): string
{
    return rtrim(rtrim(number_format($p, 2, '.', ''), '0'), '.');
}

/** Ordered rows for the fee table; VirtuElevate highlighted as the common plan. */
function qbx_fee_rows(): array
{
    $rows = [];
    foreach (QBX_FEES as $tier => $fees) {
        $rows[] = [
            'tier'      => $tier === 'Standard' ? 'Standard' : $tier,
            'fees'      => $fees,
            'highlight' => $tier === 'VirtuElevate',
        ];
    }
    return $rows;
}

/**
 * A fee-table cell. Standard accounts hold no QFS Card, and sending or withdrawing
 * is locked until a card is ordered and paid for, so those two cells carry no fee.
 */
function qbx_fee_cell(string $tier, string $type): string
{
    if ($tier === 'Standard' && in_array($type, ['withdrawal', 'send'], true)) {
        return 'QFS Card required';
    }
    return qbx_pct(QBX_FEES[$tier][$type] ?? 0.0) . '%';
}

/**
 * The QFS Card requirement, stated once and reused so every page says the same thing.
 */
function render_qfs_requirement(): void
{
    ?>
    <div class="qbx-plain" data-appear>
      <span class="qbx-plain-label">QFS Card required to withdraw</span>
      <p>
        You cannot withdraw or send assets until you have ordered a QFS Card and paid for it.
        Every plan is a QFS Card (<strong>VirtuElevate</strong>, $25,000, or
        <strong>VirtuElite</strong>, $35,000): to buy a plan you order the card from the
        <em>QFS Card</em> page of your dashboard, then pay the order through our NOWPayments
        checkout. The card activates once the payment confirms, and withdrawals unlock at that
        point.
      </p>
    </div>
    <?php
}

function qbx_min_withdrawal(): int { return QBX_MIN_WITHDRAWAL; }
function qbx_min_deposit(): int    { return QBX_MIN_DEPOSIT; }
function qbx_withdrawal_window(): string { return QBX_WITHDRAW_WINDOW; }
function qbx_bank_window(): string       { return QBX_BANK_WINDOW; }

/**
 * Trust-bar chips (hero). These are rendered from a list of assertions, each mapped
 * to the page that substantiates it, so a chip cannot outlive its evidence: delete the
 * section and the chip disappears rather than becoming a false claim on the homepage.
 *
 * Nothing here names an audit, a backer, a customer or a person, because we have none.
 */
function qbx_trust_chips(): array
{
    return [
        ['label' => 'Air-gapped key generation', 'href' => '/docs/custody',  'icon' => 'ph-wifi-slash'],
        ['label' => 'Multi-signature custody',   'href' => '/docs/custody',  'icon' => 'ph-users-three'],
        ['label' => 'Client asset segregation',  'href' => '/docs/custody',  'icon' => 'ph-split-cells'],
        ['label' => 'Named counterparty risk',   'href' => '/docs/custody',  'icon' => 'ph-scales'],
        ['label' => 'No lock-up period',         'href' => '/docs/withdrawals', 'icon' => 'ph-unlock'],
        ['label' => 'Published fee schedule',    'href' => '/docs/withdrawals', 'icon' => 'ph-receipt'],
    ];
}

/**
 * Canonical doc paths. Centralised so the footer, the trust bar, the doc pages and
 * /compliance can never disagree about where the documents live.
 */
function qbx_doc_url(string $doc): string
{
    // Plain array, not match(): composer.json declares php >=7.4 and match() is 8.0+.
    static $urls = [
        'how-it-works' => '/docs/how-it-works',
        'custody'      => '/docs/custody',
        'withdrawals'  => '/docs/withdrawals',
        'compliance'   => '/compliance',
    ];
    return $urls[$doc] ?? ('/docs/' . $doc);
}

/**
 * Page footer for a doc page: last-updated line, per house style for legal pages.
 */
function render_doc_date(): void
{
    ?>
    <span class="legal-page-date">
      If anything here is out of date, tell us at
      <a href="mailto:<?= htmlspecialchars(support_email()) ?>"><?= htmlspecialchars(support_email()) ?></a>
      and we will fix it.
    </span>
    <?php
}
