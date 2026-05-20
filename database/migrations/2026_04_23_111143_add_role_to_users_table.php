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
        Schema::table('users', function (Blueprint $table) {
            // ★追加：roleカラム（デフォルトは 'user' にする）
            $table->string('role')->default('user')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ★追加：ロールバック用に削除処理を書く
            $table->dropColumn('role');
        });
    }
};
