<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class TrendyolHelper
{
    public static function getOrders(User $user, $page, $orderStatus = 'Created')
    {
        $defaultStore = $user->stores()->defaultStore()->first();

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $defaultStore->token
        ])->get('https://api.trendyol.com/sapigw/suppliers/' . $defaultStore->supplier_id . '/orders?
        orderByField=PackageLastModifiedDate&
        orderByDirection=DESC&
        status=' . $orderStatus . '&
        size=200&page=' . $page);

        $responseContent = $response->body();
        return json_decode($responseContent)?->content;
    }

    public static function getProductByBarcode(User $user, $barcode)
    {
        $defaultStore = $user->stores()->defaultStore()->first();

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $defaultStore->token
        ])->get('https://api.trendyol.com/sapigw/suppliers/' . $defaultStore->supplier_id . '/products?barcode=' . $barcode);

        $responseContent = $response->body();
        return json_decode($responseContent)->content[0];
    }
}
