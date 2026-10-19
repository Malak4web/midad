<?php

use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// BE-57
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// BE-56: every storefront URL gets the SPA shell with its own meta.
// API, Sanctum and storage paths keep their own 404s.
Route::get('/{path?}', [SeoController::class, 'page'])
    ->where('path', '^(?!api/|api$|sanctum/|storage/|up$).*$')
    ->name('spa');
