<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SSLCommerz Callbacks
Route::post('/ssl/success', [App\Http\Controllers\Api\PaymentController::class, 'sslSuccess'])->name('ssl.success');
Route::post('/ssl/fail', [App\Http\Controllers\Api\PaymentController::class, 'sslFail'])->name('ssl.fail');
Route::post('/ssl/cancel', [App\Http\Controllers\Api\PaymentController::class, 'sslCancel'])->name('ssl.cancel');
Route::post('/ssl/ipn', [App\Http\Controllers\Api\PaymentController::class, 'sslIpn'])->name('ssl.ipn');
