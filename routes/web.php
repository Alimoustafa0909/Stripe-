<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Stripe\PaymentMethodController;
use App\Http\Controllers\Stripe\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});
Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->middleware('auth');

// routes/web.php

Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
Route::post('/payment-methods/set-default', [PaymentMethodController::class, 'setDefault'])->name('payment-methods.set-default');
Route::get('/payment-methods/create', [PaymentMethodController::class, 'create'])->name('payment-methods.create');
Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');

Route::get('/subscription', [SubscriptionController::class, 'showSubscriptionForm'])->name('subscription');
Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);


require __DIR__.'/auth.php';
