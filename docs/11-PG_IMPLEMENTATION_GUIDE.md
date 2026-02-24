# Midtrans Payment Gateway Implementation Guide

> Reference implementation from the **baca** project (Laravel 12 + Midtrans Snap).
> Use this as a blueprint to implement the same payment flow in another Laravel project.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Dependencies](#2-dependencies)
3. [Environment & Configuration](#3-environment--configuration)
4. [Database Schema](#4-database-schema)
5. [Purchase Model](#5-purchase-model)
6. [MidtransService](#6-midtransservice)
7. [Purchase Controller (Buy Flow)](#7-purchase-controller-buy-flow)
8. [Webhook Controller](#8-webhook-controller)
9. [Routes](#9-routes)
10. [Frontend (Snap Integration)](#10-frontend-snap-integration)
11. [Access Control Middleware](#11-access-control-middleware)
12. [Security Considerations](#12-security-considerations)
13. [Payment Flow Diagram](#13-payment-flow-diagram)

---

## 1. Architecture Overview

The payment uses **Midtrans Snap** (popup-based checkout). The flow:

1. User clicks "Buy" → AJAX POST to backend
2. Backend creates a `Purchase` record (status: `pending`) and requests a Snap token from Midtrans API
3. Backend returns the Snap token to frontend
4. Frontend opens the Snap popup via `snap.pay(token)`
5. User completes payment inside the popup
6. Midtrans sends a **webhook** (server-to-server) to confirm payment
7. Webhook controller verifies signature + API status, then updates `Purchase` to `paid`

---

## 2. Dependencies

```bash
composer require midtrans/midtrans-php "^2.6"
```

This is the only payment-related package needed. It provides `Midtrans\Config`, `Midtrans\Snap`, and `Midtrans\Transaction` classes.

---

## 3. Environment & Configuration

### .env variables

```env
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY_SANDBOX=SB-Mid-server-xxxxx
MIDTRANS_CLIENT_KEY_SANDBOX=SB-Mid-client-xxxxx
MIDTRANS_SERVER_KEY_PRODUCTION=
MIDTRANS_CLIENT_KEY_PRODUCTION=
```

### config/services.php

Add this entry to the services config array:

```php
'midtrans' => [
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'server_key_sandbox' => env('MIDTRANS_SERVER_KEY_SANDBOX'),
    'client_key_sandbox' => env('MIDTRANS_CLIENT_KEY_SANDBOX'),
    'server_key_production' => env('MIDTRANS_SERVER_KEY_PRODUCTION'),
    'client_key_production' => env('MIDTRANS_CLIENT_KEY_PRODUCTION'),
],
```

---

## 4. Database Schema

### Migration: purchases table with payment fields

The key columns needed for Midtrans integration on your purchasable entity:

```php
Schema::table('purchases', function (Blueprint $table) {
    // Core purchase fields (adapt to your domain)
    $table->foreignId('user_id')->constrained();
    $table->foreignId('book_id')->constrained(); // your purchasable item
    $table->integer('price');

    // Midtrans payment fields
    $table->string('transaction_id')->nullable();       // Midtrans transaction ID
    $table->enum('payment_status', [
        'pending', 'paid', 'failed', 'expired', 'cancelled'
    ])->default('pending');
    $table->string('snap_token')->nullable();            // Snap token for reuse
    $table->string('midtrans_order_id')->nullable();     // Unique order ID sent to Midtrans
    $table->timestamp('paid_at')->nullable();            // When payment completed
    $table->timestamp('purchased_at')->nullable();       // Alias / legacy
    $table->timestamps();

    // Prevent duplicate purchases
    $table->unique(['user_id', 'book_id']);
});
```

---

## 5. Purchase Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Purchase extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'book_id',
        'price',
        'purchased_at',
        'transaction_id',
        'payment_status',
        'snap_token',
        'midtrans_order_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'paid_at'      => 'datetime',
        ];
    }

    // Relationships (adapt to your domain)
    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function book(): BelongsTo  { return $this->belongsTo(Book::class); }

    // Scopes
    public function scopePaid(Builder $query): Builder    { return $query->where('payment_status', self::STATUS_PAID); }
    public function scopePending(Builder $query): Builder  { return $query->where('payment_status', self::STATUS_PENDING); }

    // Status helpers
    public function isPaid(): bool    { return $this->payment_status === self::STATUS_PAID; }
    public function isPending(): bool { return $this->payment_status === self::STATUS_PENDING; }
    public function isFailed(): bool  { return in_array($this->payment_status, [self::STATUS_FAILED, self::STATUS_EXPIRED, self::STATUS_CANCELLED]); }
}
```

---

## 6. MidtransService

A service wrapper around the Midtrans SDK. Place at `app/Services/MidtransService.php`.

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        $isProduction = config('services.midtrans.is_production', false);
        Config::$isProduction = $isProduction;
        Config::$serverKey = $isProduction
            ? config('services.midtrans.server_key_production')
            : config('services.midtrans.server_key_sandbox');
    }

    /**
     * Create a Snap transaction token.
     *
     * @param array $params  Must contain: transaction_details, customer_details, item_details
     * @return array{token: string, redirect_url: string}|null
     */
    public function createSnapTransaction(array $params): ?array
    {
        try {
            if (empty(Config::$serverKey)) {
                Log::error('Midtrans server key is not configured');
                return null;
            }

            $snapToken = Snap::getSnapToken($params);

            if (empty($snapToken)) {
                Log::error('Midtrans Snap token is empty', ['params' => $params]);
                return null;
            }

            return [
                'token'        => $snapToken,
                'redirect_url' => $this->getRedirectUrl($snapToken),
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans Snap transaction creation failed', [
                'error'  => $e->getMessage(),
                'params' => $params,
            ]);
            return null;
        }
    }

    /**
     * Verify transaction status with Midtrans API (used by webhook for double-check).
     */
    public function verifyTransaction(string $orderId): ?array
    {
        try {
            return \Midtrans\Transaction::status($orderId);
        } catch (\Exception $e) {
            Log::error('Midtrans transaction verification failed', [
                'error'    => $e->getMessage(),
                'order_id' => $orderId,
            ]);
            return null;
        }
    }

    /**
     * Get client key for frontend Snap script.
     */
    public function getClientKey(): string
    {
        $isProduction = config('services.midtrans.is_production', false);
        return $isProduction
            ? config('services.midtrans.client_key_production')
            : config('services.midtrans.client_key_sandbox');
    }

    /**
     * Get Snap redirect URL (fallback for non-popup flow).
     */
    public function getRedirectUrl(string $snapToken): string
    {
        $baseUrl = config('services.midtrans.is_production', false)
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
        return "{$baseUrl}/snap/v2/vtweb/{$snapToken}";
    }

    /**
     * Verify webhook signature: SHA512(order_id + status_code + gross_amount + server_key)
     * Uses timing-safe comparison to prevent timing attacks.
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        if (empty(Config::$serverKey)) {
            Log::error('Midtrans server key is not configured for signature verification');
            return false;
        }

        // Midtrans sends gross_amount as "150000.00" — strip the decimal
        $grossAmountClean = str_replace('.', '', $grossAmount);
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmountClean . Config::$serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }
}
```

---

## 7. Purchase Controller (Buy Flow)

Key logic for initiating a purchase. Adapt the item/model references to your domain.

```php
public function buy(Book $book, MidtransService $midtransService)
{
    $user = Auth::user();

    // 1. Check for existing paid purchase
    $existingPaid = Purchase::where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->paid()
        ->first();

    if ($existingPaid) {
        return response()->json([
            'success' => false,
            'message' => 'Already purchased.',
        ], 400);
    }

    // 2. Calculate effective price
    $effectivePrice = ($book->discounted_price && $book->discounted_price > 0)
        ? $book->discounted_price
        : $book->price;

    // 3. Retry loop for deadlock handling (max 3 attempts)
    $maxRetries = 3;
    $attempt = 0;
    $purchase = null;

    while ($attempt < $maxRetries && !$purchase) {
        try {
            DB::beginTransaction();

            // Atomic get-or-create (handles race conditions)
            $purchase = Purchase::firstOrCreate(
                ['user_id' => $user->id, 'book_id' => $book->id],
                ['price' => $effectivePrice, 'payment_status' => Purchase::STATUS_PENDING]
            );

            if ($purchase->isPaid()) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Already purchased.'], 400);
            }

            // Reuse existing pending Snap token if available
            if ($purchase->isPending() && $purchase->snap_token && $purchase->midtrans_order_id) {
                DB::commit();
                return response()->json([
                    'success'    => true,
                    'token'      => $purchase->snap_token,
                    'client_key' => $midtransService->getClientKey(),
                ]);
            }

            // Clear old transaction data for failed/expired/cancelled
            if ($purchase->payment_status !== Purchase::STATUS_PENDING) {
                $purchase->update([
                    'payment_status'   => Purchase::STATUS_PENDING,
                    'midtrans_order_id' => null,
                    'snap_token'        => null,
                    'transaction_id'    => null,
                ]);
            }

            // Generate unique order ID: PREFIX-{purchaseId}-{timestamp}-{microseconds}
            $orderId = 'BOOK-' . $purchase->id . '-' . time() . '-' . substr(microtime(true) * 10000, -6);

            // Build Snap transaction params
            $params = [
                'transaction_details' => [
                    'order_id'     => $orderId,
                    'gross_amount' => $effectivePrice,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email'      => $user->email,
                ],
                'item_details' => [[
                    'id'       => $book->id,
                    'price'    => $effectivePrice,
                    'quantity'  => 1,
                    'name'     => $book->title,
                ]],
            ];

            $snapResponse = $midtransService->createSnapTransaction($params);

            if (!$snapResponse || !isset($snapResponse['token'])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Payment initialization failed.'], 500);
            }

            $purchase->update([
                'snap_token'        => $snapResponse['token'],
                'midtrans_order_id' => $orderId,
            ]);

            DB::commit();

            return response()->json([
                'success'    => true,
                'token'      => $snapResponse['token'],
                'client_key' => $midtransService->getClientKey(),
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            // Deadlock → retry with exponential backoff
            if ($e->getCode() === '40001' || str_contains($e->getMessage(), 'Deadlock')) {
                $attempt++;
                if ($attempt >= $maxRetries) throw $e;
                usleep(100000 * $attempt); // 100ms, 200ms, 300ms
                continue;
            }

            // Duplicate entry → fetch existing record and continue
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $purchase = Purchase::where('user_id', $user->id)->where('book_id', $book->id)->first();
                if ($purchase?->isPaid()) {
                    return response()->json(['success' => false, 'message' => 'Already purchased.'], 400);
                }
                continue;
            }

            throw $e;
        }
    }
}
```

**Key patterns:**
- `firstOrCreate` for atomic upsert
- Snap token reuse for pending purchases (avoids duplicate Midtrans API calls)
- Deadlock retry with exponential backoff
- Unique order ID: `PREFIX-{id}-{timestamp}-{microseconds}`

---

## 8. Webhook Controller

Handles server-to-server notifications from Midtrans. Place at `app/Http/Controllers/MidtransWebhookController.php`.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(protected MidtransService $midtransService) {}

    public function handle(Request $request)
    {
        try {
            $notification = $request->all();
            Log::info('Midtrans webhook received', ['notification' => $notification]);

            $orderId           = $notification['order_id'] ?? null;
            $transactionStatus = $notification['transaction_status'] ?? null;
            $fraudStatus       = $notification['fraud_status'] ?? null;
            $transactionId     = $notification['transaction_id'] ?? null;
            $statusCode        = $notification['status_code'] ?? null;
            $grossAmount       = $notification['gross_amount'] ?? null;
            $signatureKey      = $notification['signature_key'] ?? null;

            // Validate required fields
            if (!$orderId || !$transactionStatus) {
                return response()->json(['status' => 'error', 'message' => 'Invalid notification'], 400);
            }

            // Verify webhook signature
            if ($signatureKey && $statusCode && $grossAmount) {
                if (!$this->midtransService->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                    Log::warning('Invalid webhook signature', ['order_id' => $orderId, 'ip' => $request->ip()]);
                    return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
                }
            }

            // Find purchase
            $purchase = Purchase::where('midtrans_order_id', $orderId)->first();
            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Purchase not found'], 404);
            }

            // Idempotency: ignore duplicate settlement
            if ($purchase->isPaid() && $transactionStatus === 'settlement') {
                return response()->json(['status' => 'ok', 'message' => 'Already processed']);
            }

            // Double-check with Midtrans API (don't blindly trust the webhook payload)
            $verified = $this->midtransService->verifyTransaction($orderId);
            if ($verified && isset($verified['transaction_status'])) {
                if ($verified['transaction_status'] !== $transactionStatus) {
                    Log::warning('Status mismatch', [
                        'webhook' => $transactionStatus,
                        'api'     => $verified['transaction_status'],
                    ]);
                    $transactionStatus = $verified['transaction_status'];
                    $fraudStatus = $verified['fraud_status'] ?? $fraudStatus;
                }
            }

            // Map status and update
            DB::beginTransaction();

            $paymentStatus = $this->mapTransactionStatus($transactionStatus, $fraudStatus);
            $updateData = [
                'payment_status' => $paymentStatus,
                'transaction_id' => $transactionId,
            ];
            if ($paymentStatus === Purchase::STATUS_PAID) {
                $updateData['paid_at'] = now();
                $updateData['purchased_at'] = now();
            }

            $purchase->update($updateData);
            DB::commit();

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook processing failed', [
                'error'        => $e->getMessage(),
                'notification' => $request->all(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Processing failed'], 500);
        }
    }

    /**
     * Map Midtrans statuses to internal payment statuses.
     */
    private function mapTransactionStatus(string $transactionStatus, ?string $fraudStatus): string
    {
        // Fraud status takes priority
        if ($fraudStatus === 'challenge') return Purchase::STATUS_PENDING;
        if ($fraudStatus === 'deny')      return Purchase::STATUS_FAILED;

        return match ($transactionStatus) {
            'settlement' => Purchase::STATUS_PAID,
            'pending'    => Purchase::STATUS_PENDING,
            'deny'       => Purchase::STATUS_FAILED,
            'cancel'     => Purchase::STATUS_CANCELLED,
            'expire'     => Purchase::STATUS_EXPIRED,
            'refund'     => Purchase::STATUS_FAILED,
            default      => Purchase::STATUS_PENDING,
        };
    }
}
```

---

## 9. Routes

```php
use App\Http\Controllers\BookController;
use App\Http\Controllers\MidtransWebhookController;

// Webhook — public, rate-limited, no CSRF
Route::post('/webhooks/midtrans', [MidtransWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.midtrans');

// Purchase routes — authenticated
Route::middleware('auth')->group(function () {
    Route::post('/books/{book}/buy', [BookController::class, 'buy'])->name('books.buy');
    Route::get('/my-books', [BookController::class, 'purchases'])->name('purchases.index');
});
```

**Important:** The webhook route must be excluded from CSRF verification. Add it to the `$except` array in your CSRF middleware, or if using `api` middleware group it's already excluded.

---

## 10. Frontend (Snap Integration)

### Load the Snap script (in your layout)

```blade
@php
    $isProduction = config('services.midtrans.is_production', false);
    $clientKey = $isProduction
        ? config('services.midtrans.client_key_production')
        : config('services.midtrans.client_key_sandbox');
    $snapUrl = $isProduction
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
@endphp

@if($clientKey)
    <script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
@endif
```

### Buy button JavaScript

```javascript
// On buy button click:
fetch('/books/' + bookId + '/buy', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    },
})
.then(response => response.json())
.then(data => {
    if (data.success && data.token) {
        // Poll for snap.pay availability (script may still be loading)
        let attempts = 0;
        const interval = setInterval(function () {
            attempts++;
            if (typeof snap !== 'undefined' && snap.pay) {
                clearInterval(interval);
                snap.pay(data.token, {
                    onSuccess: function (result) {
                        // Payment succeeded — webhook handles DB update
                        showToast('Payment successful!', 'success');
                        setTimeout(() => window.location.reload(), 2000);
                    },
                    onPending: function (result) {
                        // Payment pending (bank transfer, etc.)
                        showToast('Payment pending. Complete your payment.', 'info');
                        setTimeout(() => window.location.reload(), 2000);
                    },
                    onError: function (result) {
                        showToast('Payment failed. Please try again.', 'error');
                    },
                    onClose: function () {
                        // User closed popup without finishing
                        // Re-enable the buy button
                    },
                });
            } else if (attempts >= 10) {
                clearInterval(interval);
                showToast('Payment service unavailable. Please refresh.', 'error');
            }
        }, 500);
    }
});
```

**Key points:**
- The backend returns `{ success, token, client_key }` as JSON
- `snap.pay(token, callbacks)` opens the Midtrans payment popup
- The `onSuccess`/`onPending` callbacks are **client-side only** — the webhook is the authoritative source of truth for payment status
- The interval polling handles the case where the Snap script hasn't loaded yet

---

## 11. Access Control Middleware

Optional middleware to protect content that requires a paid purchase:

```php
<?php

namespace App\Http\Middleware;

use App\Models\Book;
use App\Models\Purchase;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBookPurchase
{
    public function handle(Request $request, Closure $next)
    {
        $bookSlug = $this->extractBookSlug($request->path());

        if (!$bookSlug) {
            return $next($request); // Not a book route
        }

        $book = Book::where('slug', $bookSlug)->first();
        if (!$book) {
            return $next($request);
        }

        // Free books don't need purchase check
        if ($book->price == 0) {
            return $next($request);
        }

        // Check if user has a paid purchase
        if (Auth::check()) {
            $hasPurchase = Purchase::where('user_id', Auth::id())
                ->where('book_id', $book->id)
                ->paid()
                ->exists();

            if ($hasPurchase) {
                return $next($request);
            }
        }

        // Redirect to preview or purchase page
        return redirect('/preview/' . $bookSlug);
    }
}
```

---

## 12. Security Considerations

1. **Webhook signature verification** — SHA512(order_id + status_code + gross_amount + server_key) with timing-safe `hash_equals()`
2. **API double-check** — After receiving a webhook, verify the status with `\Midtrans\Transaction::status()` to prevent spoofed webhooks
3. **Idempotency** — Skip duplicate `settlement` notifications for already-paid purchases
4. **Rate limiting** — Webhook endpoint uses `throttle:60,1`
5. **CSRF exclusion** — Webhook must be excluded from CSRF (it comes from Midtrans servers)
6. **Ownership check** — Always verify `purchase.user_id === auth.id` before exposing payment data
7. **Database transactions** — Wrap status updates in DB transactions to prevent partial writes

---

## 13. Payment Flow Diagram

```
┌──────────┐     POST /books/{id}/buy      ┌──────────────┐
│  Browser  │ ─────────────────────────────→│   Laravel     │
│ (Frontend)│                               │  Controller   │
│           │←── { token, client_key } ─────│               │
│           │                               │  Creates      │
│           │                               │  Purchase     │
│           │   snap.pay(token)             │  (pending)    │
│           │ ──────────────→ ┌───────────┐ │               │
│           │                 │ Midtrans  │ │  Calls Snap   │
│           │                 │   Snap    │ │  API for      │
│           │←── payment ────→│  Popup    │ │  token        │
│           │    callbacks    └───────────┘ └──────┬────────┘
│           │                                      │
│           │                                      │
│           │                 ┌───────────┐        │
│           │                 │ Midtrans  │        │
│           │                 │  Server   │────────┘
│           │                 │           │  Snap::getSnapToken()
│           │                 │           │
│           │                 │           │  POST /webhooks/midtrans
│           │                 │           │──────→ ┌──────────────┐
│           │                 └───────────┘        │   Webhook    │
│           │                                      │  Controller  │
│           │                                      │              │
│           │                                      │ 1. Verify    │
│           │                                      │    signature │
│           │                                      │ 2. API check │
│           │                                      │ 3. Update    │
│           │                                      │    Purchase  │
│           │                                      │    → paid    │
└──────────┘                                       └──────────────┘
```

---

## Midtrans Dashboard Setup

1. Get API keys from https://dashboard.midtrans.com (Settings → Access Keys)
2. Set webhook/notification URL to `{YOUR_APP_URL}/webhooks/midtrans`
3. Enable the payment methods you want (credit card, bank transfer, e-wallet, etc.)
4. For testing, use sandbox mode and test credentials from Midtrans docs

---

## Adaptation Checklist

When implementing in your project:

- [ ] `composer require midtrans/midtrans-php "^2.6"`
- [ ] Add env variables and `config/services.php` entry
- [ ] Create migration with payment fields on your purchase/order table
- [ ] Create `Purchase` model (or adapt your existing order model) with status constants and scopes
- [ ] Create `MidtransService` — copy as-is, it's domain-agnostic
- [ ] Create `MidtransWebhookController` — copy as-is, just update the model reference
- [ ] Add buy logic in your controller — adapt item/price references to your domain
- [ ] Register routes (webhook excluded from CSRF, buy route behind auth)
- [ ] Load Snap script in layout
- [ ] Add buy button JS with `snap.pay()` callbacks
- [ ] Configure webhook URL in Midtrans dashboard
- [ ] Test with sandbox credentials using Midtrans simulator
