<?php

use Illuminate\Support\Facades\Route;

Auth::routes();
Route::group(['middleware' => ['auth']], function () {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth');
    Route::redirect('/home','/');
    Route::post('/user/account-information-store', [App\Http\Controllers\UserController::class, 'accountInformationStore'])->name('user.account-information-store');
    Route::post('/order/getByCargoTrackId', [App\Http\Controllers\OrderController::class, 'getByCargoTrackId'])->name('order.getByCargoTrackId');
    Route::post('/order/getOrders', [App\Http\Controllers\OrderController::class, 'getOrders'])->name('order.getOrders');
    Route::post('/store/update-default', [App\Http\Controllers\StoreController::class, 'updateDefault'])->name('store.updateDefault');
    Route::post('/order/{order}/video/store', [App\Http\Controllers\OrderController::class, 'storeVideo'])->name('order.storeVideo');
});
