# PayHero M-Pesa checkout

The public bookstore checkout creates or reuses one order, reserves inventory, and immediately sends a PayHero M-Pesa STK prompt. The browser polls the local payment status endpoint; only a verified successful transaction confirms the order and commits reserved stock.

## Production configuration

Set these values in `backend/.env`:

```dotenv
APP_URL=https://globalministriesdailybread.org/backend
APP_ROUTE_PREFIX=backend
STOREFRONT_URL=https://globalministriesdailybread.org

PAYHERO_BASE_URL=https://backend.payhero.co.ke
PAYHERO_PAYMENTS_PATH=/api/v2/payments
PAYHERO_STATUS_BASE_URL=https://api.payhero.africa
PAYHERO_STATUS_PATH=/api/global/transaction-status
PAYHERO_AUTH_TOKEN=your-basic-authorization-token
PAYHERO_CHANNEL_ID=your-channel-id
PAYHERO_PROVIDER=m-pesa
PAYHERO_CALLBACK_URL=https://globalministriesdailybread.org/backend/api/payments/payhero/callback
```

Copy `PAYHERO_AUTH_TOKEN` from Pay Hero's API Keys page. Store the token value only; the client adds the `Basic` scheme to the Authorization header. A value copied with the `Basic ` prefix is also accepted and normalized. Do not commit live credentials. After changing environment values, run:

```bash
php artisan optimize:clear
php artisan config:cache
```

Configure the PayHero channel to use the same public HTTPS callback URL. The callback has no customer session dependency. Because PayHero does not document a callback signature, production callbacks are confirmed through PayHero's transaction-status API before an order is paid.

## Scheduler

Laravel schedules `payments:reconcile-payhero` every five minutes and `orders:release-expired` every minute. The server cron must run Laravel's scheduler:

```cron
* * * * * cd /path/to/project/backend && php artisan schedule:run >> /dev/null 2>&1
```

Reconciliation checks older pending attempts when a callback is late. A successful payment commits inventory once; duplicate callbacks and repeated reconciliation are idempotent. Definite initiation failures may be retried against the same order while its reservation remains active. An uncertain network outcome stays pending to prevent a duplicate charge.

## Operational checks

1. Place a low-value order using a Safaricom number and confirm the phone prompt appears.
2. Complete payment and confirm the order changes to `paid` / `confirmed` and the reservation changes to `committed`.
3. Confirm the admin payment row contains the external reference, PayHero reference, CheckoutRequestID, and M-Pesa receipt.
4. Cancel a prompt and confirm the same order can be retried without creating another reservation.
5. Run `php artisan payments:reconcile-payhero` and check that pending transactions are queried without exposing credentials in logs.

Historical Paystack rows remain in the payments table and can be filtered in the admin payment list. Paystack routes, credentials, hosted checkout, callback handling, and scheduled reconciliation are no longer active.
