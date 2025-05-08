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
    protected $signature = 'change:cargo {--store_id= : Belirli bir mağazanın siparişlerini işle}';

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
        
        $query = CargoRule::query();
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $cargoRules = $query->get();

        if ($cargoRules->isEmpty()) {
            $this->error('İşlenecek kargo kuralı bulunamadı.');
            return;
        }

        $this->info('Kargo kuralları işleniyor...');
        $bar = $this->output->createProgressBar($cargoRules->count());
        $bar->start();

        foreach ($cargoRules as $rule) {
            $store = Store::find($rule->store_id);
            if (!$store) {
                $this->warn("Mağaza bulunamadı: {$rule->store_id}");
                continue;
            }

            $orders = Order::where('store_id', $store->id)
                ->whereIn('status', ['Created', 'Picking', 'Invoiced'])
                ->get();

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

                try {
                    // Kargo firmasını güncelle
                    TrendyolHelper::updateOrderCargoProvider($order, $rule->to_cargo);
                    
                    // Kuralı güncelle
                    $rule->update([
                        'status' => 'executed',
                        'result' => 'Başarılı: Sipariş ' . $order->order_id . ' için kargo firması güncellendi.',
                        'executed_at' => now()
                    ]);

                    $this->info("\nSipariş güncellendi: {$order->order_id} - {$rule->from_cargo} -> {$rule->to_cargo}");
                    
                    Log::info('Kargo kuralı başarıyla uygulandı', [
                        'order_id' => $order->order_id,
                        'rule_id' => $rule->id,
                        'store_id' => $store->id,
                        'from_cargo' => $rule->from_cargo,
                        'to_cargo' => $rule->to_cargo
                    ]);
                } catch (\Exception $e) {
                    $rule->update([
                        'status' => 'failed',
                        'result' => 'Hata: ' . $e->getMessage(),
                        'executed_at' => now()
                    ]);

                    $this->error("\nHata: Sipariş {$order->order_id} için kargo firması güncellenemedi: " . $e->getMessage());
                    
                    Log::error('Kargo kuralı uygulanırken hata oluştu', [
                        'order_id' => $order->order_id,
                        'rule_id' => $rule->id,
                        'store_id' => $store->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('İşlem tamamlandı!');
    }
}
