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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('kolaygelsin_customer_id')->nullable();
            $table->string('kolaygelsin_username')->nullable();
            $table->string('kolaygelsin_password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('kolaygelsin_customer_id');
            $table->dropColumn('kolaygelsin_username');
            $table->dropColumn('kolaygelsin_password');
        });
    }
};
