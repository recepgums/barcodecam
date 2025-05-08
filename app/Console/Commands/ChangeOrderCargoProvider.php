<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Helpers\TrendyolHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChangeOrderCargoProvider extends Command
{
    protected $signature = 'orders:change-cargo-provider \
        {from : Kaynak kargo firması} \
        {to : Hedef kargo firması} \
        {--exclude= : Hariç tutulacak barkodlar (virgülle ayrılmış)}';

    protected $description = 'Belirtilen kargo firmasındaki siparişleri, hariç tutulan barkodlar dışında başka bir kargo firmasına çevirir ve Trendyol API ile günceller.';

    public function handle(): int
    {
        $from = $this->argument('from');
        $to = $this->argument('to');
        $excludeBarcodes = collect(explode(',', (string)$this->option('exclude')))->filter()->map(fn($b) => trim($b))->all();

        $this->info("Kargo firması '$from' olan siparişler '$to' olarak değiştirilecek.");
        if ($excludeBarcodes) {
            $this->info('Hariç tutulan barkodlar: ' . implode(', ', $excludeBarcodes));
        }

        $orders = Order::where('cargo_service_provider', $from)
            ->whereHas('orderProducts', function ($q) use ($excludeBarcodes) {
                if ($excludeBarcodes) {
                    $q->whereNotIn('product_id', function($query) use ($excludeBarcodes) {
                        $query->select('id')->from('products')->whereIn('barcode', $excludeBarcodes);
                    });
                }
            })
            ->get();

        $updated = 0;
        DB::beginTransaction();
        try {
            foreach ($orders as $order) {
                $order->cargo_service_provider = $to;
                $order->save();
                // Trendyol API ile güncelle
                try {
                    TrendyolHelper::updateOrderCargoProvider($order, $to);
                } catch (\Exception $e) {
                    Log::error('Trendyol API kargo güncelleme hatası', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                    $this->error("[API] Sipariş #{$order->order_id} güncellenemedi: {$e->getMessage()}");
                    continue;
                }
                $updated++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('İşlem sırasında hata oluştu: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Toplam {$updated} sipariş güncellendi.");
        return self::SUCCESS;
    }
} 