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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('parameter_input_type')->nullable()->after('parameter_text');
        });

        // 既にテキストが登録されている案件は「テキスト入力」として扱う
        DB::table('projects')
            ->whereNotNull('parameter_text')
            ->where('parameter_text', '!=', '')
            ->update(['parameter_input_type' => 'text']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('parameter_input_type');
        });
    }
};
