<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CargoRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'store', 'orderProducts.product']);

        // Filtreler
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

        // Kargo firmalarını ve durumları filtre için al
        $cargoProviders = Order::select('cargo_service_provider')
            ->whereNotNull('cargo_service_provider')
            ->distinct()
            ->pluck('cargo_service_provider');
            
        $statuses = Order::TYPES;

        // Kargo kurallarını paginate ile çek
        $cargoRules = CargoRule::with('user')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('shipments.index', compact('orders', 'cargoProviders', 'statuses', 'cargoRules'));
    }

    public function storeRule(Request $request)
    {
        $request->validate([
            'from_cargo' => 'required|string',
            'to_cargo' => 'required|string|different:from_cargo',
            'exclude_barcodes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Kuralı kaydet
            $rule = CargoRule::create([
                'user_id' => auth()->id(),
                'from_cargo' => $request->from_cargo,
                'to_cargo' => $request->to_cargo,
                'exclude_barcodes' => $request->exclude_barcodes,
                'status' => 'pending'
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

            $affectedOrders = $orders->get();
            $updated = 0;
            $errors = [];

            foreach ($affectedOrders as $order) {
                try {
                    $order->cargo_service_provider = $request->to_cargo;
                    $order->save();
                    
                    // Trendyol API ile güncelle
                    \App\Helpers\TrendyolHelper::updateOrderCargoProvider($order, $request->to_cargo);
                    $updated++;
                } catch (\Exception $e) {
                    $errors[] = "Sipariş #{$order->order_id}: " . $e->getMessage();
                }
            }

            // Kural durumunu güncelle
            $rule->status = $errors ? 'failed' : 'executed';
            $rule->result = $errors 
                ? 'Hatalar: ' . implode(', ', $errors)
                : "{$updated} sipariş başarıyla güncellendi.";
            $rule->executed_at = now();
            $rule->save();

            DB::commit();

            if ($errors) {
                return redirect()->route('shipments.index')
                    ->with('warning', "Kural eklendi fakat bazı siparişler güncellenemedi. {$rule->result}");
            }

            return redirect()->route('shipments.index')
                ->with('success', "Kural başarıyla eklendi ve {$updated} sipariş güncellendi.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('shipments.index')
                ->with('error', 'Kural eklenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function editRule(CargoRule $rule)
    {
        $cargoProviders = Order::select('cargo_service_provider')
            ->whereNotNull('cargo_service_provider')
            ->distinct()
            ->pluck('cargo_service_provider');

        return view('shipments.rules.edit', compact('rule', 'cargoProviders'));
    }

    public function updateRule(Request $request, CargoRule $rule)
    {
        $request->validate([
            'from_cargo' => 'required|string',
            'to_cargo' => 'required|string|different:from_cargo',
            'exclude_barcodes' => 'nullable|string',
        ]);

        try {
            $rule->update([
                'from_cargo' => $request->from_cargo,
                'to_cargo' => $request->to_cargo,
                'exclude_barcodes' => $request->exclude_barcodes,
                'status' => 'pending' // Kuralı yeniden çalıştırmak için pending yapıyoruz
            ]);

            return redirect()->route('shipments.index')
                ->with('success', 'Kural başarıyla güncellendi.');
        } catch (\Exception $e) {
            return redirect()->route('shipments.index')
                ->with('error', 'Kural güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function destroyRule(CargoRule $rule)
    {
        try {
            $rule->delete();
            return redirect()->route('shipments.index')
                ->with('success', 'Kural başarıyla silindi.');
        } catch (\Exception $e) {
            return redirect()->route('shipments.index')
                ->with('error', 'Kural silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }
} 