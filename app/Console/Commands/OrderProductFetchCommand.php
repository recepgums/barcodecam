<?php

namespace App\Console\Commands;

use App\Helpers\TrendyolHelper;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProductFetchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:product-fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Order::with(['store', 'user'])->get()->each(function ($order) {
            try {
                DB::beginTransaction();
                $products = json_decode($order?->lines);

                foreach ($products as $product) {
                    $productRecord = TrendyolHelper::getProductByBarcode($order->user, $order->store, $product?->barcode);

                    OrderProduct::firstOrCreate([
                        'order_id' => $order->id,
                        'product_id' => $productRecord->id,
                    ], [
                        'user_id' => $order->user->id,
                        'store_id' => $order->store->id,
                        'quantity' => $product?->quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::commit();

                Cache::put('order_fetch_date_' . $order->user->id . '_' . $order->store->id, now()->toDateTimeString(), 1440 * 2);
            } catch (\Exception $exception) {
                DB::rollBack();
                dd($exception);
                Log::error("Error fetching orders for user {$order->user->id} and store {$order->store->id}: " . $exception->getMessage() . " Line:" . $exception->getLine());
            }
        });
    }
}
