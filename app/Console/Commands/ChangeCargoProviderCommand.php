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

                    //check if orderResponse->cargotracking number starts with 888
                    if(str_starts_with($orderResponse->cargoTrackingNumber, '888')){
                        $order->cargo_service_provider = CargoRule::CARGO_PROVIDERS[$rule->to_cargo];
                        $order->cargo_tracking_number = $orderResponse->cargoTrackingNumber;
                        $order->save();
                    }else{

                        TrendyolHelper::updateOrderCargoProvider($order, $rule->to_cargo);
                        sleep(2);
                        $orderResponse = TrendyolHelper::getOrderByPackageId($order->order_id, $store);
                        
                        $order->cargo_service_provider = CargoRule::CARGO_PROVIDERS[$rule->to_cargo];
                        $order->cargo_tracking_number = $orderResponse->cargoTrackingNumber;
                        $order->save();
                    }
                    

                    if($rule->to_cargo == "KOLAYGELSINMP"){
                        $KolayGelsinBarcodeResponse = \App\Helpers\KolayGelsinHelper::getBarcode($store,2, $order->cargo_tracking_number);
                        $KolayGelsinBarcode = $KolayGelsinBarcodeResponse['result']['BarcodeZpl'] ?? null;
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
