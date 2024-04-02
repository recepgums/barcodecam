<?php

namespace App\Http\Controllers;

use App\Helpers\TrendyolHelper;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function getByCargoTrackId(Request $request)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('cargo_tracking_number', $request->get('code'))
            ->first();

        if ($request->get('response_type') == 'view') {
            $products = json_decode($order?->lines);
            $view = view('components.modal.order-detail', ['products' => $products,'order' => $order])->render();

          return response()->json(['view' => $view]);
        }

        return response()->json(['data' => $order]);
    }

    public function getOrders()
    {

        $orders = [];
        $page = 0;
        $user = auth()->user();
        do {
            $currentOrders = TrendyolHelper::getOrders($user, $page);

            if (!empty($currentOrders)) {
                $orders = array_merge($orders, $currentOrders);
                $page++;
            } else {
                break;
            }
        } while (true);


        try {
            DB::beginTransaction();

            Order::where('user_id', auth()->id())->delete();
            foreach ($orders as $order) {
                Order::create([
                    'user_id' => $user->id,
                    'customer_name' => $order->shipmentAddress->fullName,
                    'address' => $order->shipmentAddress->fullAddress,
                    'order_id' => $order->id,
                    'cargo_tracking_number' => $order->cargoTrackingNumber,
                    'cargo_service_provider' => $order->cargoProviderName,
                    'lines' => json_encode($order->lines),
                    'order_date' => date('Y-m-d H:i:s', $order->orderDate / 1000),
                    'status' => $order->status,
                    'total_price' => $order->totalPrice,
                ]);
            }
            DB::commit();

            Cache::put('order_fetch_date_' . auth()->id(),now()->toDateTimeString(),1440 * 2);
        } catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }
        return redirect()->back()->with('success', 'Urunler basariyla cekildi');
    }
}
