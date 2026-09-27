# Pay-in & Payout Module (Laravel + Backpack)

Backend module for initiating **pay-ins** and **payouts**, resolving them through a
scheduled cron job, and keeping a per-merchant wallet balance in sync. Built to make
sure a payment can never get processed (and never move a wallet balance) more than once.


**Pay-in** = money coming in from a customer to the merchant — adds to the merchant's
wallet balance once it succeeds.

**Payout** = money going out from the merchant to someone else (a vendor, a
beneficiary bank account) — deducts from the merchant's wallet balance.
---

## 1. Tech / Structure

```
app/
  Models/            Merchant, Wallet, Payin, Payout, PaymentLog
  Services/          WalletService, PaymentService, PaymentProcessingService, PaymentLogService
  Http/
    Controllers/Api/     PayinController, PayoutController
    Controllers/Admin/   Backpack CRUD controllers (Merchant, Payin, Payout, Wallet)
    Requests/            StorePayinRequest, StorePayoutRequest
    Middleware/          EnsureMerchantApiKey
  Console/
    Kernel.php         schedules the cron
    Commands/           ProcessPendingPayments
  Helpers/             TransactionHelper
database/
  migrations/          merchants, wallets, payins, payouts, payment_logs
  seeders/              MerchantSeeder (creates merchants + wallets)
routes/
  api.php               public API
  backpack/custom.php   admin CRUD routes
```

Controllers stay thin — they just validate input and format the response. All the
actual logic (transaction id generation, wallet math, status transitions, duplicate
protection) lives in `app/Services`.

---

## 2. Database Design

| Table | Purpose |
| `merchants` | merchant account + unique `api_key` for API auth |
| `wallets` | one row per merchant, holds current `balance` |
| `payins` / `payouts` | one row per payment attempt — unique `transaction_id`, `status` enum (`PENDING`/`SUCCESS`/`FAILED`), full request stored in a `meta` json column for audit |
| `payment_logs` | append-only event log for every pay-in/payout — initiation, status changes, processing outcome — used for auditing and debugging |

Relationships: `Merchant hasOne Wallet`, `Merchant hasMany Payin/Payout`.

Indexes: `status`, `(merchant_id, status)`, `created_at` on `payins`/`payouts` for
fast filtering; unique indexes on `transaction_id`, `email`, `api_key`.

Instead of a separate ledger table, duplicate-crediting is prevented with boolean
guard flags directly on the payment row (`wallet_credited`, `wallet_debited`,
`wallet_reversed`) — see below.

---

## 3. Duplicate-processing protection

1. The cron pulls `PENDING` rows in batches.
2. For each row individually, it opens a DB transaction and locks that one row with
   `lockForUpdate()`, then re-checks it's still `PENDING` — if it's already been
   resolved by another run, it's skipped.
3. The wallet row itself is also locked with `lockForUpdate()` before its balance
   is read or changed.
4. A boolean flag (`wallet_credited` for pay-ins, `wallet_reversed` for payouts) is
   checked before touching the wallet and set right after — so even if the same
   record somehow got processed twice, the balance only moves once.
5. The scheduled task also runs with `->withoutOverlapping()` as an extra safety
   net, though the row-locking above is what actually guarantees correctness.

---

## 4. Setup Instructions

```bash
composer create-project laravel/laravel payin-payout
cd payin-payout

# copy this module's app/, database/, routes/ folders into the project,
# merging with what Laravel generated

composer require backpack/crud
php artisan backpack:install   

cp .env.example .env
php artisan key:generate
# set DB_* credentials in .env

php artisan migrate --seed
```

Register the API-key middleware alias in `app/Http/Kernel.php`:
```php
'merchant.api' => \App\Http\Middleware\EnsureMerchantApiKey::class,
```

Each model shown in a Backpack CRUD panel (`Merchant`, `Wallet`, `Payin`, `Payout`)
needs `use CrudTrait;` added — Backpack throws an error without it.

