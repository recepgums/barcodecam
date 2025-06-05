<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'supplier_id',
        'token',
        'is_default',
        'api_key',
        'api_secret',
        'order_fetched_at',
        'kolaygelsin_customer_id',
        'kolaygelsin_username',
        'kolaygelsin_password',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeDefaultStore($query)
    {
        return $query->where('is_default', true);
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
