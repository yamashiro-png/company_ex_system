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
        Schema::table('projects', function (Blueprint $table) {
            $table->date('billing_date')->nullable()->after('shipping_cost');                       // 請求日
            $table->unsignedInteger('billing_count')->nullable()->after('billing_date');            // 請求台数
            $table->decimal('billing_shipping_cost', 15, 2)->nullable()->after('billing_count');    // 請求する出荷費用
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['billing_date', 'billing_count', 'billing_shipping_cost']);
        });
    }
};
