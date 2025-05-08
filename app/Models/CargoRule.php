<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_cargo',
        'to_cargo',
        'exclude_barcodes', // virgülle ayrılmış string
        'status', // pending, executed, failed
        'result', // işlem sonucu veya hata mesajı
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 