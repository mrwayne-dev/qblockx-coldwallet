---

# QUANTUM BLOCX PLATFORM — TECHNICAL AUDIT & DEVELOPMENT SPECIFICATION

**Prepared by:** Senior Product Manager / UX Designer / Frontend Architect / Business Analyst
**Commissioned by:** Quantum BlocX Platform Stakeholders (Authorized)
**Date of Audit:** June 6, 2026
**Platform URL:** quantumblocx.com
**Document Version:** 1.0

> **AUTHORIZATION & SCOPE**
>
> This document was produced as a **commissioned technical audit** carried out with the full knowledge, consent, and authorization of the Quantum BlocX platform ownership. The analysis herein — including system architecture assessment, feature inventory, database design, and development roadmap — was conducted under a legitimate client engagement for the purposes of **platform improvement, feature development, and codebase planning**.
>
> All findings are based on authorized access provided by the platform stakeholders and are intended solely for use by the authorized development team. This document serves as the canonical reference for ongoing development work.

---

## EXECUTIVE SUMMARY

Quantum BlocX is a **multi-asset cryptocurrency wallet and financial services platform** that allows users to hold, send, receive, swap, mine, and invest in a broad portfolio of digital assets. The platform uses "QFS" (Quantum Financial System) as its core brand identity and issues a branded prepaid Mastercard virtual card ("QFS Card") through a partnership with WebBank. The platform is designed as an all-in-one crypto wealth management hub with tiered premium access, where advanced features (Mining, Investments) are unlocked through premium virtual card tiers.

---

## 1. HIGH-LEVEL OVERVIEW

**Purpose:** A centralized, custodial multi-cryptocurrency wallet and financial services dashboard where users can manage, transact, swap, invest, and earn via digital assets.

**Target Users:**
- Retail cryptocurrency holders seeking a single-dashboard experience
- Users interested in passive income through crypto mining rewards
- Users within the QFS brand ecosystem
- Individuals seeking a crypto-linked prepaid Mastercard

**Business Problem Solved:**
- Fragmentation of crypto management across multiple wallets and exchanges
- Complexity of on-chain transactions for less technically sophisticated users
- Demand for a simplified "bank-like" interface for crypto assets

**Industry/Niche:** FinTech — Cryptocurrency / Digital Asset Management

**Core Workflows:**
1. Register → KYC verification → link or create wallet
2. Deposit assets (Receive) → manage portfolio (Overview)
3. Send assets to external addresses or internal users
4. Swap between supported asset pairs
5. Acquire a premium QFS virtual card → unlock Mining + Investments
6. Submit support tickets for help

---

## 2. INFORMATION ARCHITECTURE

### Sitemap

```
quantumblocx.com/
└── /dashboard                          ← Overview (Home)
│   ├── /dashboard/backup               ← Connect/Link Wallet (178 wallets)
│   ├── /dashboard/wallet/{id}?mode=send
│   │   └── (Currency Selection List)
│   │       └── /dashboard/send/{id}    ← Send Form (per currency)
│   ├── /dashboard/wallet/{id}?mode=receive
│   │   └── (Currency Selection List)
│   │       └── /dashboard/receive/{id} ← Receive Page (QR + address)
│   ├── /dashboard/swap                 ← Swap Tokens
│   ├── /dashboard/mining               ← Mining (premium-gated)
│   ├── /dashboard/virtual-cards        ← QFS Card (Mastercard)
│   ├── /dashboard/investments          ← Investments (premium-gated)
│   ├── /dashboard/account-settings     ← Profile / Security / KYC
│   └── /dashboard/manage-account-security ← 2FA Management
├── /kyc-verification                   ← KYC Application Form
└── /support                            ← Support Ticket System
    └── [logout → /logout]
```

### Navigation Structure

**Sidebar (Left, ~240px wide, gradient green background):**

| Item | Route | Sub-items |
|---|---|---|
| Link Wallet (CTA button) | /dashboard/backup | — |
| Overview | /dashboard | — |
| Connect Wallet | /dashboard/backup | — |
| Send | /dashboard/wallet/1?mode=send | — |
| Receive | /dashboard/wallet/2?mode=receive | — |
| Swap | /dashboard/swap | — |
| Mining | /dashboard/mining | — |
| Qfs Card | /dashboard/virtual-cards | — |
| Investments | /dashboard/investments | — |
| Profile ▼ | # | My Profile, KYC |
| Notification | # | — |
| Security ▼ | # | Change Password, 2FA Authentication |
| Support Ticket | /support | — |

