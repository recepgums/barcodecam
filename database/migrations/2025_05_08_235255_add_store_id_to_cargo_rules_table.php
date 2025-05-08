<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CargoRule;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Geçersiz store_id'leri olan kayıtları silelim
        DB::table('cargo_rules')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('stores')
                      ->whereRaw('stores.id = cargo_rules.store_id');
            })
            ->delete();

        // Foreign key constraint ekleyelim
        Schema::table('cargo_rules', function (Blueprint $table) {
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargo_rules', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });
    }
};
