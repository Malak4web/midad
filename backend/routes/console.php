<?php

use App\Models\Cart;
use Illuminate\Support\Facades\Schedule;

// Free stock held by online orders that were never paid (BE-36).
Schedule::command('orders:release-expired')->everyMinute()->withoutOverlapping();

// Drop guest carts nobody touched for 60 days.
Schedule::call(fn () => Cart::whereNull('user_id')->where('updated_at', '<', now()->subDays(60))->delete())->daily();
