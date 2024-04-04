<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function updateDefault(Request $request)
    {
        $user = auth()->user();
        try {
            DB::beginTransaction();
            $user->stores()->update(['is_default' => false]);

            $store = Store::where('id', $request->store_id)
                ->where('user_id', $user->id)
                ->update(['is_default' => true]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
        }

        return response()->json(['store' => $store]);
    }
}
