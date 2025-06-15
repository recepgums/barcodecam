<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Order extends Model  implements HasMedia
{
    use HasFactory,InteractsWithMedia;

    const TYPES = [
        'Created' => 'Oluşturuldu',
        'Picking' => 'Toplama',
        'Invoiced' => 'Faturalı',
        'Shipped' => 'Gönderildi',
        'Cancelled' => 'İptal edildi',
        'Delivered' => 'Teslim edilmiş',
        'UnDelivered' => 'Teslim Edilmedi',
        'Returned' => 'İade',
        'Repack' => 'Yeniden paketle',
        'UnPacked' => 'Paketlenmemiş',
        'UnSupplied' => 'Tedarik Edilmedi',
    ];
    protected $fillable = [
        'user_id',
        'store_id',
        'customer_name',
        'address',
        'order_id',
        'cargo_tracking_number',
        'cargo_service_provider',
        'lines',
        'order_date',
        'agreed_delivery_date',
        'status',
        'total_price',
        'zpl_barcode',
        'zpl_barcode_type',
        'zpl_print_count',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'agreed_delivery_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function products()
    {
        return $this->hasManyThrough(Product::class, OrderProduct::class, 'order_id', 'id', 'id', 'product_id');
    }

    /**
     * Kargoya vermek için kalan süreyi hesapla
     */
    public function getRemainingDeliveryTimeAttribute()
    {
        if (!$this->agreed_delivery_date) {
            return null;
        }
        
        $now = now();
        $agreedDate = \Carbon\Carbon::parse($this->agreed_delivery_date);
        
        if ($agreedDate->isPast()) {
            return 'Süre doldu';
        }
        
        $diff = $now->diff($agreedDate);
        
        // Gün, saat ve dakikayı her zaman göster
        $days = $diff->days;
        $hours = $diff->h;
        $minutes = $diff->i;
        
        return sprintf("%d gün %d saat %02d dakika", $days, $hours, $minutes);
    }

    /**
     * ZPL barcode resmi için media collection
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('zpl_images')
            ->singleFile() // Her sipariş için sadece 1 resim
            ->acceptsMimeTypes(['image/png']);
    }

    /**
     * ZPL barcode resmini al
     */
    public function getZplImageUrl(): ?string
    {
        $media = $this->getFirstMedia('zpl_images');
        return $media ? $media->getUrl() : null;
    }
}
