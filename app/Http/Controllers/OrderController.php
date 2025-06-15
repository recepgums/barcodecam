<?php

namespace App\Http\Controllers;

use App\Helpers\TrendyolHelper;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $query = Order::with(['orderProducts.product', 'media'])
            ->where('user_id', auth()->id());

        // Tarih aralığı filtresi
        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        // Sipariş durumu filtresi
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Video durumu filtresi
        if ($request->filled('has_video')) {
            if ($request->has_video == '1') {
                $query->whereHas('media');
            } else {
                $query->whereDoesntHave('media');
            }
        }

        // only_videos parametresi için özel pagination
        if ($request->filled('only_videos') && $request->only_videos == '1') {
            $orders = $query->orderBy('created_at', 'desc')->paginate(200);
        } else {
            $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        }

        return view('orders.index', [
            'orders' => $orders
        ]);
    }
    public function getByCargoTrackId(Request $request)
    {
        try {
            $order = Order::where('user_id', auth()->id())
                ->where('cargo_tracking_number', $request->get('code'))
                ->first();

            if ($request->get('response_type') == 'view') {
                $products = json_decode($order?->lines);
                $view = view('components.modal.order-detail', ['products' => $products, 'order' => $order])->render();

                return response()->json(['view' => $view, 'order_id' => $order?->id, 'video_url' => $order?->getFirstMediaUrl('videos')]);
            }
        } catch (Exception $e) {
            Log::error('ERROR ON GET ORDER BY TRACK ID ' . $request->get('code') . "\n" .
                json_encode($order) . "....." .
                $e->getMessage() . $e->getLine() . $e->getFile());
            dd($e->getMessage() . $e->getLine() . $e->getFile());
            return response()->json(['data' => $order]);
        }

        return response()->json(['data' => $order]);
    }

    public function getOrders(Request $request)
    {
        set_time_limit(300);
        $orders = [];
        $page = 0;
        $user = auth()->user();
        $defaultStore = $user->stores()->defaultStore()->first();

        do {
            $currentOrders = TrendyolHelper::getOrdersByUser($user, $page, $request->get('status'));

            if (!empty($currentOrders)) {
                $orders = array_merge($orders, $currentOrders);
                $page++;
            } else {
                break;
            }
        } while (true);

        try {

            foreach ($orders as $order) {
                $orderRecord = Order::firstOrCreate([
                    'order_id' => $order?->id,
                ],[
                    'user_id' => $user->id,
                    'store_id' => $defaultStore?->id,
                    'customer_name' => $order?->shipmentAddress?->fullName ?? '',
                    'address' => $order->shipmentAddress?->fullAddress ?? '',
                    'order_id' => $order?->id,
                    'cargo_tracking_number' => $order?->cargoTrackingNumber ?? '',
                    'cargo_service_provider' => $order?->cargoProviderName ?? '',
                    'lines' => json_encode($order?->lines),
                    'order_date' => date('Y-m-d H:i:s', $order?->orderDate / 1000),
                    'agreed_delivery_date' => $order?->agreedDeliveryDate ? date('Y-m-d H:i:s', $order?->agreedDeliveryDate / 1000) : null,
                    'status' => $order->status,
                    'total_price' => $order->totalPrice,
                ]);
            }

            Cache::put('order_fetch_date_' . auth()->id(), now()->toDateTimeString(), 1440 * 2);
        } catch (Exception $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        $defaultStore->update(['order_fetched_at' => now()]);

        return redirect()->back()->with('success', 'Siparişler başarıyla çekildi');
    }

    public function storeVideo(Order $order, Request $request)
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
        } catch (Exception $exception) {
            DB::rollBack();
            dd($exception->getMessage());
        }

        return response()->json([
            'video_url' => $videoUrl
        ]);
    }

    public function fetchOrderCron()
    {
        try {
            Artisan::call('fetch:orders');
        }catch (Exception $exception) {
            return response()->json(['status' => 'FAILED'. $exception->getMessage()],500);
        }
        return response()->json(['status' => 'Orders fetched successfully']);
    }

    /**
     * Seçili siparişleri işleme alındı durumuna günceller
     */
    public function updateToProcess(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|integer|exists:orders,id'
        ]);

        $orderIds = $request->order_ids;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        foreach($orderIds as $orderId){
            try {
                $order = Order::find($orderId);
                if (!$order) {
                    $errors[] = "Sipariş ID {$orderId} bulunamadı";
                    $errorCount++;
                    continue;
                }
                
                // Lines verisini parse et
                $linesData = json_decode($order->lines, true);
                if (empty($linesData)) {
                    $errors[] = "Sipariş {$order->order_id}: Lines verisi bulunamadı";
                    $errorCount++;
                    continue;
                }
                
                // Trendyol API formatına çevir
                $formattedLines = [];
                foreach ($linesData as $line) {
                    $formattedLines[] = [
                        'lineId' => (int)$line['id'], // long olarak gönder
                        'quantity' => (int)$line['quantity'] // int olarak gönder
                    ];
                }
                
                // API isteği gönder
                $response = TrendyolHelper::updateOrderPackageStatus($order, 'Picking', $formattedLines);
                
                // Başarılı ise local database'i de güncelle (Order.php const'taki değer)
                $order->update(['status' => 'Picking']);
                $successCount++;
                
            } catch (\Exception $e) {
                $orderIdForError = isset($order) ? $order->order_id : $orderId;
                $errors[] = "Sipariş {$orderIdForError}: " . $e->getMessage();
                $errorCount++;
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => "İşlem tamamlandı. Başarılı: {$successCount}, Hatalı: {$errorCount}",
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);
    }
}