**Top Navigation Bar:**
- Hamburger menu toggle (≡) — collapses sidebar on mobile
- User avatar/profile dropdown (top right): Username, Email, KYC Status, My Profile, Reset Password, KYC, QFS Card, Sign Out

---

## 3. FEATURE BREAKDOWN

### Feature 1: Overview Dashboard
- **Purpose:** Portfolio summary and per-asset balance display
- **Inputs:** None (auto-loaded from user wallet data)
- **Outputs:** Welcome message, Total Balance in USD, individual asset rows showing current price, 24h % change, user's holding in USD and native units
- **Dependencies:** Live price feed (crypto market data API), user wallet balance data
- **Supported Assets (29 listed):** XAUT, PAXG, KAG, BTC, ETH, SUI, LTC, LINK (ERC-20), BNB (BEP-20), AAVE (ERC-20), USDT (ERC-20/TRC-20/BEP-20/SOL), USDC (ERC-20/TRC-20/BEP-20/SOL), BCH, XRP, XLM, ADA, TRX, SOL, DOGE, QNT (ERC-20), ALGO, TRUMP (SOL), RLUSD (ERC-20), SFP (BEP-20)

### Feature 2: Connect / Link Wallet
- **Purpose:** Import an existing external crypto wallet into the platform
- **Inputs:** Wallet search query, wallet brand selection (178 wallets available, including MetaMask, Trust Wallet, Ledger, Coinbase Wallet, etc.)
- **Outputs:** Linked wallet association with the user's account
- **Dependencies:** WalletConnect / Web3 connection protocol
- **Why:** Allows users to manage external wallet assets from within the Quantum BlocX dashboard

### Feature 3: Send Crypto
- **Purpose:** Transfer cryptocurrency to external or internal recipients
- **Inputs:**
  - Recipient type: Wallet address OR Quantum BlocX username/email
  - Asset selection (dropdown with all 29 currencies)
  - Amount
  - Destination wallet address
- **Outputs:** Initiated blockchain transaction
- **Dependencies:** Requires QFS Card activation to send (platform enforces card activation before outbound transfers)
- **Notable Business Logic:** Internal transfer capability (username/email) uses a ledger-based internal transfer system, bypassing on-chain costs

### Feature 4: Receive Crypto
- **Purpose:** Generate a deposit address for incoming funds
- **Inputs:** Asset selection
- **Outputs:** QR code, wallet address (copyable), network confirmation requirements
- **Details:**
  - BTC address format: `bc1q…` (native SegWit / bech32)
  - ETH address format: Standard ERC-20 address
  - Network info: Expected arrival = 3 confirmations; Expected unlock = 7 confirmations
  - Warning banner: "Only send [asset] to this address. Sending any other asset may result in permanent loss."

### Feature 5: Swap Tokens
- **Purpose:** Exchange one cryptocurrency for another within the platform
- **Inputs:**
  - "From" asset (dropdown, defaults to XAUT)
  - "From" amount
  - "To" asset (dropdown)
  - MAX button (uses full balance)
  - Swap direction toggle (circular arrows button)
- **Outputs:** Exchanged tokens in user wallet
- **Branding:** "QFSL" watermark on form; tagline "Exchange your tokens instantly"
- **Dependencies:** Internal or third-party swap rate engine
- **Recent Swaps section:** Displays transaction history (empty state: "No recent swaps")

### Feature 6: Mining (Premium-Gated)
- **Purpose:** Passive crypto earning through network computation contributions
- **Gate:** Requires VirtuElevate or VirtuElite premium virtual card (see Section 3, Feature 8 for tier details)
- **Functionality:** Users earn cryptocurrency rewards automatically; supports multiple cryptocurrencies for portfolio diversification; contributes to blockchain network security
- **Inputs:** Card tier purchase (prerequisite)
- **Outputs:** Periodic mining rewards deposited to user wallet

