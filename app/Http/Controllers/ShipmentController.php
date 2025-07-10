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
use App\Helpers\TrendyolHelper;

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
                    $query->where('zpl_print_count', '>', 0);
                    break;
                case 'not_printed':
                    $query->where(function($q) {
                        $q->where('zpl_print_count', 0)
                          ->orWhereNull('zpl_print_count');
                    });
                    break;
            }
        }

        // Barkod filtresi
        if ($request->filled('barcode')) {
            $barcodes = is_array($request->barcode) ? array_filter($request->barcode) : [$request->barcode];
            if (!empty($barcodes)) {
                $query->whereHas('orderProducts.product', function($q) use ($barcodes) {
                    $q->whereIn('barcode', $barcodes);
                });
            }
        }

        // Ürün sayısı filtresi
        if ($request->filled('product_count')) {
            switch ($request->product_count) {
                case 'single':
                    // Tek ürün olan siparişler (toplam quantity = 1)
                    $query->whereIn('id', function($subQuery) {
                        $subQuery->select('order_id')
                            ->from('order_products')
                            ->groupBy('order_id')
                            ->havingRaw('SUM(quantity) = 1');
                    });
                    break;
                case 'multiple':
                    // Birden fazla ürün olan siparişler (toplam quantity > 1)
                    $query->whereIn('id', function($subQuery) {
                        $subQuery->select('order_id')
                            ->from('order_products')
                            ->groupBy('order_id')
                            ->havingRaw('SUM(quantity) > 1');
                    });
                    break;
            }
        }

        // Sıralama
        switch ($request->get('sort_by')) {
            case 'price_asc':
                $query->orderByRaw('CAST(total_price AS DECIMAL(10,2)) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('CAST(total_price AS DECIMAL(10,2)) DESC');
                break;
            case 'date_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'date_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'delivery_time_asc':
                $query->orderBy('agreed_delivery_date', 'asc');
                break;
            case 'delivery_time_desc':
                $query->orderBy('agreed_delivery_date', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = in_array($request->get('per_page'), [10, 50, 100, 500, 1000]) ? (int)$request->get('per_page') : 100;
        $orders = $query->paginate($perPage)->appends($request->except('page'));

        // Kargo firmalarını ve durumları filtre için al (sadece kullanıcının store'larından)
        $cargoProviders = Order::whereHas('store', function($q) {
                $q->where('user_id', auth()->id());
            })
            ->select('cargo_service_provider')
            ->whereNotNull('cargo_service_provider')
            ->distinct()
            ->pluck('cargo_service_provider');
            
        $statuses = Order::TYPES;

        // Tüm barkodları filtre için çek (ürün adı ve resmiyle birlikte)
        $barcodeProducts = \App\Models\Product::whereHas('store', function($q) {
            $q->where('user_id', auth()->id());
        })
        ->whereNotNull('barcode')
        ->get(['barcode', 'title', 'image_url'])
        ->unique('barcode')
        ->values();

        return view('shipments.index', compact('orders', 'cargoProviders', 'statuses', 'barcodeProducts'));
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
                $originalZplCode = $barcodeResponse['result']['BarcodeZpl'];
                
                // Ürün bilgilerini ZPL'ye ekle
                $enhancedZplCode = $this->addProductInfoToZpl($originalZplCode, $order);
                
                // ZPL verisini Order'a kaydet
                $order->update([
                    'zpl_barcode' => $enhancedZplCode
                ]);

                // ZPL görüntüsü oluşturma işlemi
                try {
                    // Mevcut ZPL image'ını temizle (varsa)
                    $order->clearMediaCollection('zpl_images');
                    
                    // ZPL formatını kontrol et
                    if (ZplGeneratorHelper::isValidZpl($enhancedZplCode ?? "")) {
                        // ZPL'yi PNG'ye çevir
                        $pngData = ZplGeneratorHelper::generatePngFromZpl($enhancedZplCode);
                        
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

            // Kullanıcının siparişlerini filtrele - sadece Kolay Gelsin Marketplace olanları
            $orders = Order::with('store')
                ->whereIn('id', $orderIds)
                ->whereHas('store', function($q) {
                    $q->where('user_id', auth()->id());
                })
                ->where('cargo_service_provider', 'Kolay Gelsin Marketplace')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uygun sipariş bulunamadı. Sadece "Kolay Gelsin Marketplace" kargo firmasına ait siparişler için ZPL oluşturulabilir.'
                ], 400);
            }

            foreach ($orders as $order) {
                try {
                    $store = $order->store;
                    if (!$store) {
                        $errors[] = "Sipariş {$order->order_id}: Mağaza bilgisi bulunamadı";
                        $errorCount++;
                        continue;
                    }

                    // Eğer ZPL kodu yoksa önce oluştur (tekil generateZPL mantığı)
                    if (empty($order->zpl_barcode)) {
                        // KolayGelsinHelper ile ZPL barcode oluştur
                        $barcodeResponse = KolayGelsinHelper::getBarcode($store, 2, $order->cargo_tracking_number);

                        if (!isset($barcodeResponse['result']) || !isset($barcodeResponse['result']['BarcodeZpl'])) {
                            $errors[] = "Sipariş {$order->order_id}: ZPL barcode oluşturulamadı - " . ($barcodeResponse['message'] ?? 'Bilinmeyen hata');
                            $errorCount++;
                            continue;
                        }

                        $originalZplCode = $barcodeResponse['result']['BarcodeZpl'];
                        
                        // Ürün bilgilerini ZPL'ye ekle
                        $enhancedZplCode = $this->addProductInfoToZpl($originalZplCode, $order);
                        
                        // ZPL verisini Order'a kaydet
                        $order->update([
                            'zpl_barcode' => $enhancedZplCode
                        ]);
                    }

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
                        
                        Log::info('Bulk ZPL image başarıyla oluşturuldu', ['order_id' => $order->id]);
                        
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
                    Log::error('Bulk ZPL generation failed for single order', [
                        'order_id' => $order->id,
                        'error' => $orderError->getMessage(),
                        'trace' => $orderError->getTraceAsString()
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
            Log::error('Bulk ZPL generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'Toplu işlem sırasında hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ZPL koduna ürün bilgilerini ekler
     */
    private function addProductInfoToZpl(string $originalZpl, Order $order): string
    {
        // ZPL'nin sonundaki ^XZ'yi bul ve kaldır
        $zplWithoutEnd = rtrim($originalZpl);
        if (str_ends_with($zplWithoutEnd, '^XZ')) {
            $zplWithoutEnd = substr($zplWithoutEnd, 0, -3);
        }

        // Lines verisinden ürün bilgilerini al
        $linesData = json_decode($order->lines, true);
        
        // CRITICAL VALIDATION: Log which order gets which products
        Log::info('ZPL Product Info Addition', [
            'order_id' => $order->id,
            'order_package_id' => $order->order_id,
            'cargo_tracking_number' => $order->cargo_tracking_number,
            'lines_count' => count($linesData ?? []),
            'product_names' => array_map(function($line) {
                return $line['productName'] ?? 'N/A';
            }, $linesData ?? []),
            'product_barcodes' => array_map(function($line) {
                return $line['barcode'] ?? $line['sku'] ?? 'N/A';
            }, $linesData ?? [])
        ]);
        
        if (empty($linesData)) {
            // Ürün yoksa orijinal ZPL'yi döndür
            return $originalZpl;
        }

        // Ürün bilgileri için ZPL kodları
        $productZpl = '';
        
        // Sadece çizgi - başlık yok
        $productZpl .= '^FO10,700^GB600,3,3,B,0^FS'; // Kalın çizgi
        
        $yPosition = 720; // Çizgiden hemen sonra başla
        $lineHeight = 85; // Satır yüksekliği artırıldı (daha fazla alan)
        // Sayfa sınırını kaldırıyoruz - tüm ürünler gösterilecek
        
        foreach ($linesData as $index => $productData) {
            // Sayfa sınırı kontrolü kaldırıldı - tüm ürünler yazılacak
            
            // Ürün bilgilerini al
            $productName = $productData['productName'] ?? 'Ürün Adı Yok';
            $quantity = $productData['quantity'] ?? 1;
            $barcode = $productData['barcode'] ?? ($productData['sku'] ?? 'Barkod Yok');
            $productSize = $productData['productSize'] ?? '';
            $productColor = $productData['productColor'] ?? '';
            
            // Birden fazla ürün varsa ayırıcı noktalı çizgi ekle (ilk ürün hariç)
            if ($index > 0) {
                $separatorY = $yPosition - 10;
                $productZpl .= "^FO20,{$separatorY}^GB580,1,1,B,1^FS"; // İnce noktalı çizgi
            }
            
            // Ürün adını satırlara böl (her satır maksimum 70 karakter)
            $maxCharsPerLine = 70;
            $productNameLines = [];
            
            if (strlen($productName) <= $maxCharsPerLine) {
                // Tek satıra sığıyor
                $productNameLines[] = $productName;
            } else {
                // Çok uzun, satırlara böl
                $words = explode(' ', $productName);
                $currentLine = '';
                
                foreach ($words as $word) {
                    if (strlen($currentLine . ' ' . $word) <= $maxCharsPerLine) {
                        $currentLine .= ($currentLine ? ' ' : '') . $word;
                    } else {
                        if ($currentLine) {
                            $productNameLines[] = $currentLine;
                            $currentLine = $word;
                        } else {
                            // Tek kelime çok uzunsa, zorla böl
                            $productNameLines[] = substr($word, 0, $maxCharsPerLine - 3) . '...';
                            $currentLine = '';
                        }
                    }
                }
                
                if ($currentLine) {
                    $productNameLines[] = $currentLine;
                }
                
                // Maksimum 2 satır göster
                if (count($productNameLines) > 2) {
                    $productNameLines = array_slice($productNameLines, 0, 2);
                    $productNameLines[1] = substr($productNameLines[1], 0, $maxCharsPerLine - 3) . '...';
                }
            }
            
            // Ürün adı satırlarını yazdır
            $currentY = $yPosition;
            foreach ($productNameLines as $lineIndex => $line) {
                $productZpl .= "^FO20,{$currentY}^A0,22,22^FD{$line}^FS";
                $currentY += 24; // Satır arası boşluk
            }
            
            // İkinci kısım için Y pozisyonunu ayarla (ürün adı satır sayısına göre)
            $secondLineY = $yPosition + (count($productNameLines) * 24) + 4;
            
            // ADET - çok büyük ve sol tarafta
            $productZpl .= "^FO20,{$secondLineY}^A0,35,35^FDADET: {$quantity}^FS";
            
            // Barkod - sağ tarafta, büyük font
            $productZpl .= "^FO380,{$secondLineY}^A0,28,28^FD{$barcode}^FS";
            
            // Renk ve ebat bilgisi - ortada, ADET ile Barkod arasında
            $extraInfo = [];
            if (!empty($productSize)) $extraInfo[] = $productSize;
            if (!empty($productColor)) $extraInfo[] = $productColor;
            
            if (!empty($extraInfo)) {
                $extraInfoText = implode(' | ', $extraInfo); // Pipe ile ayır
                if (strlen($extraInfoText) > 25) {
                    $extraInfoText = substr($extraInfoText, 0, 22) . '...';
                }
                // Ortada konumlandır
                $productZpl .= "^FO180,{$secondLineY}^A0,20,20^FD{$extraInfoText}^FS";
            }
            
            // Dinamik satır yüksekliği (ürün adı satır sayısına göre)
            $dynamicLineHeight = $lineHeight + (count($productNameLines) > 1 ? 24 : 0);
            $yPosition += $dynamicLineHeight;
        }
        
        // Artık tüm ürünler gösteriliyor - "kalan ürün" kısmı kaldırıldı
        
        // ZPL'yi birleştir ve sonlandır
        return $zplWithoutEnd . $productZpl . '^XZ';
    }

    /**
     * Seçili siparişleri KolayGelsin Marketplace'e çevirir
     */
    public function convertToKolayGelsin(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:orders,id'
        ]);

        try {
            $orderIds = $request->input('order_ids');
            $orders = Order::with(['store'])
                ->whereIn('id', $orderIds)
                ->whereHas('store', function($q) {
                    $q->where('user_id', auth()->id());
                })
                ->where('cargo_service_provider', '!=', 'Kolay Gelsin Marketplace')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Çevrilecek uygun sipariş bulunamadı.'
                ]);
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($orders as $order) {
                try {
                    // Trendyol API ile kargo firmasını güncelle
                    TrendyolHelper::updateOrderCargoProvider($order, 'KOLAYGELSINMP');
                    
                    // Database'de de güncelle
                    $order->update([
                        'cargo_service_provider' => 'Kolay Gelsin Marketplace',
                        'zpl_barcode' => null, // ZPL'yi sıfırla, yeniden oluşturulacak
                        'zpl_barcode_type' => null
                    ]);
                    
                    $successCount++;
                    
                    Log::info('Sipariş KolayGelsin\'e çevrildi', [
                        'order_id' => $order->id,
                        'order_package_id' => $order->order_id,
                        'user_id' => auth()->id()
                    ]);
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = "Sipariş {$order->order_id}: " . $e->getMessage();
                    $errors[] = $errorMessage;
                    
                    Log::error('KolayGelsin çevirme hatası', [
                        'order_id' => $order->id,
                        'order_package_id' => $order->order_id,
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id()
                    ]);
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
            Log::error('KolayGelsin toplu çevirme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Toplu çevirme sırasında hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
} 