```bash
php artisan serve
```

### Running the cron locally
```bash

# for run one time payment process run below command 
php artisan payments:process

# if continuously run , simulating production cron
php artisan schedule:work
```

### Production cron entry (crontab -e)
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. API Documentation & Sample Requests

Every request needs an `X-API-KEY` header identifying the merchant. Seeded demo
merchants:

| Merchant | API Key |
| Demo Store Pvt Ltd | `test_api_key_demo_store_123456` |
| Second Merchant Co | `test_api_key_second_merchant_654321` |

New merchants created through the admin panel get their key auto-generated — check
the Preview page, or run `php artisan tinker` and:
```php
App\Models\Merchant::where('email', 'someone@example.com')->value('api_key');
```

### Initiate a Pay-in
```
POST /api/v1/payins
Content-Type: application/json
X-API-KEY: <merchant_api_key>

{
  "amount": 500.00,
  "currency": "INR",
  "customer_name": "Rahul Sharma",
  "customer_email": "rahul@example.com",
  "payment_method": "upi"
}
```
**Response `201`**
```json
{
  "success": true,
  "message": "Pay-in initiated",
  "data": {
    "transaction_id": "PIN-20260926-AB12CD34",
    "status": "PENDING",
    "amount": "500.00",
    "currency": "INR",
    "created_at": "2026-09-26T10:00:00.000000Z"
  }
}
```

### Initiate a Payout
```
POST /api/v1/payouts
X-API-KEY: <merchant_api_key>

{
  "amount": 250.00,
  "beneficiary_name": "Priya Verma",
  "beneficiary_account": "123456789012",
  "ifsc_code": "HDFC0001234"
}
```
Same response shape, `transaction_id` prefixed `POUT-...`. Funds are debited from
the wallet immediately at initiation, not on success 

### Check a payment's status
```
GET /api/v1/payins/{transaction_id}
GET /api/v1/payouts/{transaction_id}
```

### List / filter payments
```
GET /api/v1/payins?status=SUCCESS
GET /api/v1/payouts?status=PENDING
```

### Validation error example
```
POST /api/v1/payins   { "amount": -5 }
```
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount field must be at least 1."]
  }
}
```

---

## 6. Admin Panel (Backpack)

Visit `/admin` after `backpack:install`:
- **Merchants** — full CRUD; creating a merchant auto-generates its `api_key` and wallet.
- **Pay-ins / Payouts** — list + show (they're created via API, resolved by cron);
  filterable by status, merchant, and date via query params, e.g.
  `/admin/payin?status=PENDING&merchant_id=1`.
- **Wallets** — list + show; filterable by merchant. Balances only ever change
  through `WalletService`.

---

## 7. Logging

Logged at every stage:
- payment initiated (full request payload + transaction id)
- each cron pass: per-payment outcome (SUCCESS/FAILED/left PENDING)
- wallet balance change
- payout failed due to insufficient balance
- any exception during initiation or processing

Check `storage/logs/laravel.log`, plus the `payment_logs` table for a structured,
queryable version of the same events.

---

## 8. Summary

- Merchants, wallets, pay-ins and payouts with proper relationships and indexes
- REST API to initiate pay-ins/payouts, authenticated per merchant via API key
- Cron command that resolves pending payments and updates wallet balances safely,
  with row-level locking so nothing gets processed twice
- Backpack admin panel for Merchants, Pay-ins, Payouts and Wallets with basic
  status/merchant/date filtering
- Logging to both file and a dedicated `payment_logs` table
- UI kept to Backpack's default theme, no custom styling — status resolution is
  randomised since there's no real payment gateway wired up
---

## 9. Git

```powershell
git init
git add .
git commit -m "Pay-in & Payout module with Laravel + Backpack"
git remote add origin <your-repo-url>
git push -u origin main
```
`.env` is excluded via `.gitignore` — never commit real credentials.
