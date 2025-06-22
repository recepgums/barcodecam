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

class KolayGelsinHelper
{
    public static function login(Store $store)
    {
        return Cache::remember('kolaygelsin_token_'.$store->id, 60 * 24 * 3, function () use ($store) {
            $response = Http::post('https://api.sendeo.com.tr/api/Token/LoginAES', [
                'musteri' => $store->kolaygelsin_username,
                'sifre' => $store->kolaygelsin_password
            ]);

            if($response->status() == 401){
                Cache::forget('kolaygelsin_token_'.$store->id);
                return self::login($store);
            }
            return $response->json()['result']['Token'];
        });
    }

 
    public static function getBarcode($store, $barcodeLabelType, $referenceNo)
    {
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . self::login($store)
        ])->post('https://api.sendeo.com.tr/api/Cargo/GETBARCODE', [
            'barcodeLabelType' => $barcodeLabelType,
            'referenceNo' => $referenceNo
        ]);

        if($response->json()['StatusCode'] != 200){
            dd($response->json(),self::login($store),$barcodeLabelType,$referenceNo);
        }
        if($response->status() == 401){
            self::login($store);
            return self::getBarcode($store, $barcodeLabelType, $referenceNo);
        }
        return $response->json();
    }
}
