<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // ▼ これを追加（依頼先会社名を保存する列を作る）
            $table->string('partner_name')->nullable()->comment('依頼先会社名');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // ▼ これを追加（元に戻す時の処理）
            $table->dropColumn('partner_name');
        });
    }
};