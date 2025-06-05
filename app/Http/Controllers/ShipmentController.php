<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CargoRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Helpers\KolayGelsinHelper;
use App\Helpers\ZplGeneratorHelper;

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
            // Status array olarak gelirse whereIn kullan
            if (is_array($request->status)) {
                $statuses = array_filter($request->status); // Boş değerleri filtrele
                if (!empty($statuses)) {
                    $query->whereIn('status', $statuses);
                }
            } else {
                // String olarak gelirse tek status filtresi
                $query->where('status', $request->status);
            }
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

        // Yazdırma durumu filtresi
        if ($request->filled('print_status')) {
            switch ($request->print_status) {
                case 'printed':
                    // Yazdırılmış (print count > 0)
                    $query->where('zpl_print_count', '>', 0);
                    break;
                case 'not_printed':
                    // Yazdırılmamış (print count = 0 veya null)
                    $query->where(function($q) {
                        $q->where('zpl_print_count', 0)
                          ->orWhereNull('zpl_print_count');
                    });
                    break;
                // 'all' durumunda hiçbir şey eklemeye gerek yok
            }
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
            \App\Helpers\TrendyolHelper::updateOrderCargoProvider($order, array_search($newProvider, CargoRule::CARGO_PROVIDERS));

            $orderResponse = \App\Helpers\TrendyolHelper::getOrderByPackageId($order->order_id, $order->store);
            $order->cargo_service_provider = $newProvider;
            $order->cargo_tracking_number = $orderResponse->cargoTrackingNumber;
            $order->save();
            DB::commit();

            return redirect()->back()->with('success', 'Kargo firması başarıyla güncellendi ve Trendyol API ile senkronize edildi   ');
        } catch (\Exception $e) {
            Log::error('Single update failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
                $zplCode = $barcodeResponse['result']['BarcodeZpl'];
                
                // ZPL verisini Order'a kaydet
                $order->update([
                    'zpl_barcode' => $zplCode
                ]);

                // ZPL görüntüsü oluşturma işlemi
                try {
                    // Mevcut ZPL image'ını temizle (varsa)
                    $order->clearMediaCollection('zpl_images');
                    
                    // ZPL formatını kontrol et
                    if (ZplGeneratorHelper::isValidZpl($zplCode ?? "")) {
                        // ZPL'yi PNG'ye çevir
                        $pngData = ZplGeneratorHelper::generatePngFromZpl($zplCode);
                        
                        if ($pngData) {
                            // PNG'yi geçici dosya olarak kaydet
                            $tempPath = tempnam(sys_get_temp_dir(), 'zpl_');
                            if ($tempPath !== false) {
                                // .png uzantısı ekle
                                $tempPngPath = $tempPath . '.png';
                                rename($tempPath, $tempPngPath);
                                
                                // PNG verisini dosyaya yaz
                                $bytesWritten = file_put_contents($tempPngPath, $pngData);
                                if ($bytesWritten !== false && file_exists($tempPngPath)) {
                                    try {
                                        // Spatie Media Library ile kalıcı olarak kaydet
                                        $order->addMedia($tempPngPath)
                                            ->usingName("ZPL Barcode - Order {$order->order_id}")
                                            ->usingFileName("zpl_barcode_{$order->id}_" . time() . '.png')
                                            ->toMediaCollection('zpl_images');
                                            
                                        Log::info('ZPL image başarıyla kaydedildi', ['order_id' => $order->id]);
                                    } finally {
                                        // Geçici dosyayı güvenli şekilde sil
                                        if (file_exists($tempPngPath)) {
                                            try {
                                                unlink($tempPngPath);
                                            } catch (\Exception $unlinkError) {
                                                Log::warning('Geçici dosya silinemedi', [
                                                    'file' => $tempPngPath,
                                                    'error' => $unlinkError->getMessage()
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $imageError) {
                    // ZPL görüntü oluşturma hatası olsa bile ZPL kodu kaydedildi, devam et
                    Log::warning('ZPL görüntüsü oluşturulamadı ancak ZPL kodu kaydedildi', [
                        'order_id' => $order->id,
                        'error' => $imageError->getMessage()
                    ]);
                }

                return redirect()->back()->with('success', 'ZPL barcode başarıyla oluşturuldu ve kaydedildi.');
            } else {
                return redirect()->back()->with('error', 'ZPL barcode oluşturulamadı: ' . ($barcodeResponse['message'] ?? 'Bilinmeyen hata'));
            }

        } catch (\Exception $e) {
            Log::error('ZPL generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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

    /**
     * ZPL kodunu PNG görüntüye çevirir ve local olarak kaydeder
     */
    public function generateZplImage(Request $request, Order $order)
    {
        try {
            // ZPL kodunun varlığını kontrol et
            if (empty($order->zpl_barcode)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Bu sipariş için ZPL kodu bulunamadı.'
                ], 400);
            }

            // ZPL formatını kontrol et
            if (!ZplGeneratorHelper::isValidZpl($order->zpl_barcode)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Geçersiz ZPL formatı.'
                ], 400);
            }

            // Mevcut ZPL image'ı kontrol et
            $existingImageUrl = $order->getZplImageUrl();
            if ($existingImageUrl) {
                return response()->json([
                    'success' => true,
                    'image_url' => $existingImageUrl,
                    'message' => 'Mevcut ZPL görüntüsü kullanıldı.'
                ]);
            }

            // ZPL'yi PNG'ye çevir
            $pngData = ZplGeneratorHelper::generatePngFromZpl($order->zpl_barcode);
            
            if (!$pngData) {
                return response()->json([
                    'success' => false, 
                    'message' => 'ZPL görüntüsü oluşturulamadı.'
                ], 500);
            }

            // PNG'yi geçici dosya olarak kaydet
            $tempPath = tempnam(sys_get_temp_dir(), 'zpl_');
            if ($tempPath === false) {
                throw new \Exception('Geçici dosya oluşturulamadı.');
            }
            
            // .png uzantısı ekle
            $tempPngPath = $tempPath . '.png';
            rename($tempPath, $tempPngPath);
            
            // PNG verisini dosyaya yaz
            $bytesWritten = file_put_contents($tempPngPath, $pngData);
            if ($bytesWritten === false) {
                throw new \Exception('PNG verisi geçici dosyaya yazılamadı.');
            }

            // Dosyanın var olduğunu kontrol et
            if (!file_exists($tempPngPath)) {
                throw new \Exception('Geçici PNG dosyası oluşturulamadı.');
            }

            try {
                // Spatie Media Library ile kalıcı olarak kaydet
                $media = $order->addMedia($tempPngPath)
                    ->usingName("ZPL Barcode - Order {$order->order_id}")
                    ->usingFileName("zpl_barcode_{$order->id}_" . time() . '.png')
                    ->toMediaCollection('zpl_images');
            } finally {
                // Geçici dosyayı güvenli şekilde sil
                if (file_exists($tempPngPath)) {
                    try {
                        unlink($tempPngPath);
                    } catch (\Exception $unlinkError) {
                        Log::warning('Geçici dosya silinemedi', [
                            'file' => $tempPngPath,
                            'error' => $unlinkError->getMessage()
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'image_url' => $media->getUrl(),
                'message' => 'ZPL görüntüsü başarıyla oluşturuldu ve kaydedildi.'
            ]);

        } catch (\Exception $e) {
            Log::error('ZPL image generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'Bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toplu ZPL kodlarını PNG görüntüye çevirir ve local olarak kaydeder
     */
    public function generateBulkZplImages(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|integer|exists:orders,id'
        ]);

        try {
            $orderIds = $request->input('order_ids');
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // Kullanıcının siparişlerini filtrele
            $orders = Order::whereIn('id', $orderIds)
                ->whereHas('store', function($q) {
                    $q->where('user_id', auth()->id());
                })
               /*  ->where('cargo_service_provider', 'Kolay Gelsin Marketplace')
                ->whereIn('status', ['Created', 'Picking', 'Invoiced']) */
                ->whereNotNull('zpl_barcode')
                ->get();

                if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uygun sipariş bulunamadı. Siparişlerin Kolay Gelsin Marketplace kargo firmasına ait, uygun durumda ve ZPL koduna sahip olması gerekir.'
                ], 400);
            }

            foreach ($orders as $order) {
                try {
                    // ZPL formatını kontrol et
                    if (!ZplGeneratorHelper::isValidZpl($order->zpl_barcode)) {
                        $errors[] = "Sipariş {$order->order_id}: Geçersiz ZPL formatı";
                        $errorCount++;
                        continue;
                    }

                    // Mevcut ZPL image'ını temizle (varsa)
                    $order->clearMediaCollection('zpl_images');

                    // ZPL'yi PNG'ye çevir
                    $pngData = ZplGeneratorHelper::generatePngFromZpl($order->zpl_barcode);
                    
                    if (!$pngData) {
                        $errors[] = "Sipariş {$order->order_id}: ZPL görüntüsü oluşturulamadı";
                        $errorCount++;
                        continue;
                    }

                    // PNG'yi geçici dosya olarak kaydet
                    $tempPath = tempnam(sys_get_temp_dir(), 'zpl_bulk_');
                    if ($tempPath === false) {
                        $errors[] = "Sipariş {$order->order_id}: Geçici dosya oluşturulamadı";
                        $errorCount++;
                        continue;
                    }
                    
                    // .png uzantısı ekle
                    $tempPngPath = $tempPath . '.png';
                    rename($tempPath, $tempPngPath);
                    
                    // PNG verisini dosyaya yaz
                    $bytesWritten = file_put_contents($tempPngPath, $pngData);
                    if ($bytesWritten === false) {
                        $errors[] = "Sipariş {$order->order_id}: PNG verisi dosyaya yazılamadı";
                        $errorCount++;
                        continue;
                    }

                    // Dosyanın var olduğunu kontrol et
                    if (!file_exists($tempPngPath)) {
                        $errors[] = "Sipariş {$order->order_id}: PNG dosyası oluşturulamadı";
                        $errorCount++;
                        continue;
                    }

                    try {
                        // Spatie Media Library ile kalıcı olarak kaydet
                        $media = $order->addMedia($tempPngPath)
                            ->usingName("ZPL Barcode - Order {$order->order_id}")
                            ->usingFileName("zpl_barcode_{$order->id}_" . time() . '.png')
                            ->toMediaCollection('zpl_images');
                        
                        $successCount++;
                    } catch (\Exception $mediaError) {
                        $errors[] = "Sipariş {$order->order_id}: Media kaydetme hatası - " . $mediaError->getMessage();
                        $errorCount++;
                    } finally {
                        // Geçici dosyayı güvenli şekilde sil
                        if (file_exists($tempPngPath)) {
                            try {
                                unlink($tempPngPath);
                            } catch (\Exception $unlinkError) {
                                Log::warning('Toplu işlemde geçici dosya silinemedi', [
                                    'file' => $tempPngPath,
                                    'order_id' => $order->id,
                                    'error' => $unlinkError->getMessage()
                                ]);
                            }
                        }
                    }

                } catch (\Exception $orderError) {
                    Log::error('Bulk ZPL image generation failed for single order', [
                        'order_id' => $order->id,
                        'error' => $orderError->getMessage()
                    ]);
                    
                    $errors[] = "Sipariş {$order->order_id}: " . $orderError->getMessage();
                    $errorCount++;
                }
            }

            return response()->json([
                'success' => true,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
                'message' => "İşlem tamamlandı. Başarılı: {$successCount}, Hatalı: {$errorCount}"
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk ZPL image generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'Toplu işlem sırasında hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
} 