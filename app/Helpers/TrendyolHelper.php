<?php
namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class TrendyolHelper
{
    public static function getOrders(User $user,$page)
    {
        $response = Http::withHeaders([
            'Authorization' =>'Basic '. $user->token
        ])->get('https://api.trendyol.com/sapigw/suppliers/'.$user->supplier_id.'/orders?
        orderByField=PackageLastModifiedDate&
        orderByDirection=DESC&
        size=200&page='.$page);
        //status=Created,Shipped

        $responseContent = $response->body();
        return json_decode($responseContent)->content;
    }

    public static function getProductByBarcode(User $user,$barcode)
    {
        $response = Http::withHeaders([
            'Authorization' =>'Basic '. $user->token
        ])->get('https://api.trendyol.com/sapigw/suppliers/'.$user->supplier_id.'/products?barcode='.$barcode);

        $responseContent = $response->body();
        return json_decode($responseContent)->content[0];
    }
}
