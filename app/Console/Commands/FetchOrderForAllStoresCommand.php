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
                    $bar = $this->output->createProgressBar(count($orders));
                    $bar->start();

                    $allBarcodes = [];
                    foreach ($orders as $order) {
                        foreach ($order?->lines as $product) {
                            $allBarcodes[] = $product?->barcode;
                        }
                    }

                    $uniqueBarcodes = array_unique($allBarcodes);
                    $products = TrendyolHelper::getProductsByBarcodes($user, $uniqueBarcodes);

                    $productMap = [];
                    foreach ($products as $product) {
                        $productMap[$product->barcode] = $product;
                    }

                    foreach ($orders as $order) {
                        $orderRecordFromDatabase = Order::firstOrCreate([
                            'order_id' => $order?->id,
                        ], [
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

                       /* foreach ($order?->lines as $product) {
                            $productRecord = $productMap[$product?->barcode] ?? null;
                            TrendyolHelper::getProductByBarcode($user, $product?->barcode);
                        }*/

                        $bar->advance();

                        $store->update(['order_fetched_at' => now()]);
                    }
                    $bar->finish();

                    Cache::put('order_fetch_date_' . $user->id . '_' . $store->id, now()->toDateTimeString(), 1440 * 2);
                } catch (\Exception $exception) {
                    Log::error("Error fetching orders for user {$user->id} and store {$store->id}: " . $exception->getMessage() . " Line:" . $exception->getLine());
                }
            });
        });

        Log::info('Scheduled fetch order for all stores worked!');
    }
}
