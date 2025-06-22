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
        $orders = Order::with(['store', 'user'])->get();
        $bar = $this->output->createProgressBar(count($orders));
        $bar->start();

        foreach ($orders as $order) {
            try {
                $products = json_decode($order?->lines);
                if (!$products) {
                    $this->warn("Order {$order->id} has no valid lines data");
                    $bar->advance();
                    continue;
                }
                
                foreach ($products as $product) {
                    if (!$product?->barcode) {
                        $this->warn("Product in order {$order->id} has no barcode");
                        continue;
                    }
                    
                    $productRecord = TrendyolHelper::getProductByBarcode($order->user, $order->store, $product->barcode);
                    
                    // Ürün bulunamazsa atla
                    if (!$productRecord) {
                        $this->warn("Product not found for barcode: {$product->barcode} in order {$order->id}");
                        continue;
                    }
                    
                    // Ürünün database'de gerçekten var olup olmadığını kontrol et
                    $existingProduct = \App\Models\Product::find($productRecord->id);
                    if (!$existingProduct) {
                        $this->warn("Product ID {$productRecord->id} does not exist in database for barcode: {$product->barcode} in order {$order->id}");
                        // Cache'i temizle
                        \Illuminate\Support\Facades\Cache::forget($product->barcode);
                        continue;
                    }
                    
                    try {
                        OrderProduct::firstOrCreate([
                            'order_id' => $order->id,
                            'product_id' => $productRecord->id,
                        ], [
                            'user_id' => $order->user->id,
                            'store_id' => $order->store->id,
                            'quantity' => $product->quantity ?? 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        if ($e->getCode() === '23000') {
                            $this->warn("Foreign key constraint violation for product_id: {$productRecord->id}, barcode: {$product->barcode} in order {$order->id}");
                            // Cache'i temizle
                            \Illuminate\Support\Facades\Cache::forget($product->barcode);
                        } else {
                            throw $e;
                        }
                    }
                }
                $bar->advance();
            } catch (\Exception $exception) {
                dd($exception);
                Log::error("Error fetching orders for user {$order->user->id} and store {$order->store->id}: " . $exception->getMessage() . " Line:" . $exception->getLine());
                $bar->advance(); // Hatada da progress bar'ı ilerlet
            }
        }
        $bar->finish();

    }
}
