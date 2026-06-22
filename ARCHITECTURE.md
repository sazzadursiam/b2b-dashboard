# Architecture

This document describes the design of the B2B Executive Dashboard backend — the layering,
the multi-tenant scoping model, the caching/stampede strategy, and the order lifecycle.

## Layering

Requests flow through thin controllers into a service layer that owns the domain logic.
HTTP concerns never leak into the domain, and the domain never reaches back into HTTP.

```
HTTP Request
  │
  ▼
Form Request            validation (StoreOrderRequest, UpdateOrderStatusRequest, …)
  │  → DTO              OrderData::fromRequest()
  ▼
Controller              orchestration only; no business rules
  │
  ▼
Service                 OrderService, OrderStateService, DashboardAnalyticsService,
  │                     IdempotencyService — pure domain, receive DTOs/models
  ▼
Model (Eloquent)        tenant-scoped via the BelongsToBusiness trait
  │
  ▼
API Resource            intentional output (OrderResource, UserResource, …)
```

Side effects (cache refresh, inventory, notifications, refunds) are dispatched as **events**
and **queued jobs**, so the request path stays fast and the side effects are retryable.

### Key directories

| Path | Responsibility |
|---|---|
| `app/Http/Requests` | Input validation (Form Requests) |
| `app/DataTransferObjects` | Immutable input carriers passed into services |
| `app/Services` | Domain logic |
| `app/Http/Resources` | Output shaping (JSON resources) |
| `app/Enums` | `OrderStatus` — owns the state machine |
| `app/Events` / `app/Listeners` | Domain events + queued reactions |
| `app/Jobs` | Background work (`RefreshAnalyticsCache`, `RefundPaymentJob`) |
| `app/Models/Concerns` / `app/Models/Scopes` | Multi-tenancy primitives |
| `app/Support/CurrentBusiness` | Request-scoped active-tenant holder |

### Why services and not repositories

The brief encourages "Service Classes, Repositories, and Actions." I used **services with
action-style methods** (`OrderService::place`, `OrderStateService::transition`) and deliberately
**did not add a Repository layer**:

- Eloquent already *is* the data-access abstraction (and the unit of work, via its query
  builder and transactions). A repository on top of it mostly re-exposes the same methods with
  more indirection — a classic over-abstraction in Laravel apps of this size.
- The genuinely valuable abstraction here is **tenant isolation**, and that is better expressed
  as a global scope + trait (cross-cutting, automatic) than as repository methods that each
  developer must remember to call.
- Services keep the domain logic in one testable place without coupling it to HTTP (they take
  DTOs/models, not `Request`) — which is the actual benefit people want from "repositories."

If the data layer later needed swapping (e.g. a read model backed by a search engine), the
service boundary is exactly where a repository interface would be introduced — so the seam
already exists without paying for it today.

## Multi-tenancy (the most important design decision)

Every business is a tenant. A user belongs to exactly one business. The hard requirement is
that **no query ever crosses a tenant boundary** and **`business_id` is never taken from user
input**. Rather than repeat `->where('business_id', …)` in every query (one forgotten clause =
a data leak), scoping is enforced centrally with three collaborating pieces:

```
SetCurrentBusiness (middleware)         CurrentBusiness (request singleton)
  reads $request->user()->business_id ───►  holds the active business id
                                                    ▲
                                                    │ resolved by
                              ┌─────────────────────┴─────────────────────┐
                              │                                            │
                     BusinessScope (global scope)            BelongsToBusiness (model trait)
        adds WHERE business_id = <current> to reads        auto-fills business_id on create
```

1. **`SetCurrentBusiness`** middleware runs after `auth:sanctum` on the `/api/v1` group and
   pushes the authenticated user's `business_id` into `CurrentBusiness`.
2. **`CurrentBusiness`** is a `scoped` singleton — one instance per request/job lifecycle.
3. **`BelongsToBusiness`** trait (on Order, OrderItem, Product, Inventory, Sale, OrderAudit,
   IdempotencyKey):
   - registers **`BusinessScope`**, a global scope that appends a table-qualified
     `WHERE business_id = <current>` to every read;
   - hooks `creating` to **auto-assign `business_id`** from `CurrentBusiness` when not set.

