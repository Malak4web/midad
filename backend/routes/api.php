<?php

use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Middleware\LogAdminActivity;
use Illuminate\Support\Facades\Route;

// Every plain throttle carries its own prefix: without one, all limits share a single
// per-visitor counter and browsing would exhaust the payment and coupon allowances.

// ── Storefront catalog (public)
Route::middleware('throttle:120,1,catalog')->group(function () {
    Route::get('/home', [CatalogController::class, 'home']);
    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/categories/{slug}', [CatalogController::class, 'category']);
    Route::get('/products', [CatalogController::class, 'products']);
    Route::get('/products/{slug}', [CatalogController::class, 'product']);
    Route::get('/search', [CatalogController::class, 'search'])->middleware('throttle:60,1,search');
    Route::get('/notes', [CatalogController::class, 'notes']);
    Route::get('/finder/questions', [CatalogController::class, 'finderQuestions']);
    Route::post('/finder/result', [CatalogController::class, 'finderResult'])->middleware('throttle:30,1,finder');
    Route::get('/governorates', [OrderController::class, 'governorates']);
});

// ── Auth (Sanctum SPA cookies)
Route::prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->middleware('throttle:120,1,me');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'throttle:20,1,logout']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth-mail');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed:relative', 'throttle:6,1,verify'])->name('verification.verify');
    Route::post('/verify-email/resend', [AuthController::class, 'resendVerification'])->middleware(['auth:sanctum', 'throttle:auth-mail']);
    Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware(['auth:sanctum', 'throttle:20,1,profile']);
});

// ── Cart (guest cookie or account)
Route::prefix('cart')->middleware('throttle:120,1,cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::post('/discovery-box', [CartController::class, 'addDiscoveryBox']);
    Route::patch('/items/{item}', [CartController::class, 'updateItem'])->whereNumber('item');
    Route::delete('/items/{item}', [CartController::class, 'removeItem'])->whereNumber('item');
    Route::post('/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:10,1,coupon');
    Route::delete('/coupon', [CartController::class, 'removeCoupon']);
    Route::put('/gift', [CartController::class, 'gift']);
});

// ── Checkout, orders, payments
Route::post('/checkout', [OrderController::class, 'checkout'])->middleware('throttle:checkout');
Route::get('/orders/{number}', [OrderController::class, 'show'])->middleware('throttle:30,1,orders');
Route::post('/orders/{number}/pay', [OrderController::class, 'retryPayment'])->middleware('throttle:10,1,pay');
Route::post('/payments/paymob/webhook', [OrderController::class, 'paymobWebhook'])->middleware('throttle:120,1,webhook');
Route::get('/payments/sandbox/{reference}', [OrderController::class, 'sandbox'])->middleware('throttle:30,1,sandbox-view');
Route::post('/payments/sandbox/{reference}', [OrderController::class, 'sandboxComplete'])->middleware('throttle:20,1,sandbox');

// ── Account
Route::prefix('account')->middleware(['auth:sanctum', 'throttle:60,1,account'])->group(function () {
    Route::get('/orders', [AccountController::class, 'orders']);
    Route::get('/addresses', [AccountController::class, 'addresses']);
    Route::post('/addresses', [AccountController::class, 'storeAddress']);
    Route::put('/addresses/{address}', [AccountController::class, 'updateAddress']);
    Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress']);
});

// ── Admin (BE-46 … BE-55). Staff run orders and stock; admins also own catalog, money and people.
Route::prefix('admin')->middleware(['auth:sanctum', 'can:staff', LogAdminActivity::class, 'throttle:300,1,admin'])->group(function () {
    Route::get('/dashboard', [ReportController::class, 'dashboard']);

    Route::get('/orders', [App\Http\Controllers\Admin\OrderController::class, 'index']);
    Route::get('/orders/{order:number}', [App\Http\Controllers\Admin\OrderController::class, 'show']);
    Route::post('/orders/{order:number}/status', [App\Http\Controllers\Admin\OrderController::class, 'transition']);
    Route::put('/orders/{order:number}/note', [App\Http\Controllers\Admin\OrderController::class, 'note']);
    Route::get('/orders/{order:number}/waybill', [App\Http\Controllers\Admin\OrderController::class, 'waybill']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/stock/low', [ProductController::class, 'lowStock']);
    Route::post('/variants/{variant}/stock', [ProductController::class, 'adjustStock']);
    Route::get('/variants/{variant}/movements', [ProductController::class, 'movements']);

    Route::middleware('can:admin')->group(function () {
        Route::post('/orders/{order:number}/refund', [App\Http\Controllers\Admin\OrderController::class, 'refund']);

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/images', [ProductController::class, 'uploadImage']);
        Route::delete('/products/{product}/images/{image}', [ProductController::class, 'deleteImage']);
        Route::put('/products/{product}/images/order', [ProductController::class, 'sortImages']);

        Route::get('/categories', [StoreController::class, 'categories']);
        Route::put('/categories/{category}', [StoreController::class, 'updateCategory']);
        Route::post('/spotlights', [StoreController::class, 'saveSpotlight']);
        Route::put('/spotlights/{spotlight}', [StoreController::class, 'saveSpotlight']);
        Route::delete('/spotlights/{spotlight}', [StoreController::class, 'deleteSpotlight']);
        Route::get('/governorates', [StoreController::class, 'governorates']);
        Route::put('/governorates', [StoreController::class, 'updateGovernorates']);
        Route::get('/settings', [StoreController::class, 'settings']);
        Route::put('/settings', [StoreController::class, 'updateSettings']);
        Route::get('/finder', [StoreController::class, 'finder']);
        Route::put('/finder/answers/{answer}', [StoreController::class, 'updateFinderAnswer']);

        Route::get('/coupons', [CouponController::class, 'index']);
        Route::post('/coupons', [CouponController::class, 'store']);
        Route::put('/coupons/{coupon}', [CouponController::class, 'update']);
        Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{user}', [CustomerController::class, 'show']);

        Route::get('/reports', [ReportController::class, 'report']);
        Route::get('/reports/orders.csv', [ReportController::class, 'exportOrders']);
    });
});
