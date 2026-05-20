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
        // projectsテーブルに担当者情報を追加
        Schema::table('projects', function (Blueprint $table) {
            $table->string('pic_name')->nullable()->after('name'); // 担当者名
            $table->string('pic_email')->nullable()->after('pic_name'); // 担当者メール
        });

        // under_companiesテーブルに担当者名を追加（メールは既にあるので名前だけ）
        Schema::table('under_companies', function (Blueprint $table) {
            $table->string('pic_name')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function ($table) {
            $table->dropColumn(['pic_name', 'pic_email']);
        });
        Schema::table('under_companies', function ($table) {
            $table->dropColumn('pic_name');
        });
    }
};
