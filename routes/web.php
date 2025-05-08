<?php

use Illuminate\Support\Facades\Route;

Auth::routes();
Route::group(['middleware' => ['auth']], function () {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);
    Route::view('video', 'video');
    Route::redirect('/home','/');
    Route::post('/user/account-information-store', [App\Http\Controllers\UserController::class, 'accountInformationStore'])->name('user.account-information-store');
    Route::post('/order/getByCargoTrackId', [App\Http\Controllers\OrderController::class, 'getByCargoTrackId'])->name('order.getByCargoTrackId');
    Route::post('/order/getOrders', [App\Http\Controllers\OrderController::class, 'getOrders'])->name('order.getOrders');
    Route::post('/store/update-default', [App\Http\Controllers\StoreController::class, 'updateDefault'])->name('store.updateDefault');
    Route::post('/order/{order}/video/store', [App\Http\Controllers\OrderController::class, 'storeVideo'])->name('order.storeVideo');
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::get('/shipments', [App\Http\Controllers\ShipmentController::class, 'index'])->name('shipments.index');
    Route::post('/shipments/rules/store', [App\Http\Controllers\ShipmentController::class, 'storeRule'])->name('shipments.rules.store');
    Route::get('/shipments/rules/{rule}/edit', [App\Http\Controllers\ShipmentController::class, 'editRule'])->name('shipments.rules.edit');
    Route::put('/shipments/rules/{rule}', [App\Http\Controllers\ShipmentController::class, 'updateRule'])->name('shipments.rules.update');
    Route::delete('/shipments/rules/{rule}', [App\Http\Controllers\ShipmentController::class, 'destroyRule'])->name('shipments.rules.destroy');
    Route::get('login-as/{id}',function ($id){
        auth()->loginUsingId($id);
    });
});
