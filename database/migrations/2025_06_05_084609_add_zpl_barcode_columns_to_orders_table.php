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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('zpl_barcode')->nullable();
            $table->string('zpl_barcode_type')->nullable();
            $table->integer('zpl_print_count')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('zpl_barcode');
            $table->dropColumn('zpl_barcode_type');
            $table->dropColumn('zpl_print_count');
        });
    }
};