### Consequences

- Controllers/services write `Order::latest()->cursorPaginate()` — no manual tenant filter.
- Route-model binding (`PATCH /orders/{order}/status`) auto-scopes, so a foreign id resolves
  to **404**. An explicit `abort_if(... 404)` is kept as defense-in-depth.
- **When no business is set** — the public payment webhook, queue workers, console commands,
  the seeder — the scope is a no-op. This is intentional: the webhook must resolve an order by
  id without an authenticated tenant, and analytics aggregation runs in jobs. Those paths pass
  `business_id` explicitly where it matters.

> The heavy analytics aggregation uses the **query builder** (`DB::table(...)`) with an explicit
> `business_id` filter, by design — it deliberately bypasses Eloquent scopes for performance and
> predictable SQL, and is therefore unaffected by the global scope.

## Caching & stampede prevention

`GET /api/v1/analytics/dashboard` aggregates 30 days of orders (revenue, AOV, top products,
24h hourly velocity). The computation is expensive and read by thousands of concurrent users,
so it is aggressively cached with explicit stampede protection.

```
request ──► cache hit? ──yes──► return {data, generated_at, cached: true}
               │ no
               ▼
        Cache::lock("analytics_business_{id}", 10s)->block(5s)
               │
        ┌──────┴───────────────────────────────┐
        │ winner                                │ everyone else
        ▼                                       ▼
  recompute() once  ──► put(tags, 1h)     blocks until lock frees,
  return cached:false                     then reads the now-warm cache
                                          (never touches the database)
```

- **Tags** — `Cache::tags(['business_{id}'])` so a business's dashboard can be invalidated
  independently.
- **Lock** — `Cache::lock(key, 10)` with a 10s TTL prevents deadlock if the rebuilder crashes;
  `->block(5, …)` makes waiters park for up to 5s rather than hammering the DB.
- **Invalidation** — creating/updating a `Sale` dispatches `RefreshAnalyticsCache`, which is
  `ShouldBeUnique` per `business_id`: a burst of 50 sales collapses to a single rebuild.
- Requires a taggable, lock-capable store → **Redis** in app, **array** store in tests.

## Order lifecycle (event-driven)

```
POST /orders ──► OrderService::place() ──► Order(status: pending)

PATCH /orders/{id}/status  ┐
payment webhook            ├─► OrderStateService::transition($order, OrderStatus)
internal logic             ┘        │
                                    ├─ validate transition via OrderStatus::canTransitionTo()
                                    ├─ if → Processing: reserve inventory SYNCHRONOUSLY
                                    │        └─ fails ⇒ block transition + RefundPaymentJob ("REFUND INITIATED")
                                    ├─ write OrderAudit (from, to, user, business, metadata)
                                    └─ dispatch OrderStatusChanged
                                              │
                          ┌───────────────────┴────────────────────┐
                          ▼                                         ▼
              UpdateInventoryListener (queued)         LogOrderNotificationListener (queued)
              commit/release stock on shipped/canceled  log the notification
```

**State machine** lives in the `OrderStatus` enum:
`pending → paid → processing → shipped → delivered`, with `canceled` reachable from any
non-terminal state.

**Why inventory reservation is synchronous** while everything else is queued: requirement is
that insufficient stock must *prevent* the move to `processing` and trigger a refund. A queued
listener runs *after* the transition has already committed, so it cannot block it. The
reservation therefore happens inside the transaction; the queued `UpdateInventoryListener`
handles the non-blocking downstream stock bookkeeping (commit on `shipped`, release on
`canceled`).

## Idempotency (API and webhook)

Both retry paths are made idempotent by persisting a record of what was already processed —
the two cases differ only in *what* the key is and *what* gets replayed.

