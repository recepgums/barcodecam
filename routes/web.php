<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/user/account-information-store', [App\Http\Controllers\UserController::class, 'accountInformationStore'])->name('user.account-information-store');
Route::post('/order/getByCargoTrackId', [App\Http\Controllers\OrderController::class, 'getByCargoTrackId'])->name('order.getByCargoTrackId');
Route::post('/order/getOrders', [App\Http\Controllers\OrderController::class, 'getOrders'])->name('order.getOrders');
