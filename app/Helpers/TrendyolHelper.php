<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Order;
use App\Models\CargoRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolHelper
{
    public static function getOrdersByUser(User $user, $page, $orderStatus = 'Created')
    {
        $defaultStore = $user->stores()->defaultStore()->first();

        if (is_array($orderStatus)){
            $orderStatus = trim(implode(',', $orderStatus));
        }

        $queryString = 'orderByField=PackageLastModifiedDate&orderByDirection=DESC&size=200&page=' . $page;
        if ($orderStatus) {
            $queryString .= '&status=' . $orderStatus;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $defaultStore->token
        ])->get('https://api.trendyol.com/sapigw/suppliers/' . $defaultStore->supplier_id . '/orders?', $queryString);

        $responseContent = $response->body();
        return json_decode($responseContent)?->content;
    }

    public static function getProductsByBarcodes(User $user, $barcodes)
    {
        $defaultStore = $user->stores()->defaultStore()->first();

        if (!$defaultStore) {
            $defaultStore = $user->stores()->orderByDesc('created_at')->first();
            $defaultStore->update(['is_default' => true]);
        }

        $retryCount = 3; // Number of retries
        $timeout = 60; // Timeout in seconds
        $products = [];
        if($defaultStore->api_key && $defaultStore->api_secret){
            $basicToken = base64_encode($defaultStore->api_key.":".$defaultStore->api_secret);
            $endpoint = 'https://apigw.trendyol.com/integration/product/sellers/' . $defaultStore->supplier_id . '/products';
        }else{
            $basicToken = $defaultStore->token;
            $endpoint = 'https://api.trendyol.com/sapigw/suppliers/' . $defaultStore->supplier_id . '/products';
        }

        for ($i = 0; $i < $retryCount; $i++) {
            try {

                $response = Http::withHeaders([
                    'Authorization' => 'Basic ' . $basicToken
                ])->timeout($timeout)
                ->get($endpoint, [
                    'barcodes' => implode(',', $barcodes)
                ]);

                if ($response->successful()) {
                    $responseContent = $response->body();
                    $content = json_decode($responseContent)->content;

                    foreach ($content as $productData) {
                        $product = Cache::remember($productData->barcode, 43200, function () use ($productData, $user, $defaultStore) {
                            return Product::firstOrCreate(
                                [
                                    'barcode' => $productData->barcode,
                                    'user_id' => $user->id,
                                    'store_id' => $defaultStore->id,
                                ],
                                [
                                    'title' => $productData->title,
                                    'price' => $productData->salePrice,
                                    'quantity' => $productData->quantity,
                                    'image_url' => $productData->images[0]->url,
                                    'productUrl' => $productData->productUrl,
                                ]
                            );
                        });
                        $products[] = $product;
                    }

                    return $products;
                } else {
                    Log::warning('Trendyol API request failed', ['response' => $response->body()]);
                    throw new \Exception('API request failed');
                }
            } catch (\Exception $e) {
                Log::error('Trendyol API request error: ' . $e->getMessage());

                if ($i == $retryCount - 1) {
                    return [];
                }

                sleep(2);
            }
        }

        return $products;
    }

    public static function getProductByBarcode(User $user, Store $store = null, $barcode)
    {
        if (!$store) {
            $store = $user->stores()->defaultStore()->first();
        }

        return Cache::remember($barcode, 43200, function () use ($store, $barcode, $user) {
            $retryCount = 3; // Number of retries
            $timeout = 60; // Timeout in seconds

            if($store->api_key && $store->api_secret){
                $basicToken = base64_encode($store->api_key.":".$store->api_secret);
                $endpoint = 'https://apigw.trendyol.com/integration/product/sellers/' . $store->supplier_id . '/products?barcode=' . $barcode;

            }else{
                $basicToken = $store->token;
                $endpoint = 'https://api.trendyol.com/sapigw/suppliers/' . $store->supplier_id . '/products?barcode=' . $barcode;
            }
            for ($i = 0; $i < $retryCount; $i++) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Basic ' . $basicToken
                    ])->timeout($timeout)->get($endpoint);
                    if ($response->successful()) {
                        $responseContent = $response->body();
                        $content = json_decode($responseContent)->content[0];

                        return Product::firstOrCreate(
                            [
                                'barcode' => $barcode,
                                'user_id' => $user->id,
                                'store_id' => $store->id,
                            ],
                            [
                                'title' => $content->title,
                                'price' => $content->salePrice,
                                'quantity' => $content->quantity,
                                'image_url' => $content->images[0]->url,
                                'productUrl' => $content->productUrl,
                            ]
                        );
                    } else {
                        Log::warning('Trendyol API request failed', ['response' => $response->body()]);
                        throw new \Exception('API request failed');
                    }
                } catch (\Exception $e) {
                    Log::error('Trendyol API request error: ' . $e->getMessage());

                    if ($i == $retryCount - 1) {
                        return null;
                    }

                    sleep(2);
                }
            }

            return null;
        });
    }

    public static function getOrdersByStore(Store $store, $page)
    {
        $queryString = 'orderByField=PackageLastModifiedDate&orderByDirection=DESC&size=200&status=Created,Picking,Invoiced,Repack,UnPacked&page=' . $page;
        
        if($store->api_key && $store->api_secret){
            $basicToken = base64_encode($store->api_key.":".$store->api_secret);
            $endpoint = 'https://apigw.trendyol.com/integration/order/sellers/'. $store->supplier_id .'/orders?'.$queryString;
        }else{
            $basicToken = $store->token;
            $endpoint = 'https://api.trendyol.com/sapigw/suppliers/' . $store->supplier_id . '/orders?'.$queryString;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $basicToken
        ])->get($endpoint, $queryString);

        $responseContent = $response->body();
        $decodedResponse = json_decode($responseContent);
        
        if (isset($decodedResponse->content)) {
            return $decodedResponse->content;
        } else {
            Log::error('Invalid response received from Trendyol API', ['store' => $store, 'response' => $responseContent]);
            return [];
        }
    }

    /**
     * Siparişin kargo bilgisini Trendyol API ile günceller.
     */
    public static function updateOrderCargoProvider(\App\Models\Order $order, string $toCargo)
    {
        $store = $order->store;
        if (!$store) {
            throw new \Exception('Siparişe ait mağaza bulunamadı.');
        }

        //check if store has api_key and api_secret and set the token variable  
        if($store->api_key && $store->api_secret){
            $basicToken = base64_encode($store->api_key.":".$store->api_secret);
        }else{
            $basicToken = $store->token;
        }
        // Header'a eklerken:
        $headers = [
            'Authorization' => 'Basic ' . $basicToken,
            'Accept' => 'application/json',
        ];


        $endpoint = 'https://apigw.trendyol.com/integration/order/sellers/'. $store->supplier_id .'/shipment-packages/' . $order->order_id . '/cargo-providers';
        $payload = [
            'cargoProvider' => $toCargo,
        ];

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Basic ' . $basicToken,
        ])->put($endpoint, $payload);


        if (!$response->successful()) {
            throw new \Exception('Trendyol API kargo güncelleme başarısız: ' . $response->body());
        }
        return true;
    }
}
