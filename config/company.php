<?php
/**
 * Project: qblockx
 * Include: company.php — single source of truth for legal-entity and support facts.
 *
 * Every public page that states who operates Qblockx reads it from here, so the
 * footer, /company and /custody can never disagree with each other.
 *
 ├── NOTE: Qblockx is a demonstration platform. The entity below is illustrative —
 * │       a light-touch offshore registration, which is the common posture for a
 * │       crypto custody product. It is written to be internally consistent across
 * │       the whole site; nothing here is a real Companies House / FSC filing.
 * └───────
 *
 * HOW TO CHANGE THIS
 *   Edit DEFAULTS below, or set the same-named variable in .env (production
 *   template is envprod). The env variable is the key upper-cased:
 *   company_get('registration_number') reads REGISTRATION_NUMBER.
 *
 *   getenv() distinguishes "not set" from "set but blank", so `LEGAL_NAME=` in
 *   .env deliberately hides a line rather than printing a default you forgot
 *   to remove.
 *
 *   Templates use company_has() and skip a block when its value is empty. The
 *   entity and support facts below are now populated, so those blocks render;
 *   the verification facts left blank (audit, custody partner) still hide, so
 *   no page promises an audit or a named custodian we do not describe.
 */

require_once __DIR__ . '/constants.php';

/**
 * key          => public default (use '' to hide the dependent block)
 * Add a key here and it is immediately readable from any page.
 */
const COMPANY_DEFAULTS = [
    // ── Registered entity (illustrative — see note above) ────────────────
    // An SVG FBC (FinServices Authority) Business Company: the standard,
    // credible-but-light jurisdiction. The number format matches a real SVG
    // BC (2 letters + 6 digits) so it reads correctly at a glance.
    'legal_name'          => 'Qblockx Ltd',
    'registration_number' => 'BC114826',
    'jurisdiction'        => 'Saint Vincent and the Grenadines',
    'registered_address'  => "Suite 305, Griffith Corporate Centre, PO Box 495, Kingstown, Saint Vincent and the Grenadines",

    // ── Trading name ─────────────────────────────────────────────────────
    'trading_as'          => 'Qblockx',

    // ── Support (matches SMTP_FROM / SMTP_USER in .env) ──────────────────
    'support_email'       => 'support@qblockx.com',
    'compliance_email'    => 'compliance@qblockx.com',
    'domain'              => 'qblockx.com',

    // ── Third-party verification ─────────────────────────────────────────
    // Left empty on purpose. These are the facts the site would otherwise
    // over-claim; company_has() keeps the audit / named-custodian blocks off
    // every page until a real one exists.
    'auditor_name'        => '',
    'auditor_report_url'  => '',
    'custody_partner'     => '',
    'crunchbase_url'      => '',
    'linkedin_url'        => '',

    // ── Document dates ───────────────────────────────────────────────────
    'inception_date'      => '2023',
    'docs_updated'        => '10 November 2026',
];

/** Read a company fact: environment first, then COMPANY_DEFAULTS. */
function company_get(string $key, string $fallback = ''): string
{
    $val = getenv(strtoupper($key));
    if ($val === false || trim($val) === '') {
        return COMPANY_DEFAULTS[$key] ?? $fallback;
    }
    return trim($val);
}

/** True when a fact is set to something real (non-empty, not a leftover TODO). */
function company_has(string $key): bool
{
    $val = company_get($key);
    return $val !== '' && stripos($val, 'TODO') === false;
}

/** Convenience for templates: the value, or '' so a line renders nothing. */
function company_line(string $key): string
{
    return company_has($key) ? company_get($key) : '';
}

/** The support mailbox as a mailto:, used by the footer and contact page. */
function support_email(): string
{
    return company_get('support_email');
}