**API — order creation (client-supplied key, response replay).**
The client sends an `Idempotency-Key` header. `IdempotencyService::find()` looks it up
(tenant-scoped, unexpired) *before* any work. On a hit, the **stored response body and status
code are returned verbatim** — no second order is created. On a miss, the order is created and
the full response is persisted to `idempotency_keys` with a 24h TTL. A unique constraint on
`(business_id, key, endpoint)` is the backstop against two concurrent first-time requests.
Replaying the *response* (not just blocking the write) is what makes the retry transparent to
the caller.

**Webhook — payment events (provider-supplied key, no-op replay).**
Payment providers retry aggressively and deliver out of order, so the webhook keys on the
provider's `transaction_id`. Each id is recorded in `processed_webhooks` (unique column); a
repeat delivery short-circuits to `duplicate_ignored` with **zero side effects**. Separately,
out-of-order events are tolerated by *state*, not just by id: a `payment_succeeded` only acts
when the order is `pending` — otherwise it is logged and ignored rather than erroring or
double-charging. Together these give exactly-once *effect* on an at-least-once delivery channel.

## Resilience

- Inventory reservation failure ⇒ order stays `pending`, `RefundPaymentJob` logs
  `REFUND INITIATED` (compensation).
- `RefreshAnalyticsCache` takes its own `lock(..., 10)` before recomputing, so even concurrent
  jobs don't double-aggregate.
- Cursor pagination (`GET /orders`) avoids offset scans on large tables.
- Custom `analytics` rate limiter: 200 req/min keyed by `business_id`.

## Evolving this monolith into microservices

The code is already organised so the seams a microservice split would follow exist *today*,
inside one deployable. The migration would be incremental, not a rewrite.

**1. The boundaries are already drawn.** Each service + its events maps to a candidate service:

| Bounded context | Owns | Today |
|---|---|---|
| **Orders** | orders, order_items, state machine, audit | `OrderService`, `OrderStateService`, `OrderStatus` |
| **Analytics** | dashboard aggregation + cache | `DashboardAnalyticsService`, `RefreshAnalyticsCache` |
| **Inventory** | stock / reservations | `UpdateInventoryListener`, reservation logic |
| **Payments** | webhooks, refunds | `PaymentWebhookController`, `RefundPaymentJob` |
| **Notifications** | outbound messages | `LogOrderNotificationListener` |

**2. Events become the integration contract.** Internally we already publish
`OrderStatusChanged` and react with queued listeners. To split out a service, replace the
in-process event bus with a **broker** (Kafka / SQS / Rabbit): the Orders service emits
`OrderStatusChanged` to a topic; Inventory, Notifications, and Analytics consume it. The
listeners barely change — only their transport does. This is the classic strangler-fig path.

**3. Keep the asynchronous, idempotent, compensating patterns — they are what make distribution
safe.** They were chosen with this in mind:

- **Idempotency keys / `transaction_id` ledger** already give exactly-once *effect* over an
  at-least-once broker — mandatory once delivery is over the network.
- The synchronous reserve-then-refund flow is a **saga/compensation** in miniature; across
  services it becomes an explicit orchestrated saga (reserve inventory → on failure, emit
  `PaymentRefundRequested`).
- **Per-business cache + tags** mean Analytics can own its own cache/store independently.

**4. Data ownership.** Each service gets its own schema; cross-context reads move from SQL joins
to API calls or, better, **locally-maintained read models** kept current from the event stream
(Analytics already treats its data as a derived projection it rebuilds — a natural CQRS read
model).

**5. Cross-cutting concerns become platform concerns.** `CurrentBusiness` (tenant context)
graduates into a propagated request/trace header enforced at the gateway; Sanctum tokens become
gateway-validated JWTs; the rate limiter moves to the gateway/Redis tier.

**What I would *not* do:** split prematurely. The monolith with clean module boundaries is the
correct first stage — extract a service only when a real scaling, deployment-cadence, or team
boundary forces it (Analytics, being the heaviest and most independent, would be the first to
go).

## Testing notes

See the **Testing** section of `README.md` — in particular the warning that the test
connection must point at a dedicated, disposable database because `RefreshDatabase` runs
`migrate:fresh` (drops all tables).