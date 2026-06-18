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
        // 1. まずNULL許可でカラムを追加
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('customer_number')->nullable()->after('id');
        });

        // 2. 既存の顧客に登録順（ID順）で通番を振る
        $number = 1;
        foreach (DB::table('customers')->orderBy('id')->pluck('id') as $id) {
            DB::table('customers')->where('id', $id)->update(['customer_number' => $number++]);
        }

        // 3. 全体でユニーク制約を付ける
        Schema::table('customers', function (Blueprint $table) {
            $table->unique('customer_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['customer_number']);
            $table->dropColumn('customer_number');
        });
    }
};