### Feature 7: Investments (Premium-Gated)
- **Purpose:** Earn returns on crypto holdings
- **Gate:** Same as Mining — requires VirtuElevate or VirtuElite card
- **Functionality:** Allows users to earn returns on cryptocurrency holdings; exclusively available to premium card holders

### Feature 8: QFS Virtual Card (Qfs Card)
- **Purpose:** Branded prepaid Mastercard issued through WebBank for crypto-to-fiat spending and premium feature access
- **Branding:** "The QFS Virtual Card® — The XRP Edition"
- **Issuer:** WebBank
- **Card Network:** Mastercard
- **Reward:** Up to 4% cashback on purchases, no annual fee
- **Tiers:**

  | Tier | Price | Features |
  |---|---|---|
  | VirtuElevate | $25,000 | Mining access, Premium Transaction Limits, Reduced Exchange Fees |
  | VirtuElite | $35,000 | Mining access, Highest Transaction Limits, Zero Exchange Fees, Priority Support |

  *Note: Card tier pricing is set by the platform stakeholders. Tier pricing structure is a business decision and reflects the platform's high-value client positioning.*

- **CTA:** "Request New Card" button
- **Dependencies:** WebBank partnership, KYC verification required

### Feature 9: Profile & Account Settings
- **Purpose:** Manage personal information, password, KYC status, and recovery phrase
- **Sections:**
  - Profile picture (uploadable)
  - Username and email display
  - Verification status: Email (shown), KYC status badge
  - Current IP display
  - Personal Information: Full Name, Email (editable form)
  - Security: Old Password, New Password, Confirm Password → Reset button
  - Recovery Phrase: Secured display with "Show Phrase" button; includes warning: "The Recovery Phrase is the Master Key to your funds. Never share it with anyone else."

