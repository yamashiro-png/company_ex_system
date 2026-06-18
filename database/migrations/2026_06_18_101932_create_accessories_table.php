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
        Schema::create('accessories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 初期マスタ（仮）
        $now = now();
        DB::table('accessories')->insert([
            ['name' => 'ケース', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'フィルム', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ストラップ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'タッチペン', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessories');
    }
};
