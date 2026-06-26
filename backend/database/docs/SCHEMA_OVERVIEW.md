# Ethiopian Farmers Market Platform — Database Schema Overview

Companion reference for the migration files in `/migrations`. Built for **Laravel 13 + MySQL**, matching the architecture described in the product documentation (Vue 3 client, Laravel application layer, MySQL data layer, Chapa integration layer).

## Design principles carried over from the doc

- **Facilitated marketplace, not owner of goods/logistics** — there is no inventory-custody or delivery/logistics table. The schema only ever records *listings*, *orders*, *fulfillment status*, and *payment status* — never physical possession.
- **Capability-based identity** — a `users` row is just an identity. Farmer/buyer access is a separately granted capability, and admin is a separate privilege again (`is_admin` flag), never part of the capability system.
- **Webhook-only payment confirmation** — `payments.status` can only move `pending → confirmed` from the signed Chapa webhook handler. This is called out explicitly in the `payments` migration comments because it's a rule no column type can enforce by itself — it has to be enforced by *who is allowed to call the update*, i.e. in your service layer / route authorization, not the schema.
- **Per-farmer fulfillment** — one order can span several farmers, so `order_fulfillments` (not `orders`) is where accept/reject/complete actually happens.

## Tables

| Table | Purpose |
|---|---|
| `users` | Core identity. Phone is the login credential; `is_admin` is the separate admin privilege; `account_status` supports admin account oversight. |
| `otp_verifications` | Phone OTP codes for registration/login. Keyed by phone, not user, since OTP precedes account creation. |
| `capability_applications` | Full history of farmer/buyer applications and admin review decisions (including past rejections). |
| `user_capabilities` | The *current* granted capability state, one row per user per capability type — what your auth checks actually query. |
| `categories` | Lightweight produce categorization. *Not explicitly named in the doc*, but added because "browse and search listings" needs something to filter/search by. |
| `listings` | Farmer produce listings. `quantity_available` / `quantity_reserved` are split so checkout can hold stock without losing track of totals. |
| `listing_price_history` | Append-only price log — satisfies the explicit "price history" requirement. |
| `cart_items` | Buyer's pre-checkout cart, can hold items from multiple farmers at once. |
| `orders` | The buyer-facing order. Aggregate `status` is derived from payment + all child fulfillments. |
| `order_fulfillments` | **The core trust mechanic.** One row per farmer involved in an order; accept/reject/complete happens here, scoped so a farmer only ever sees their own rows. |
| `order_items` | Line items. `unit_price` is an immutable snapshot taken at order time, independent of later price changes. |
| `payments` | One row per order's Chapa transaction. `status` is webhook-only past `pending`. |
| `payment_webhook_events` | Raw log of every inbound webhook call, used for idempotency and audit before any state change is applied. |
| `payment_exceptions` | Admin's *only* touchpoint related to payments — disputes/mismatches/refund requests. Resolving one never writes `payments.status` directly. |
| `payouts` | Farmer payout history per settled fulfillment. |
| `audit_logs` | Polymorphic audit trail for marketplace/payment actions (listings, fulfillments, payments, capability decisions, etc.). |

## Relationships at a glance

```
users ─┬─< capability_applications >─┐
       ├─< user_capabilities          (reviewed_by → users)
       ├─< listings (as farmer) ─< listing_price_history
       ├─< cart_items (as buyer)
       ├─< orders (as buyer)
       ├─< order_fulfillments (as farmer)
       └─< payouts (as farmer)

orders ─┬─< order_fulfillments ─< order_items >─ listings
        └── payments ─┬─< payment_webhook_events
                       └─< payment_exceptions >─ orders
```

## Business rules and where they live

| Rule (from the doc) | Where it's enforced |
|---|---|
| Self-dealing is blocked (can't order your own listing) | Application layer — validate `listing.farmer_id != $buyer->id` in the checkout service. Can't be a simple `CHECK` constraint since it spans two tables; add a service-level guard + test. |
| Concurrency-safe stock reservation | `listings.quantity_available` / `quantity_reserved` + a DB transaction using `lockForUpdate()` during checkout: lock the row, validate stock, decrement available, increment reserved, then create the order atomically. |
| Payment confirmed only via webhook | `payments.status` enum + **no route/controller other than the webhook handler is permitted to write `confirmed`**. Enforce with policy/authorization, not just convention. |
| Idempotent webhook processing | `payment_webhook_events` logs every inbound call first; check for an existing `processed` row for the same `chapa_tx_ref` + `event_type` before acting (note the migration comment about `chapa_event_id` possibly being null). |
| Admin never manually confirms payment | No `payments` route accepts admin-originated status writes. `payment_exceptions` is a separate dispute/audit table that never touches `payments.status`. |
| Farmer sees only their own fulfillments | Query `order_fulfillments` filtered by `farmer_id`, scoped via policy. |
| Sensitive payment data never stored | `payments.gateway_metadata` is documented as non-sensitive metadata only — no card/wallet fields exist anywhere in the schema. |

## Not included by design

- No `password` requirement (nullable) — OTP is the primary auth method per the doc. Pair this with **Laravel Sanctum** for the Vue SPA session/token layer (Sanctum ships its own `personal_access_tokens` migration — no need to hand-build it).
- No delivery/logistics tables — outside the core model per the doc.
- No stored card/wallet data — Chapa's hosted checkout means the app never touches it.

## Natural next steps

- Eloquent models with relationships, casts, and scopes (e.g. `Listing::available()`, `OrderFulfillment::forFarmer()`).
- A `ChapaWebhookController` + `PaymentWebhookService` that owns the only code path allowed to write `payments.status = confirmed`.
- Form Request validation for the self-dealing check and stock checks at checkout.
- Seeders for categories and an admin user.

Happy to generate any of these next if useful.
