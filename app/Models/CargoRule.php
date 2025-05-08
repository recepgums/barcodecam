<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargoRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'from_cargo',
        'to_cargo',
        'exclude_barcodes', // virgülle ayrılmış string
        'status', // pending, executed, failed
        'result', // işlem sonucu veya hata mesajı
        'executed_at',
    ];

    const CARGO_PROVIDERS = [
        "TEXMP"=>"Trendyol Express Marketplace",
        "YKMP"=>"Yurtiçi Marketplace",
        "ARASMP"=>"Aras Marketplace",
        "SURATMP"=>"Surat Marketplace",
        "HOROZMP"=>"Horoz Marketplace",
        "MNGMP"=>"MNG Marketplace",
        "PTTMP"=>"PTT Marketplace",
        "CEVAMP"=>"Cevahir Marketplace",
        "KOLAYGELSINMP"=>"Kolay Gelsin Marketplace",
        "KOLAYGELSINMP"=>"Sendeo Marketplace"
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
} 