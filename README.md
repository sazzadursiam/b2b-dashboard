# B2B Executive Dashboard API

A high-volume, multi-tenant (multi-business) B2B e-commerce backend built on **Laravel 12**.
The focus is production-grade caching with cache-stampede prevention, event-driven order
processing, idempotency, queue management, and per-business data scoping.

> 📐 See **[ARCHITECTURE.md](ARCHITECTURE.md)** for the layering, multi-tenant scoping model,
> caching/stampede strategy, and order lifecycle.

## Stack

| Concern | Driver |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Auth | Laravel Sanctum (token-based API auth) |
| Database | MySQL |
| Cache | Redis (required — used for cache **tags** + atomic **locks**) |
| Queue | Redis |

> **Why Redis for cache is not optional:** the dashboard uses `Cache::tags()` and
> `Cache::lock()`. The `database`/`file` cache stores do **not** support tagging, so Redis
> (or another taggable store) is required in any real environment.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # seeds a demo business + products + orders
```

### Required `.env` variables

```dotenv
APP_KEY=                       # php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=b2b_dashboard
DB_USERNAME=root
DB_PASSWORD=secret

CACHE_STORE=redis              # MUST be a taggable+lockable store (Redis)
QUEUE_CONNECTION=redis         # database is also acceptable

REDIS_CLIENT=phpredis          # or "predis" (composer package, no PHP ext needed)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_PREFIX=b2b_dashboard_    # isolates keys if the Redis box is shared
```

The seeder creates a ready-to-use login:

```
email:    test@example.com
password: password
```

Run the app and a queue worker (the queue runs on Redis):

```bash
php artisan serve
php artisan queue:work redis
```

### Alternative: import the SQL dump

If you'd rather not run migrations, a full schema + demo-seed dump is provided:

```bash
mysql -u root -p b2b_dashboard < database/dump/b2b_dashboard_seed.sql
```

## Architectural decisions (in brief)

- **Service + Action-style layer, no Repository.** Eloquent already is the data-access
  abstraction; wrapping it in repositories adds indirection without payoff for an app this
  size. Logic lives in focused services (`OrderService`, `OrderStateService`,
  `DashboardAnalyticsService`, `IdempotencyService`). See [ARCHITECTURE.md](ARCHITECTURE.md).
- **Central multi-tenancy.** A global scope + model trait + request-scoped `CurrentBusiness`
  make tenant isolation automatic instead of relying on every query remembering a `where`.
- **Cache stampede** is solved with an atomic `Cache::lock(...)->block(...)` so exactly one
  request rebuilds a cold cache; the rest wait and read the warmed value.
- **Event-driven side effects.** State changes emit a domain event; inventory, notifications,
  cache refresh, and refunds happen via queued listeners/jobs to keep requests fast.
- **Idempotency everywhere** that retries can happen: an `Idempotency-Key` table for orders and
  a `transaction_id` ledger for webhooks.

## Key Endpoints

| Method | Path | Notes |
|---|---|---|
| `POST` | `/api/v1/login` | Issues a Sanctum token |
| `GET` | `/api/v1/analytics/dashboard` | Cached 1h, stampede-protected, rate-limited 200/min per business |
| `POST` | `/api/v1/orders` | Supports `Idempotency-Key` header (24h) |
| `GET` | `/api/v1/orders` | Cursor pagination (`next_cursor`) |
| `PATCH`| `/api/v1/orders/{id}/status` | State machine transitions |
| `POST` | `/webhooks/payment` | Public, idempotent on `transaction_id`, out-of-order tolerant |
| `GET` | `/health` | DB / cache / queue health |

### Cache stampede prevention

When the dashboard cache is cold, a single atomic lock (`Cache::lock(..., 10)->block(5)`)
ensures **only one** request recomputes the heavy aggregation. Concurrent requests block
until the cache is populated, then read it — they never touch the database. Cache is keyed
and tagged per business (`business_{id}`) and invalidated via the `RefreshAnalyticsCache`
job, which is `ShouldBeUnique` per business so a burst of sales triggers at most one rebuild.

## Artisan

```bash
php artisan analytics:warm-cache                 # dispatch a refresh job for every business
php artisan analytics:warm-cache --business=123  # just one business
```

## Testing

```bash
php artisan test
```

Covered critical scenarios (PHPUnit): cache stampede, order idempotency, out-of-order
webhooks, sale → job dispatch, and rate limiting.

### ⚠️ The test database must be a dedicated, disposable database

The test suite uses Laravel's `RefreshDatabase` trait, which runs **`migrate:fresh`**.
`migrate:fresh` **DROPS EVERY TABLE** in the connected database before re-migrating — not
just this project's tables. If the test connection ever points at a shared or production
database, **all of its data is destroyed** (this is irreversible).

For that reason the test connection is configured in `phpunit.xml` to use a **separate**
database, never the one in `.env`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="b2b_dashboard_test"/>
<env name="CACHE_STORE" value="array"/>   <!-- isolated, supports tags + locks -->
<env name="QUEUE_CONNECTION" value="sync"/>
```

Create that database once before running the suite:

