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
            $table->boolean('quote_shipping_enabled')->default(false)->after('final_price'); // 送料入力ON/OFF
            $table->decimal('quote_shipping_fee', 15, 2)->nullable()->after('quote_shipping_enabled'); // 送料金額
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['quote_shipping_enabled', 'quote_shipping_fee']);
        });
    }
};
