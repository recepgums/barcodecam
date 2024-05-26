<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolHelper
{
    public static function getOrders(User $user, $page, $orderStatus = 'Created')
    {
        $defaultStore = $user->stores()->defaultStore()->first();

        $queryString=  'orderByField=PackageLastModifiedDate&orderByDirection=DESC&size=200&page=' . $page;
        if ($orderStatus){
            $queryString.= '&status=' . $orderStatus;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $defaultStore->token
        ])->get('https://api.trendyol.com/sapigw/suppliers/' . $defaultStore->supplier_id . '/orders?',$queryString);

        $responseContent = $response->body();
        return json_decode($responseContent)?->content;
    }

    public static function getProductByBarcode(User $user, $barcode)
    {
        $defaultStore = $user->stores()->defaultStore()->first();

        if (! $defaultStore){
            dd('aaa');
            $defaultStore = $user->stores()->orderByDesc('created_at')->first();

            $defaultStore->update(['is_default' => true]);
        }

        return Cache::remember($barcode,43200,function ()use($defaultStore,$barcode,$user){
            $response =  Http::withHeaders(['Authorization' => 'Basic ' . $defaultStore->token])
                ->get('https://api.trendyol.com/sapigw/suppliers/' . $defaultStore->supplier_id . '/products?barcode=' . $barcode);

            $responseContent = $response->body();
            $content = json_decode($responseContent)->content[0];

            return Product::firstOrCreate(
                [
                    'barcode' => $barcode,
                    'user_id' => $user->id,
                    'store_id' => $defaultStore->id,
                ],
                [
                    'title' => $content->title,
                    'price' => $content->salePrice,
                    'quantity' => $content->quantity,
                    'image_url' => $content->images[0]->url,
                    'productUrl' => $content->productUrl,
                ]
            );
        });
    }
}
