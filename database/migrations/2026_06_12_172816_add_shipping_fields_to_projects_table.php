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
            $table->date('shipping_date')->nullable()->after('arrival_count');            // 出荷日
            $table->decimal('shipping_cost', 15, 2)->nullable()->after('shipping_date');  // 出荷費用
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['shipping_date', 'shipping_cost']);
        });
    }
};