```sql
CREATE DATABASE b2b_dashboard_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

> The conventional choice is in-memory SQLite (`DB_DATABASE=:memory:`), which is isolated by
> nature. Use it instead if the `pdo_sqlite` extension is available; this project ships with a
> dedicated MySQL test database because only `pdo_mysql` was present in the target environment.

**Rule of thumb:** never point `phpunit.xml` (or `.env.testing`) at a database that holds
data you care about.

## Multi-business scoping

A user belongs to exactly one business. Every authenticated query is scoped to
`auth()->user()->business_id`, and on writes the `business_id` is assigned from the
authenticated user — never from request input. The mechanism (global scope + trait +
`CurrentBusiness`) is detailed in [ARCHITECTURE.md](ARCHITECTURE.md#multi-tenancy-the-most-important-design-decision).

## Data Model

All tenant tables carry a `business_id` foreign key.

| Table | Key columns |
|---|---|
| `businesses` | `id`, `name`, `owner_id` → users, `timezone`, `subscription_tier` |
| `users` | `id`, `business_id`, `name`, `email`, `password` |
| `products` | `id`, `business_id`, `name`, `price` |
| `orders` | `id`, `business_id`, `user_id`, `status`, `total_amount`, `idempotency_key` |
| `order_items` | `id`, `business_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price` |
| `sales` | `id`, `business_id`, `order_id`, `product_id`, `quantity`, `total_amount` |
| `inventories` | `id`, `business_id`, `product_id`, `stock`, `reserved` (unique `business_id`+`product_id`) |
| `order_audits` | `id`, `order_id`, `business_id`, `from_state`, `to_state`, `user_id` (nullable), `metadata` (json) |
| `idempotency_keys` | `id`, `business_id`, `key`, `endpoint`, `response_body` (json), `status_code`, `expires_at` |
| `processed_webhooks` | `id`, `transaction_id` (unique), `event`, `payload` (json) |

## Requirements Coverage

| # | Requirement | Implementation | Test |
|---|---|---|---|
| 1 | Schema, Sanctum auth, multi-business scoping | migrations, `AuthController`, `BelongsToBusiness` + `BusinessScope` + `CurrentBusiness` + `SetCurrentBusiness` | `TenantScopeTest`, `OrderIdempotencyTest::business_id…` |
| 2 | Dashboard analytics + cache tags + stampede lock + `cached`/`generated_at` | `DashboardAnalyticsService`, `AnalyticsController` | `CacheStampedeTest` |
| 3 | Sale event → `RefreshAnalyticsCache`, `ShouldBeUnique` per business, atomic lock | `Sale::booted()`, `RefreshAnalyticsCache` | `SaleJobDispatchTest` |
| 4 | Orders endpoint, idempotency, state machine, domain event, inventory + notification listeners | `OrderService`, `OrderStateService`, `OrderStatus`, `OrderStatusChanged`, `UpdateInventoryListener`, `LogOrderNotificationListener` | `OrderIdempotencyTest`, `WebhookOutOfOrderTest` |
| 5 | Payment webhook: out-of-order tolerant + idempotent on `transaction_id` | `PaymentWebhookController`, `ProcessedWebhook` | `WebhookOutOfOrderTest` |
| 6 | Compensation: inventory failure blocks `processing` + refund job | `OrderStateService::reserveInventory`, `RefundPaymentJob` | `WebhookOutOfOrderTest` (state machine) |
| 7 | `analytics` rate limiter, 200/min per `business_id` | `AppServiceProvider::boot`, `throttle:analytics` | `RateLimitTest` |
| 8 | Audit log + `/health` (db/cache/queue) | `OrderAudit`, `order_audits` table, `HealthController` | covered via transitions |
| 9 | Cursor pagination + `next_cursor`, tenant-scoped | `OrderController::index` | `TenantScopeTest` |
| 10 | `analytics:warm-cache` (+`--business`, progress bar, timing) | `WarmAnalyticsCache` | manual / command |

### Required test scenarios

| Scenario | Test |
|---|---|
| Cache stampede → only one DB rebuild | `CacheStampedeTest::only_one_request_rebuilds_the_dashboard_cache` |
| Idempotency → one order, identical response | `OrderIdempotencyTest::repeated_request_with_same_idempotency_key…` |
| Webhook out-of-order handled gracefully | `WebhookOutOfOrderTest::out_of_order_event_is_handled_gracefully` |
| Sale created → job dispatched with business_id | `SaleJobDispatchTest::creating_a_sale_dispatches_refresh_job…` |
| 201st dashboard request → 429 | `RateLimitTest::dashboard_is_limited_to_200_requests…` |

## Postman

Import **[`B2B-Dashboard.postman_collection.json`](B2B-Dashboard.postman_collection.json)**.
Set the `base_url` variable if needed (defaults to `http://localhost:8000`), then run
**Auth → Login** first — it captures the Sanctum token into a collection variable that every
other request reuses automatically. `Create Order` auto-generates an `Idempotency-Key` and
stores the new `order_id` for the status/webhook requests.

## API examples

```http
POST /api/v1/login
{ "email": "test@example.com", "password": "password" }
→ 200 { "token": "…", "user": { "id": 1, "business_id": 1, … } }

POST /api/v1/orders
Authorization: Bearer <token>
Idempotency-Key: 7c3f…           # optional; replays within 24h
{ "items": [ { "product_id": 1, "quantity": 2 } ] }
→ 201 { "message": "Order created successfully.", "order": { "id": 1, "status": "pending", … } }

PATCH /api/v1/orders/1/status
{ "status": "paid" }             # validated against the OrderStatus enum
→ 200 { "message": "Order status updated.", "order": { "status": "paid", … } }

POST /webhooks/payment
{ "event": "payment_succeeded", "order_id": 1, "transaction_id": "abc" }
→ 200 { "status": "payment_processed" }
```