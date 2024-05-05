<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'merchant_name',
        'supplier_id',
        'token',
        'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDefaultStore($query)
    {
        return $query->where('is_default',true);
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($store) {
            $oldAttributes = $store->getOriginal();

            $newAttributes = $store->getAttributes();
        });
    }
}
