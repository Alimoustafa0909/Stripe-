<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Stripe\PaymentMethodController;
use App\Http\Controllers\Stripe\SubscriptionController;
use App\Http\Middleware\CheckStandardSubscription;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::post('/cancel-subscription', [SubscriptionController::class, 'cancelSubscription'])->name('cancel_subscription');
    Route::get('/subscription', [SubscriptionController::class, 'showSubscriptionForm'])->name('subscription');
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->middleware('auth');
    Route::view('/standard', 'stripe.standard')->name('standard');
    Route::view('/premium', 'stripe.premium')->name('premium');
    Route::view('/elite', 'stripe.elite')->name('elite');

/* Start   Routes For= Payment-Method  Page*/
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods/set-default', [PaymentMethodController::class, 'setDefault'])->name('payment-methods.set-default');
    Route::get('/payment-methods/create', [PaymentMethodController::class, 'create'])->name('payment-methods.create');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
/*End Of Payment Method Routes*/
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
// routes/web.php


require __DIR__ . '/auth.php';
