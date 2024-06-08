<?php

namespace App\Console\Commands;

use App\Helpers\TrendyolHelper;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchOrderForAllStoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:orders';

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
        User::with('stores')->get()->each(function ($user) {
            $user->stores->each(function ($store) use ($user) {
                $orders = [];
                $orderProducts = [];
                $page = 0;

                do {
                    $currentOrders = TrendyolHelper::getOrdersByStore($store, $page);
                    if (!empty($currentOrders)) {
                        $orders = array_merge($orders, $currentOrders);
                        $page++;
                    } else {
                        break;
                    }
                } while (true);

                try {
                    DB::beginTransaction();

                    foreach ($orders as $order) {
                       $orderRecordFromDatabase = Order::firstOrCreate([
                            'order_id' => $order?->id,
                        ],[
                            'user_id' => $user->id,
                            'store_id' => $store->id,
                            'customer_name' => $order?->shipmentAddress?->fullName ?? '',
                            'address' => $order->shipmentAddress?->fullAddress ?? '',
                            'order_id' => $order?->id,
                            'cargo_tracking_number' => $order?->cargoTrackingNumber ?? '',
                            'cargo_service_provider' => $order?->cargoProviderName ?? '',
                            'lines' => json_encode($order?->lines),
                            'order_date' => date('Y-m-d H:i:s', $order?->orderDate / 1000),
                            'status' => $order->status,
                            'total_price' => $order->totalPrice,
                        ]);

                        foreach ($order?->lines as $product) {
                            $productRecord = TrendyolHelper::getProductByBarcode($user, $product?->barcode);

                            $orderProducts[] = [
                                'user_id' => $user->id,
                                'store_id' => $store->id,
                                'order_id' => $orderRecordFromDatabase->id,
                                'product_id' => $productRecord?->id,
                                'original_order_id' => $order?->id,
                                'quantity' => $product?->quantity,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    OrderProduct::insert($orderProducts);
                    DB::commit();

                    Cache::put('order_fetch_date_' . $user->id . '_' . $store->id, now()->toDateTimeString(), 1440 * 2);
                } catch (\Exception $exception) {
                    DB::rollBack();
                    Log::error("Error fetching orders for user {$user->id} and store {$store->id}: " . $exception->getMessage());
                }
            });
        });

        Log::info('Scheduled fetch order for all stores worked!');
    }
}
