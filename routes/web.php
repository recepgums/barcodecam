<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

Auth::routes();
Route::group(['middleware' => ['auth']], function () {
    Route::get('artisan/{command}', function ($command) {
        try {
            ini_set('max_execution_time', 300);

            if ($command === 'migrate-pretend') {
                Artisan::call('migrate', ['--pretend' => true, '--force' => true]); // --force ekledik
            }elseif($command === 'migrate'){
                Artisan::call('migrate', ['--force' => true]);
            } elseif($command === 'generate-zpl-barcode') {
                Artisan::call('generate:zpl-barcode');
            } else {
                Artisan::call($command);
            }
            $output = Artisan::output();

        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()]);
        }

        return response()->json(['output' => $output, 'message' => 'Command executed successfully']);
    });

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
    Route::get('/shipments/rules', [App\Http\Controllers\ShipmentController::class, 'rulesIndex'])->name('shipments.rules.index');
    Route::post('/shipments/rules/store', [App\Http\Controllers\ShipmentController::class, 'storeRule'])->name('shipments.rules.store');
    Route::get('/shipments/rules/{rule}/edit', [App\Http\Controllers\ShipmentController::class, 'editRule'])->name('shipments.rules.edit');
    Route::put('/shipments/rules/{rule}', [App\Http\Controllers\ShipmentController::class, 'updateRule'])->name('shipments.rules.update');
    Route::delete('/shipments/rules/{rule}', [App\Http\Controllers\ShipmentController::class, 'destroyRule'])->name('shipments.rules.destroy');
    Route::post('/shipments/rules/{rule}/execute', [App\Http\Controllers\ShipmentController::class, 'executeRule'])->name('shipments.rules.execute');
    Route::get('login-as/{id}',function ($id){
        auth()->loginUsingId($id);
    });
    Route::post('/shipments/{order}/single-update', [App\Http\Controllers\ShipmentController::class, 'singleUpdate'])->name('shipments.single-update');
    Route::post('/shipments/{order}/generate-zpl', [App\Http\Controllers\ShipmentController::class, 'generateZPL'])->name('shipments.generate-zpl');
    Route::post('/shipments/increment-print-count', [App\Http\Controllers\ShipmentController::class, 'incrementPrintCount'])->name('shipments.increment-print-count');
});
