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
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('project_number')->nullable()->after('id');
        });

        // 2. 既存の案件に登録順（ID順）で通番を振る
        $number = 1;
        foreach (DB::table('projects')->orderBy('id')->pluck('id') as $id) {
            DB::table('projects')->where('id', $id)->update(['project_number' => $number++]);
        }

        // 3. 全体でユニーク制約を付ける
        Schema::table('projects', function (Blueprint $table) {
            $table->unique('project_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['project_number']);
            $table->dropColumn('project_number');
        });
    }
};
