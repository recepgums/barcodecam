<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $orderFetchDate =  Cache::get('order_fetch_date_' . auth()->id());

        $stores = Store::where('user_id',auth()->id())->get();
        $orderCount = Order::where('user_id',auth()->id())->count();

        return view('home',compact('orderFetchDate','stores','orderCount'));
    }
}
