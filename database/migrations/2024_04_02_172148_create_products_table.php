<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('barcode')->index();
            $table->string('title')->nullable();
            $table->double('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('image_url')->nullable();
            $table->string('productUrl')->nullable();
            $table->integer('order_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
