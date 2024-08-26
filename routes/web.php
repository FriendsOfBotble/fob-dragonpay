<?php

use FriendsOfBotble\Dragonpay\Http\Controllers\DragonpayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['core'])->prefix('payment/dragonpay')->name('payment.dragonpay.')->group(function () {
    Route::post('postback', [DragonpayController::class, 'postback'])->name('postback');
    Route::get('callback', [DragonpayController::class, 'callback'])->name('callback');
});
