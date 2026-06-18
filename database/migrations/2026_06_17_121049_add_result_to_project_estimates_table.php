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
        Schema::table('project_estimates', function (Blueprint $table) {
            $table->string('result')->nullable()->after('partner_message'); // 受注 / 失注（最終選定の結果）
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_estimates', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
