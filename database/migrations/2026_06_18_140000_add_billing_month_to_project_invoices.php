<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_invoices', function (Blueprint $table) {
            // 請求対象月（YYYY-MM）。同じ月の二重請求を防ぐためのキー
            $table->string('billing_month', 7)->nullable()->after('sequence');
        });

        // 既存の請求は請求日から対象月を補完
        DB::statement("UPDATE project_invoices SET billing_month = DATE_FORMAT(billing_date, '%Y-%m') WHERE billing_month IS NULL AND billing_date IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_invoices', function (Blueprint $table) {
            $table->dropColumn('billing_month');
        });
    }
};
