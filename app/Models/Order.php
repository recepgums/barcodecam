<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

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
        'customer_name',
        'address',
        'order_id',
        'cargo_tracking_number',
        'cargo_service_provider',
        'lines',
        'order_date',
        'status',
        'total_price',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
