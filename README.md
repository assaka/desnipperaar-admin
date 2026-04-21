# DeSnipperaar Admin

Laravel 11 admin for orders, pickup bons, certificates of destruction.

## First deploy

On the VPS (PHP 8.3+, composer, Postgres client installed):

```bash
git clone git@github.com:assaka/desnipperaar-admin.git
cd desnipperaar-admin
cp .env.example .env   # fill in DB_PASSWORD, RESEND_API_KEY, ADMIN_SEED_PASSWORD
./deploy.sh
php artisan db:seed
```

## Routes

| Path | Description |
|---|---|
| `/` | Redirects to `/orders` |
| `/login` | Admin login |
| `/orders` | Orders list |
| `/orders/{n}` | Order detail + state transitions |
| `/bons/{n}` | Bon detail |
| `/certificates/{n}` | Certificate detail |
| `POST /api/offerte` | Public — called by static site form |
| `POST /api/contact` | Public — called by static site form |

## Numbering

Order#: `DS-2026-0142` and up.
Bon#: `P-2026-0142` (ophaal). `B-` and `M-` reserved for dropoff / mobile.
Certificate#: `C-2026-0142`.

Starting sequence is configurable via `DESNIPPERAAR_ORDER_START` in `.env`.
