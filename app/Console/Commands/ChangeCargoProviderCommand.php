<?php

namespace App\Console\Commands;

use App\Helpers\TrendyolHelper;
use App\Helpers\ZplGeneratorHelper;
use App\Models\CargoRule;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ChangeCargoProviderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:cargo {--store_id= : Belirli bir mağazanın siparişlerini işle} {--rule_id= : Belirli bir kuralı çalıştır}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kargo kurallarına göre siparişlerin kargo firmalarını günceller';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeId = $this->option('store_id');
        $ruleId = $this->option('rule_id');
        
        $query = CargoRule::query();
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        if ($ruleId) {
            $query->where('id', $ruleId);
        }
        
        $cargoRules = $query->get();

        if ($cargoRules->isEmpty()) {
            $this->error('İşlenecek kargo kuralı bulunamadı.');
            return;
        }

        $this->info('Kargo kuralları işleniyor...');
        $bar = $this->output->createProgressBar($cargoRules->count());
        $bar->start();

        $totalAffectedOrders = 0;
        $totalSuccessfulRules = 0;
        $totalFailedRules = 0;
        
        foreach ($cargoRules as $rule) {
            $store = Store::find($rule->store_id);
            if (!$store) {
                $this->warn("Mağaza bulunamadı: {$rule->store_id}");
                continue;
            }

            $orders = Order::with('products')->where('store_id', $store->id)
                ->whereIn('status', ['Created', 'Picking', 'Invoiced'])
                ->where('cargo_service_provider', '!=', CargoRule::CARGO_PROVIDERS[$rule->to_cargo])
                ->whereNull('zpl_barcode')
                ->get();


            $affectedOrdersForThisRule = 0;
            $failedOrdersForThisRule = 0;
            foreach ($orders as $order) {
                //we should find the value and assign the key to cargoShortName
                $cargoShortName = array_search($order->cargo_service_provider, CargoRule::CARGO_PROVIDERS);
                // Kargo kuralı ile siparişin kargo firması eşleşiyor mu kontrol et
                if ($cargoShortName !== $rule->from_cargo) {
                    continue;
                }

                // Hariç tutulan barkodları kontrol et
                if ($rule->exclude_barcodes) {
                    $excludedBarcodes = explode(',', $rule->exclude_barcodes);
                    $orderBarcodes = $order->products->pluck('barcode')->toArray();
                    
                    if (array_intersect($excludedBarcodes, $orderBarcodes)) {
                        continue;
                    }
                }
                // Dahil edilen barkodları kontrol et
                if ($rule->include_barcodes) {
                    $includedBarcodes = explode(',', $rule->include_barcodes);
                    $orderBarcodes = $order->products->pluck('barcode')->toArray();

                    if (!array_intersect($includedBarcodes, $orderBarcodes)) {
                        continue;
                    }
                }

                try {
                    // Kargo firmasını güncelle
                    TrendyolHelper::updateOrderCargoProvider($order, $rule->to_cargo);
                  
                    $orderResponse = TrendyolHelper::getOrderByPackageId($order->order_id, $store);

                    // DEBUG: Trendyol'dan gelen response'u incele
                    dd([
                        'debug_point' => 'FIRST_API_CALL',
                        'order_id' => $order->order_id,
                        'order_db_id' => $order->id,
                        'current_cargo_provider' => $order->cargo_service_provider,
                        'current_tracking_number' => $order->cargo_tracking_number,
                        'target_cargo' => $rule->to_cargo,
                        'api_response' => $orderResponse,
                        'api_response_tracking' => $orderResponse->cargoTrackingNumber ?? 'NOT_SET',
                        'api_response_provider' => $orderResponse->cargoProviderName ?? 'NOT_SET'
                    ]);

                    //check if orderResponse->cargotracking number starts with 888
                    if(str_starts_with($orderResponse->cargoTrackingNumber, '888')){
                        $order->cargo_service_provider = CargoRule::CARGO_PROVIDERS[$rule->to_cargo];
                        $order->cargo_tracking_number = $orderResponse->cargoTrackingNumber;
                        $order->save();
                    }else{

                        TrendyolHelper::updateOrderCargoProvider($order, $rule->to_cargo);
                        sleep(2);
                        $orderResponse = TrendyolHelper::getOrderByPackageId($order->order_id, $store);
                        
                        // DEBUG: İkinci API çağrısından sonra
                        dd([
                            'debug_point' => 'SECOND_API_CALL',
                            'order_id' => $order->order_id,
                            'order_db_id' => $order->id,
                            'target_cargo' => $rule->to_cargo,
                            'api_response' => $orderResponse,
                            'api_response_tracking' => $orderResponse->cargoTrackingNumber ?? 'NOT_SET',
                            'api_response_provider' => $orderResponse->cargoProviderName ?? 'NOT_SET'
                        ]);
                        
                        $order->cargo_service_provider = CargoRule::CARGO_PROVIDERS[$rule->to_cargo];
                        $order->cargo_tracking_number = $orderResponse->cargoTrackingNumber;
                        $order->save();
                    }
                    

                    if($rule->to_cargo == "KOLAYGELSINMP"){
                        $KolayGelsinBarcodeResponse = \App\Helpers\KolayGelsinHelper::getBarcode($store,2, $order->cargo_tracking_number);
                        $originalZplCode = $KolayGelsinBarcodeResponse['result']['BarcodeZpl'] ?? null;
                        
                        // Ürün bilgilerini ZPL'ye ekle (ShipmentController'daki aynı mantık)
                        if ($originalZplCode) {
                            $enhancedZplCode = $this->addProductInfoToZpl($originalZplCode, $order);
                            $KolayGelsinBarcode = $enhancedZplCode;
                        } else {
                            $KolayGelsinBarcode = null;
                        }
                    }else{
                        $KolayGelsinBarcode = null;
                    }

                    $affectedOrdersForThisRule++;
                    $totalAffectedOrders++;
                    
                    $order->cargo_service_provider = CargoRule::CARGO_PROVIDERS[$rule->to_cargo];
                    $order->zpl_barcode = $KolayGelsinBarcode;
                    $order->zpl_barcode_type = 2;
                    
                    $order->save();

                    // Eğer Kolay Gelsin Marketplace'e çevrildiyse ve ZPL kodu varsa, görüntüsünü de oluştur
                  /*   if ($rule->to_cargo == "KOLAYGELSINMP" && $KolayGelsinBarcode) {
                        $this->generateZplImageForOrder($order, $KolayGelsinBarcode);
                    } */
                    
                    $this->info("\nSipariş güncellendi: {$order->order_id} - {$rule->from_cargo} -> {$rule->to_cargo}");
                } catch (\Exception $e) {
                    $failedOrdersForThisRule++;
                    
                    $this->error("\nHata: Sipariş {$order->order_id} için kargo firması güncellenemedi: " . $e->getMessage());
                }
            }

            // Kuralı güncelle
            if ($affectedOrdersForThisRule > 0) {
                $resultMessage = "Başarılı: {$affectedOrdersForThisRule} sipariş güncellendi.";
                if ($failedOrdersForThisRule > 0) {
                    $resultMessage .= " {$failedOrdersForThisRule} sipariş güncellenemedi.";
                }
                $rule->update([
                    'status' => 'executed',
                    'result' => $resultMessage,
                    'executed_at' => now()
                ]);
                $totalSuccessfulRules++;
            } elseif ($failedOrdersForThisRule > 0) {
                $rule->update([
                    'status' => 'failed',
                    'result' => "Hata: {$failedOrdersForThisRule} sipariş güncellenemedi.",
                    'executed_at' => now()
                ]);
                $totalFailedRules++;
            } else {
                $rule->update([
                    'status' => 'executed',
                    'result' => 'Uygulanacak sipariş bulunamadı.',
                    'executed_at' => now()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("İşlem tamamlandı! {$totalAffectedOrders} sipariş güncellendi, {$totalSuccessfulRules} kural başarılı.");
    }

    /**
     * ZPL koduna ürün bilgilerini ekler (ShipmentController'daki aynı method)
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
        Log::info('ZPL Product Info Addition (Command)', [
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
     * Verilen Order için ZPL görüntüsü oluşturur ve kaydeder
     */
    private function generateZplImageForOrder(Order $order, string $zplCode): void
    {
        try {
            // ZPL formatını kontrol et
            if (!ZplGeneratorHelper::isValidZpl($zplCode)) {
                $this->warn("Geçersiz ZPL formatı: {$order->order_id}");
                return;
            }

            // ZPL'yi PNG'ye çevir
            $pngData = ZplGeneratorHelper::generatePngFromZpl($zplCode);
            
            if (!$pngData) {
                $this->warn("ZPL PNG'ye çevrilemedi: {$order->order_id}");
                return;
            }

            // PNG'yi geçici dosya olarak kaydet
            $tempPath = tempnam(sys_get_temp_dir(), 'zpl_console_');
            if ($tempPath === false) {
                $this->warn("Geçici dosya oluşturulamadı: {$order->order_id}");
                return;
            }

            // .png uzantısı ekle
            $tempPngPath = $tempPath . '.png';
            rename($tempPath, $tempPngPath);
            
            // PNG verisini dosyaya yaz
            $bytesWritten = file_put_contents($tempPngPath, $pngData);
            if ($bytesWritten === false || !file_exists($tempPngPath)) {
                $this->warn("PNG dosyası yazılamadı: {$order->order_id}");
                return;
            }

            try {
                // Mevcut ZPL image'ını temizle (varsa)
                $order->clearMediaCollection('zpl_images');
                
                // Spatie Media Library ile kalıcı olarak kaydet
                $order->addMedia($tempPngPath)
                    ->usingName("ZPL Barcode - Order {$order->order_id}")
                    ->usingFileName("zpl_barcode_{$order->id}_" . time() . '.png')
                    ->toMediaCollection('zpl_images');
                    
                $this->info("ZPL görüntüsü oluşturuldu: {$order->order_id}");
            } finally {
                // Geçici dosyayı güvenli şekilde sil
                if (file_exists($tempPngPath)) {
                    try {
                        unlink($tempPngPath);
                    } catch (\Exception $unlinkError) {
                        Log::warning('Console: Geçici dosya silinemedi', [
                            'file' => $tempPngPath,
                            'error' => $unlinkError->getMessage()
                        ]);
                    }
                }
            }
        } catch (\Exception $imageError) {
            // ZPL görüntü oluşturma hatası olsa bile işleme devam et
            Log::warning('Console: ZPL görüntüsü oluşturulamadı', [
                'order_id' => $order->id,
                'error' => $imageError->getMessage()
            ]);
            $this->warn("ZPL görüntüsü oluşturulamadı: {$order->order_id} - {$imageError->getMessage()}");
        }
    }
}
