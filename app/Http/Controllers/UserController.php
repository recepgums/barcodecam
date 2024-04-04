<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function accountInformationStore(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|numeric',
            'token' => 'required|string',
            'merchant_name' => 'required|string',
        ]);

        $user = auth()->user();

        try {
            DB::beginTransaction();
            $user->stores()->update(['is_default' => 0]);

            Store::create([
                'user_id' => auth()->id(),
                'merchant_name' => $request->get('merchant_name'),
                'supplier_id' => (int)$request->get('supplier_id'),
                'token' => $request->get('token'),
                'is_default' => 1,
            ]);

            DB::commit();
        }catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error',$e->getMessage());
        }

        return redirect()->back()->with('success','Basarili bir sekilde ayarlandi');
    }
}
