<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CargoRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Helpers\KolayGelsinHelper;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'store', 'orderProducts.product'])
            ->whereHas('store', function($q) {
                $q->where('user_id', auth()->id());
            });

        // Store filtresi
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Diğer filtreler
        if ($request->filled('cargo_service_provider')) {
            $query->where('cargo_service_provider', $request->cargo_service_provider);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', 'like', '%' . $request->order_id . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        // Kargo firmalarını ve durumları filtre için al (sadece kullanıcının store'larından)
        $cargoProviders = Order::whereHas('store', function($q) {
                $q->where('user_id', auth()->id());
            })
            ->select('cargo_service_provider')
            ->whereNotNull('cargo_service_provider')
            ->distinct()
            ->pluck('cargo_service_provider');
            
        $statuses = Order::TYPES;

        return view('shipments.index', compact('orders', 'cargoProviders', 'statuses'));
    }

    public function rulesIndex(Request $request)
    {
        // Kargo kurallarını paginate ile çek (sadece kullanıcının kuralları)
        $cargoRules = CargoRule::with('user')
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('shipments.rules.index', compact('cargoRules'));
    }

    public function storeRule(Request $request)
    {
        $request->validate([
            'from_cargo' => 'required|string',
            'to_cargo' => 'required|string|different:from_cargo',
            'exclude_barcodes' => 'nullable|string',
            'include_barcodes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            // Kuralı kaydet
            $rule = CargoRule::create([
                'user_id' => auth()->id(),
                'store_id' => $request->store_id,
                'from_cargo' => $request->from_cargo,
                'to_cargo' => $request->to_cargo,
                'exclude_barcodes' => $request->exclude_barcodes,
                'include_barcodes' => $request->include_barcodes,
                'status' => 'active'
            ]);

            // Siparişleri bul ve güncelle
            $orders = Order::where('cargo_service_provider', $request->from_cargo);
            
            if ($request->filled('exclude_barcodes')) {
                $excludeBarcodes = collect(explode(',', $request->exclude_barcodes))
                    ->filter()
                    ->map(fn($b) => trim($b))
                    ->all();

                $orders->whereDoesntHave('orderProducts.product', function($query) use ($excludeBarcodes) {
                    $query->whereIn('barcode', $excludeBarcodes);
                });
            }
            if ($request->filled('include_barcodes')) {
                $includeBarcodes = collect(explode(',', $request->include_barcodes))
                    ->filter()
                    ->map(fn($b) => trim($b))
                    ->all();
                $orders->whereHas('orderProducts.product', function($query) use ($includeBarcodes) {
                    $query->whereIn('barcode', $includeBarcodes);
                });
            }

            $affectedOrders = $orders->get();
            $updated = 0;
            $errors = [];

            $rule->save();

            DB::commit();

            if ($errors) {
                return redirect()->route('shipments.rules.index')
                    ->with('warning', "Kural eklendi fakat bazı siparişler güncellenemedi. {$rule->result}");
            }

            return redirect()->route('shipments.rules.index')
                ->with('success', "Kural başarıyla eklendi ve {$updated} sipariş güncellendi.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('shipments.rules.index')
                ->with('error', 'Kural eklenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function editRule(CargoRule $rule)
    {
        return view('shipments.rules.edit', compact('rule'));
    }

    public function updateRule(Request $request, CargoRule $rule)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'from_cargo' => 'required|string',
            'to_cargo' => 'required|string|different:from_cargo',
            'exclude_barcodes' => 'nullable|string',
            'include_barcodes' => 'nullable|string',
        ]);

        try {
            $rule->update([
                'store_id' => $request->store_id,
                'from_cargo' => $request->from_cargo,
                'to_cargo' => $request->to_cargo,
                'exclude_barcodes' => $request->exclude_barcodes,
                'include_barcodes' => $request->include_barcodes,
                'status' => 'pending' // Kuralı yeniden çalıştırmak için pending yapıyoruz
            ]);

            return redirect()->route('shipments.rules.index')
                ->with('success', 'Kural başarıyla güncellendi.');
        } catch (\Exception $e) {
            return redirect()->route('shipments.rules.index')
                ->with('error', 'Kural güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function destroyRule(CargoRule $rule)
    {
        try {
            $rule->delete();
            return redirect()->route('shipments.rules.index')
                ->with('success', 'Kural başarıyla silindi.');
        } catch (\Exception $e) {
            return redirect()->route('shipments.rules.index')
                ->with('error', 'Kural silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function singleUpdate(Request $request, Order $order)
    {
        $request->validate([
            'cargo_service_provider' => 'required|string',
        ]);

        $oldProvider = $order->cargo_service_provider;
        $newProvider = $request->cargo_service_provider;

        if ($oldProvider === $newProvider) {
            return redirect()->back()->with('info', 'Kargo firması zaten seçili.');
        }

        DB::beginTransaction();
        try {
            $order->cargo_service_provider = $newProvider;
            $order->save();
            \App\Helpers\TrendyolHelper::updateOrderCargoProvider($order, array_search($newProvider, CargoRule::CARGO_PROVIDERS));
            DB::commit();
            return redirect()->back()->with('success', 'Kargo firması başarıyla güncellendi ve Trendyol API ile senkronize edildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Güncelleme sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    public function executeRule(CargoRule $rule)
    {
        try {
            // Artisan komutunu çalıştır
            Artisan::call('change:cargo', [
                '--rule_id' => $rule->id
            ]);
            
            $output = Artisan::output();
            
            // Kuralı yenile ve güncel durumu al
            $rule->refresh();
            
            return redirect()->route('shipments.rules.index')
                ->with('success', "Kural başarıyla çalıştırıldı. Sonuç: {$rule->result}");
                
        } catch (\Exception $e) {
            return redirect()->route('shipments.rules.index')
                ->with('error', 'Kural çalıştırılırken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function generateZPL(Order $order)
    {
        try {
            // Sadece KOLAYGELSINMP kargo sağlayıcısı için ZPL oluştur
            if ($order->cargo_service_provider !== 'Kolay Gelsin Marketplace') {
                return redirect()->back()->with('error', 'ZPL oluşturma sadece Kolay Gelsin kargo için kullanılabilir.');
            }

            $store = $order->store;
            if (!$store) {
                return redirect()->back()->with('error', 'Sipariş için mağaza bilgisi bulunamadı.');
            }

            // KolayGelsinHelper ile ZPL barcode oluştur
            $barcodeResponse = KolayGelsinHelper::getBarcode($store, 2, $order->cargo_tracking_number);
            
            if (isset($barcodeResponse['result']) && isset($barcodeResponse['result']['BarcodeZpl'])) {
                // ZPL verisini Order'a kaydet
                $order->update([
                    'zpl_barcode' => $barcodeResponse['result']['BarcodeZpl']
                ]);

                return redirect()->back()->with('success', 'ZPL barcode başarıyla oluşturuldu ve kaydedildi.');
            } else {
                return redirect()->back()->with('error', 'ZPL barcode oluşturulamadı: ' . ($barcodeResponse['message'] ?? 'Bilinmeyen hata'));
            }

        } catch (\Exception $e) {
            dd($e);
            return redirect()->back()->with('error', 'ZPL oluşturulurken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function incrementPrintCount(Request $request)
    {
        try {
            $orderIds = $request->input('order_ids', []);
            
            if (empty($orderIds)) {
                return response()->json(['success' => false, 'message' => 'Sipariş ID\'leri bulunamadı.']);
            }

            // Order'ların print count'unu 1 arttır
            Order::whereIn('id', $orderIds)
                ->increment('zpl_print_count');

            return response()->json(['success' => true, 'message' => 'Yazdırma sayısı güncellendi.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
    }
} 