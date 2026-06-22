# Database dump

`b2b_dashboard_seed.sql` is a full **schema + demo seed** export (MySQL), provided as a
runnable alternative to `php artisan migrate --seed`.

## Contents

- All application tables (businesses, users, products, orders, order_items, sales,
  inventories, order_audits, idempotency_keys, processed_webhooks) plus framework tables
  (cache, jobs, sessions, personal_access_tokens, migrations, …).
- One demo business (`Acme Corp`) with 5 products, stocked inventory, and ~40 orders spread
  over the last 30 days so the analytics dashboard has data.

## Import

The dump is portable — it does **not** hard-code a database name, so import it into whatever
database your `.env` points at:

```bash
# create the target database first if needed
mysql -u root -p -e "CREATE DATABASE b2b_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# then load schema + seed
mysql -u root -p b2b_dashboard < database/dump/b2b_dashboard_seed.sql
```

## Demo login

```
email:    test@example.com
password: password
```

> Equivalent to running `php artisan migrate --seed`. Use one or the other, not both.