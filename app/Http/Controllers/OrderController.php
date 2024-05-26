<?php

namespace App\Http\Controllers;

use App\Helpers\TrendyolHelper;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $orders = Order::where('user_id',auth()->id())
            ->whereHas('media')->get();

        return view('orders.index', ['orders' => $orders]);
    }
    public function getByCargoTrackId(Request $request)
    {
        try {
            $order = Order::where('user_id', auth()->id())
                ->where('cargo_tracking_number', $request->get('code'))
                ->first();

            if ($request->get('response_type') == 'view') {
                $products = json_decode($order?->lines);
                $view = view('components.modal.order-detail', ['products' => $products,'order' => $order])->render();

                return response()->json(['view' => $view,'order_id' => $order?->id,'video_url' => $order?->getFirstMediaUrl('videos')]);
            }
        }catch (Exception $e) {
            Log::error('ERROR ON GET ORDER BY TRACK ID '.$request->get('code') . "\n".
                json_encode($order) . "....." .
                $e->getMessage().$e->getLine().$e->getFile());
            dd($e->getMessage().$e->getLine().$e->getFile());
            return response()->json(['data' => $order]);
        }

        return response()->json(['data' => $order]);
    }

    public function getOrders(Request $request)
    {
        $orders = [];
        $page = 0;
        $user = auth()->user();
        $defaultStore = $user->stores()->defaultStore()->first();

        do {
            $currentOrders = TrendyolHelper::getOrders($user, $page,$request->get('order_status'));

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
                    'user_id' => auth()->id(),
                    'store_id' => $defaultStore?->id,
                    'customer_name' => $order?->shipmentAddress?->fullName ?? '',
                    'address' => $order->shipmentAddress?->fullAddress ?? '',
                    'order_id' => $order?->id,
                    'cargo_tracking_number' => $order?->cargoTrackingNumber ?? '',
                    'cargo_service_provider' => $order?->cargoProviderName ?? '',
                    'lines' => json_encode($order?->lines),
                    'order_date' => date('Y-m-d H:i:s', $order?->orderDate / 1000),
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
        return redirect()->back()->with('success', 'Siparişler başarıyla çekildi');
    }

    public function storeVideo(Order $order,Request $request)
    {
        Db::beginTransaction();
        try {
            $order->clearMediaCollection('videos');

            $randomString = Str::random(10);

            $filename = "{$order->user_id}_{$order->tracking_id}_{$randomString}.mp4";

            $mediaItem = $order->addMedia($request->file('video'))
                ->usingFileName($filename)
                ->toMediaCollection('videos');

            $videoUrl = $mediaItem->getUrl();

            DB::commit();
        }catch (Exception $exception){
            DB::rollBack();
            dd($exception->getMessage());
        }

        return response()->json([
            'video_url' => $videoUrl
        ]);
    }
}
