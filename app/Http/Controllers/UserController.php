<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function accountInformationStore(Request $request)
    {
        $user = auth()->user();

        $user->update([
            'supplier_id' => $request->get('supplier_id'),
            'token'=> $request->get('token'),
        ]);

        return redirect()->back()->with('success','Basarili bir sekilde ayarlandi');
    }
}
