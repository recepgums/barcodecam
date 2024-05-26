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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('store_id')->nullable()->constrained();
            $table->text('customer_name')->nullable();
            $table->longText('address')->nullable();
            $table->string('order_id')->nullable();
            $table->string('cargo_tracking_number')->nullable()->index();
            $table->string('cargo_service_provider')->nullable()->comment('mng aras');
            $table->longText('lines')->nullable();
            $table->string('order_date')->nullable();
            $table->string('status')->nullable();
            $table->string('total_price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
