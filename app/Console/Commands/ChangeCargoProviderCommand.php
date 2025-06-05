<?php

namespace App\Console\Commands;

use App\Helpers\TrendyolHelper;
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
                ->get();
            // dd($orders);
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
                    if($rule->to_cargo == "KOLAYGELSINMP"){
                        $KolayGelsinBarcode = \App\Helpers\KolayGelsinHelper::getBarcode($store,2, $order->cargo_tracking_number);
                    }else{
                        $KolayGelsinBarcode = null;
                    }

                    $affectedOrdersForThisRule++;
                    $totalAffectedOrders++;
                    
                    $order->cargo_service_provider = CargoRule::CARGO_PROVIDERS[$rule->to_cargo];
                    $order->zpl_barcode = $KolayGelsinBarcode;
                    $order->zpl_barcode_type = 2;
                    
                    $order->save();
                    
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
}