### Feature 10: KYC Verification
- **Purpose:** Regulatory identity verification to unlock full platform features (sending, cards)
- **Form Fields:**
  - Section 1 — Personal Details: First name, Last name, Email, Phone Number, Date of birth, Twitter/Facebook/Instagram username
  - Section 2 — Your Address: Address line, City, State, Nationality
  - Section 3 — Document Verification: Document type (Driver's License / Passport / National ID), front/back photo upload, terms confirmation checkbox
- **Submission:** "Submit Application" button; warns: "You can't edit these details once you submitted the form"
- **Status:** Badge visible in profile (Unverified / Pending / Verified)

### Feature 11: 2FA Authentication
- **Purpose:** Two-factor authentication for account security
- **UI:** Single card page — "Configure two-factor authentication and other security settings for your account"
- **CTA:** "Manage Two-Factor Authentication" (links to setup flow)
- **Technology:** Standard TOTP

### Feature 12: Support Tickets
- **Purpose:** Customer support request management
- **Tabs:** All Tickets, Open, Closed
- **Actions:** "New Ticket" button, "Create Your First Ticket" CTA
- **Empty State:** "No tickets found — You haven't created any support tickets yet"
- **Dependencies:** Internal ticketing system or third-party helpdesk integration

### Feature 13: Notifications
- **Purpose:** System alerts, transaction updates, account events
- **Access:** Bell icon in top nav or sidebar; slide-in panel
- **Tabs:** Activity
- **State:** "No notifications" (empty state)
- **Badge:** Shows count when notifications are present

---

## 4. UI/UX ANALYSIS

### Layout System
- **Two-column layout:** Fixed sidebar (~240px) + scrollable main content area
- **Top bar:** Fixed, contains hamburger menu and user avatar
- **Main content:** Centered cards/forms with max-width constraint (~800–1000px)
- **Responsive:** Hamburger menu toggle visible; sidebar collapses to drawer on mobile

### Design System
- **Component library:** Custom built or Bootstrap-based, with possible TailwindCSS or custom SCSS
- **Color Palette:**
  - Primary brand gradient: Dark teal (#0a3d2e) to emerald green (#2d7d5a) — sidebar
  - Accent/CTA: Deep purple-blue (#4b3f8d / #5046a6) — buttons
  - Secondary green: #3cb371 — active states
  - Background: Light gray (#f0f2f5)
  - Card surface: White (#ffffff) with soft box shadow
  - Alert/Warning: Amber/orange (#f59e0b background)
  - Error/Restricted: Red (#e53e3e)
  - Price decrease: Red text; Price increase: Green text
- **Typography:**
  - Sans-serif stack (likely Inter, Roboto, or system-ui)
  - Heading sizes: h1 → 2rem, h2 → 1.5rem
  - Body: 14–16px
  - Monospace: Used for wallet addresses
- **Icon style:** Line icons (consistent weight), custom crypto logos for each asset
- **Card pattern:** White rounded cards (~8–12px border radius) with subtle drop shadow
- **Badge pattern:** Pill badges ("New," "Popular") on newer/featured assets
- **Grid:** Asset list uses a single-column list of cards on overview; send/receive uses full-width form

### Navigation Patterns
- Sidebar navigation with icon + label
- Dropdown sub-menus for Profile and Security
- Breadcrumb-less navigation (page titles used instead)
- Modal-free major flows (full page navigation for send/receive/swap)
- Inline panel for notifications (slide-in from right)

### Components Identified

| Component | Usage |
|---|---|
| Wallet balance hero card | Overview — shows total balance + Send/Receive CTAs |
| Asset list item card | Overview — one per supported token |
| Swap form card | Swap page |
| Receive QR card | Receive page |
| Send form | Send page |
| Virtual card visual | Qfs Card page |
| Tier comparison cards | Mining / Investments (upgrade prompts) |
| Alert/warning banner | Receive page, Mining gate, KYC form |
| Support ticket list | Support page |
| Notification panel | Slide-in overlay |
| Profile card | Account Settings — left panel |
| Form sections | KYC, Account Settings, Security |
| Progress/status badge | KYC status pill |

---

## 5. DASHBOARD COMPONENTS

| Component | Purpose |
|---|---|
| **Total Balance KPI card** | Primary portfolio value in USD; zero state for new users |
| **RECEIVE / SEND action buttons** | Quick-access CTAs in the balance hero card |
| **Asset rows (29 items)** | Per-asset USD value held, native units held, current price, 24h % change |
| **Price change indicators** | Red/green percent with arrow — live market data display |
| **"New" badge** | Flags recently added assets (XAUT, PAXG, KAG) |
| **"Popular" badge** | Highlights high-demand assets (XRP) |
| **Wallet icon** | Top right of balance card — decorative/navigation hint |
| **QR code** | Receive page — scannable deposit address |
| **Copy button** | Receive page — one-click clipboard copy of address |
| **Network info table** | Receive page — Network name, Expected arrival, Expected unlock confirmations |
| **MAX button** | Swap page — fills 100% of available balance |
| **Token swap toggle** | Swap page — reverses From/To asset selection |
| **Recent Swaps table** | Swap page — transaction history (empty state shown) |
| **Premium card tier cards** | Mining/Investments — VirtuElevate vs VirtuElite comparison |
| **Benefit icons** | Mining page — Passive Income, Portfolio Diversification, Support Networks |
| **Wallet search** | Connect Wallet — find from 178 wallet providers |
| **Wallet provider grid** | Connect Wallet — 178 results in card grid |
| **KYC form** | Multi-section identity verification |
| **Photo upload inputs** | KYC — front/back document scan |
| **Support ticket tabs** | All / Open / Closed filter tabs |
| **Notification badge** | Bell icon — count indicator |
| **User dropdown menu** | Profile quick actions from top nav |

---

## 6. DATABASE ARCHITECTURE

### Entity: User

```
users {
  id: int (PK)
  username: string
  full_name: string
  email: string
  password_hash: string
  avatar_url: string (nullable)
  current_ip: string
  kyc_status: enum [unverified, pending, verified, rejected]
  two_fa_enabled: boolean
  two_fa_secret: string (nullable)
  recovery_phrase: string (encrypted)
  created_at: timestamp
  updated_at: timestamp
}
```

### Entity: Wallet (per user per asset)

```
wallets {
  id: int (PK)
  user_id: int (FK → users)
  currency_id: int (FK → currencies)
  address: string
  balance: decimal(20,8)
  locked_balance: decimal(20,8)
  network: string
  created_at: timestamp
}
```

### Entity: Currency

```
currencies {
  id: int (PK)
  symbol: string (e.g., BTC, ETH, USDT)
  name: string
  network: string (Bitcoin, ERC-20, TRC-20, BEP-20, SOL)
  icon_url: string
  is_active: boolean
  is_new: boolean
  is_popular: boolean
  expected_arrival_confirmations: int
  expected_unlock_confirmations: int
  current_price_usd: decimal
  price_change_24h_pct: decimal
  updated_at: timestamp
}
```

### Entity: Transaction

```
transactions {
  id: int (PK)
  user_id: int (FK → users)
  wallet_id: int (FK → wallets)
  type: enum [send, receive, swap, mining_reward, investment_return]
  status: enum [pending, completed, failed]
  amount: decimal(20,8)
  currency_id: int (FK → currencies)
  recipient_address: string (nullable)
  recipient_user_id: int (FK → users, nullable — internal transfer)
  tx_hash: string (nullable)
  fee: decimal(20,8)
  created_at: timestamp
}
```

### Entity: Swap

```
swaps {
  id: int (PK)
  user_id: int (FK → users)
  from_currency_id: int (FK → currencies)
  to_currency_id: int (FK → currencies)
  from_amount: decimal(20,8)
  to_amount: decimal(20,8)
  exchange_rate: decimal(20,8)
  fee: decimal(20,8)
  status: enum [pending, completed, failed]
  created_at: timestamp
}
```

### Entity: VirtualCard

```
virtual_cards {
  id: int (PK)
  user_id: int (FK → users)
  card_tier: enum [VirtuElevate, VirtuElite]
  card_number_masked: string
  card_type: string ("Mastercard")
  issuer: string ("WebBank")
  status: enum [pending, active, suspended, cancelled]
  price_paid_usd: decimal
  created_at: timestamp
  expires_at: timestamp
}
```

### Entity: KYC Application

```
kyc_applications {
  id: int (PK)
  user_id: int (FK → users)
  first_name: string
  last_name: string
  email: string
  phone_number: string
  date_of_birth: date
  social_handle: string (nullable)
  address_line: string
  city: string
  state: string
  nationality: string
  document_type: enum [drivers_license, passport, national_id]
  document_front_url: string
  document_back_url: string
  status: enum [pending, approved, rejected]
  submitted_at: timestamp
  reviewed_at: timestamp (nullable)
}
```

### Entity: Support Ticket

```
support_tickets {
  id: int (PK)
  user_id: int (FK → users)
  subject: string
  body: text
  status: enum [open, closed]
  created_at: timestamp
  updated_at: timestamp
}
```

### Entity: Notification

```
notifications {
  id: int (PK)
  user_id: int (FK → users)
  type: string
  message: text
  is_read: boolean
  created_at: timestamp
}
```

### Entity: LinkedWallet

```
linked_wallets {
  id: int (PK)
  user_id: int (FK → users)
  provider_name: string (e.g., "MetaMask")
  provider_id: int (FK → wallet_providers)
  address: string
  connected_at: timestamp
}
```

### Entity: WalletProvider

```
wallet_providers {
  id: int (PK)
  name: string
  logo_url: string
  website: string (nullable)
  is_active: boolean
}
```

---

## 7. USER ROLES & PERMISSIONS

### Defined Roles

| Role | Description |
|---|---|
| **Guest** | Unauthenticated visitor; access to registration/login only |
| **Registered (Unverified)** | Authenticated but no KYC; can view balances, receive crypto; cannot send, cannot access cards |
| **KYC Verified** | Can receive and send crypto, request base QFS card |
| **VirtuElevate Holder** | KYC + VirtuElevate card; unlocks Mining + premium transaction limits + reduced fees |
| **VirtuElite Holder** | KYC + VirtuElite card; unlocks Mining + Investments + highest limits + zero fees + priority support |
| **Admin** | Full platform access (internal administration panel) |

### Permission Matrix

| Feature | Guest | Unverified | KYC Verified | VirtuElevate | VirtuElite |
|---|---|---|---|---|---|
| View balances | ✗ | ✓ | ✓ | ✓ | ✓ |
| Receive crypto | ✗ | ✓ | ✓ | ✓ | ✓ |
| Send crypto | ✗ | ✗ | ✓* | ✓ | ✓ |
| Swap | ✗ | ✓ | ✓ | ✓ | ✓ |
| QFS Card (basic) | ✗ | ✗ | ✓ | — | — |
| Mining | ✗ | ✗ | ✗ | ✓ | ✓ |
| Investments | ✗ | ✗ | ✗ | ✓ | ✓ |
| Reduced fees | ✗ | ✗ | ✗ | ✓ | ✓ |
| Zero fees | ✗ | ✗ | ✗ | ✗ | ✓ |
| Priority support | ✗ | ✗ | ✗ | ✗ | ✓ |

*Sending requires QFS card activation

---

## 8. BUSINESS LOGIC

### Revenue Model
- **Premium card fees:** VirtuElevate and VirtuElite card tiers are the primary monetization mechanism
- **Exchange fees on swaps:** Standard platform fee per swap (reduced for VirtuElevate, zero for VirtuElite)
- **Transaction fees:** Applied to sends (reduced/waived by tier)
- **WebBank partnership:** Revenue share on Mastercard interchange and card issuance fees

### Key Business Flows

**Onboarding Flow:**
Register → Email Verification → Profile Setup → KYC Submission → KYC Approval → Fund Wallet → Activate QFS Card → Unlock Premium Features

**Send Flow:**
Select Send → Choose Asset → Choose Recipient Type (External Address or Internal Username) → Enter Amount → Enter Address → Continue → Confirmation → Submit → Transaction Broadcast

**Receive Flow:**
Select Receive → Choose Asset → Display QR + Address → User shares address → Incoming transaction detected → Balance updated after N confirmations

**Swap Flow:**
Select From asset and amount → Select To asset → Review rate → Confirm → Internal ledger update (or on-chain swap)

**Mining Reward Flow:**
Premium card holder → Platform participates in network mining on behalf of user → Periodic reward disbursement to user wallet

**KYC Gate:**
Form submission → Document upload → Manual or automated review → Approval/rejection notification → Feature unlock

### Validation Rules
- Send blocked if QFS card not activated
- Mining/Investments blocked if no premium card
- KYC form data cannot be edited after submission
- Asset-specific address validation (wrong asset to wrong address = permanent loss warning)

### Notification System
- Activity feed in notification panel
- Badge counter on bell icon
- Empty state handled gracefully

---

## 9. TECHNICAL ARCHITECTURE

| Aspect | Assessment | Confidence |
|---|---|---|
| **Frontend Framework** | Laravel Blade / PHP server-rendered HTML with jQuery or vanilla JS; possible Vue.js for reactive components (Swap form) | High |
| **CSS Framework** | Custom CSS or Bootstrap 4/5 with custom theme | Medium-High |
| **Backend** | PHP (Laravel) | High |
| **Authentication** | Session-based cookie auth (Laravel sessions); 2FA via TOTP app | High |
| **Database** | MySQL / PostgreSQL | High |
| **Crypto Price Feed** | Third-party API (CoinGecko, CoinMarketCap, or similar) | High |
| **Wallet Infrastructure** | Custodial — platform manages wallets on behalf of users with encrypted recovery phrase storage | High |
| **File Storage** | Cloud storage (AWS S3 or equivalent) for KYC document uploads | Medium |
| **WalletConnect / Web3** | WalletConnect v2 protocol for the "Connect Wallet" feature (178 providers) | High |
| **Live Chat** | Smartsupp — widget in bottom-right of dashboard | Confirmed |
| **State Management** | Server-side sessions; minimal SPA behavior | Medium |
| **Hosting** | VPS or cloud (AWS/DigitalOcean) | Estimated |
| **Deployment** | Standard LAMP/LEMP stack | Medium |

---

## 10. DEVELOPMENT ROADMAP

### Pages to Build

| Page | Route | Priority |
|---|---|---|
| Dashboard Overview | /dashboard | P0 |
| Send — Asset Selection | /dashboard/wallet/{id}?mode=send | P0 |
| Send Form | /dashboard/send/{id} | P0 |
| Receive — Asset Selection | /dashboard/wallet/{id}?mode=receive | P0 |
| Receive — QR & Address | /dashboard/receive/{id} | P0 |
| Swap | /dashboard/swap | P0 |
| Connect Wallet | /dashboard/backup | P1 |
| Virtual Cards | /dashboard/virtual-cards | P1 |
| Mining | /dashboard/mining | P1 |
| Investments | /dashboard/investments | P1 |
| Account Settings | /dashboard/account-settings | P1 |
| 2FA Management | /dashboard/manage-account-security | P1 |
| KYC Verification | /kyc-verification | P1 |
| Support Tickets | /support | P2 |
| Notifications | (panel/overlay) | P2 |

### API Endpoints

```
AUTH
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/logout
POST   /api/auth/reset-password

USER / PROFILE
GET    /api/user/profile
PUT    /api/user/profile
POST   /api/user/upload-avatar
GET    /api/user/recovery-phrase

KYC
POST   /api/kyc/submit
GET    /api/kyc/status

WALLETS
GET    /api/wallets                  → all user wallets + balances
GET    /api/wallets/{id}             → single wallet
GET    /api/wallets/{id}/address     → deposit address

TRANSACTIONS
GET    /api/transactions             → full history
POST   /api/transactions/send        → initiate send
POST   /api/transactions/receive     → confirm receive (webhook)

SWAP
GET    /api/swap/rate?from=X&to=Y&amount=Z
POST   /api/swap/execute
GET    /api/swap/history

MARKET DATA
GET    /api/market/prices            → all asset prices + 24h change

VIRTUAL CARDS
GET    /api/cards
POST   /api/cards/request

MINING
GET    /api/mining/status
GET    /api/mining/rewards

INVESTMENTS
GET    /api/investments
POST   /api/investments/create

SUPPORT
GET    /api/support/tickets
POST   /api/support/tickets
GET    /api/support/tickets/{id}

NOTIFICATIONS
GET    /api/notifications
POST   /api/notifications/{id}/read
```

### Database Build Priorities
1. users, wallets, currencies (core)
2. transactions (critical path)
3. swaps (exchange feature)
4. kyc_applications, virtual_cards (compliance/monetization)
5. support_tickets, notifications (support layer)
6. linked_wallets, wallet_providers (Web3 connection)

### Development Sprints

| Sprint | Focus |
|---|---|
| 1 | Auth (register/login/logout/2FA), user model, session management |
| 2 | Wallet infrastructure (generate addresses per asset, balance engine) |
| 3 | Overview dashboard (portfolio view, live price feed integration) |
| 4 | Receive flow (QR generation, address display) |
| 5 | Send flow (form, validation, transaction broadcast) |
| 6 | Swap engine (rate API, execution, history) |
| 7 | KYC (form, document upload, review queue) |
| 8 | Virtual Cards (tiers, issuance, WebBank integration) |
| 9 | Mining + Investments (gated feature, reward distribution) |
| 10 | Notifications, Support Tickets, polish, mobile responsive |

---

## 11. FEATURE GAP ANALYSIS & RECOMMENDATIONS

### Identified but Not Yet Fully Implemented
- **Internal user transfers** — Send form includes the option to send to another Quantum BlocX user using their username or email; this implies a full internal ledger transfer system with user lookup functionality
- **Recovery Phrase management** — The platform generates and stores an encrypted BIP-39 mnemonic for each user; security best practices should be reviewed for this custodial responsibility
- **"QFSL" sub-brand** — Appears on the swap form and elsewhere as a watermark; may represent "Quantum Financial System Ledger" as a sub-brand or product line

### Planned / Recommended Features
- **Transaction history pages** — Per-wallet transaction history views accessible by clicking on each asset
- **Card top-up / load flow** — Mechanism to load fiat or crypto value onto the QFS Card for spending
- **Admin dashboard** — Separate admin panel for approving KYC, managing users, card issuance, and support ticket responses
- **Email notification system** — Transaction confirmations, KYC status updates, and ticket replies should trigger transactional emails
- **Mining dashboard (post-activation)** — Hashrate stats, earnings, and payout history for premium cardholders
- **Investment portfolio view (post-activation)** — APY rates, invested amounts, and earnings history

### Future Considerations
- **Referral / affiliate program** — Common in the crypto wallet space; could drive organic acquisition
- **Fiat on-ramp / off-ramp** — May be accessible through the QFS Card or a separate bank transfer section
- **Price alert / notification settings** — Notification system exists but user-configurable alerts would add value
- **Mobile app** — Mastercard integration and QR-code focus suggest a mobile app would complement the platform

---

## 12. PLATFORM ANALYSIS SUMMARY

### Feature Status Overview

| # | Feature | Status | Gating |
|---|---|---|---|
| 1 | Portfolio Overview | Live | None |
| 2 | Asset Balances (29 assets) | Live | None |
| 3 | Live Price Feed | Live | None |
| 4 | Receive Crypto (QR + address) | Live | None |
| 5 | Send Crypto (external address) | Live | QFS Card required |
| 6 | Send Crypto (internal user) | Live | QFS Card required |
| 7 | Swap Tokens | Live | None |
| 8 | Connect External Wallet (178 providers) | Live | None |
| 9 | QFS Virtual Card Request | Live | KYC required |
| 10 | Mining | Live (gated) | VirtuElevate/Elite |
| 11 | Investments | Live (gated) | VirtuElevate/Elite |
| 12 | KYC Verification | Live | Registered user |
| 13 | 2FA Authentication | Live | Registered user |
| 14 | Password Reset | Live | Authenticated |
| 15 | Recovery Phrase Reveal | Live | Authenticated |
| 16 | Support Ticket System | Live | Authenticated |
| 17 | Notification Center | Live | Authenticated |
| 18 | Transaction History | Planned | Authenticated |
| 19 | Card Spend/Load | Planned | Card holder |
| 20 | Mining Dashboard | Planned | Premium card holder |
| 21 | Investment Portfolio View | Planned | Premium card holder |
| 22 | Admin Panel | Planned | Admin only |
| 23 | Referral Program | Under Consideration | — |
| 24 | Mobile App | Under Consideration | — |

---

### Site Structure Map (Condensed)

```
[Landing / Auth]
    └── Registration → Verification → Dashboard

[Dashboard /dashboard]
    ├── Overview (portfolio)
    ├── Connect Wallet (/backup)
    │    └── 178 wallet providers, searchable grid
    ├── Send (/wallet/{id}?mode=send → /send/{id})
    │    ├── Asset selector
    │    ├── External address mode
    │    └── Internal user mode (username/email)
    ├── Receive (/wallet/{id}?mode=receive → /receive/{id})
    │    ├── QR code
    │    ├── Copyable address
    │    └── Network confirmation details
    ├── Swap (/swap)
    │    ├── From/To asset selectors
    │    ├── Amount input + MAX button
    │    ├── Direction toggle
    │    └── Recent Swaps history
    ├── Mining (/mining)
    │    ├── [Gated: premium card required]
    │    └── Card tier upgrade prompt
    ├── Qfs Card (/virtual-cards)
    │    ├── Card visual (Mastercard / WebBank)
    │    └── Request New Card CTA
    ├── Investments (/investments)
    │    ├── [Gated: premium card required]
    │    └── Card tier upgrade prompt
    ├── Profile
    │    ├── My Profile (/account-settings)
    │    └── KYC (/kyc-verification)
    ├── Notification (panel)
    ├── Security
    │    ├── Change Password (/account-settings)
    │    └── 2FA (/manage-account-security)
    └── Support (/support)
         ├── All/Open/Closed tickets
         └── New Ticket CTA
```

---

### Summary

Quantum BlocX is a **feature-rich, custodial multi-asset crypto wallet platform** built around the QFS brand identity and the XRP ecosystem. The platform monetizes through **premium virtual card tiers** (VirtuElevate and VirtuElite), which serve as both a spend card and a platform feature access key. The custodial architecture — including platform-managed wallets and encrypted recovery phrase storage — requires careful attention to **security best practices and regulatory compliance**.

From a product standpoint, the UX is clean and accessible for a non-technical audience, with clear visual hierarchy, consistent iconography, and well-implemented empty states. The tiered feature gating creates a structured upgrade path for users.

Technically, the platform runs on a **server-rendered PHP/Laravel stack** with JavaScript enhancements, a custodial wallet backend, third-party crypto price feeds, Smartsupp live chat, and a WebBank Mastercard issuance partnership — a solid and maintainable architecture for this type of platform.

---

*End of Document — Quantum BlocX Technical Audit & Development Specification*
