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
            $table->string('arrival_method')->nullable()->after('contract_date');   // 入荷方法（分納/一括納品/不明）
            $table->string('delivery_method')->nullable()->after('completion_date'); // 納品方法（分納/一括納品/不明）
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['arrival_method', 'delivery_method']);
        });
    }
};
