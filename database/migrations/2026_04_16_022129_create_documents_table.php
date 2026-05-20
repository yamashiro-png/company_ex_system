<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('documents', function (Blueprint $table) {
    //         $table->id();
    //         $table->timestamps();
    //     });
    // }
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id(); // 自動で振られる番号
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 誰がアップロードしたか（ユーザーID）
            $table->string('original_name'); // 元のファイル名（例：売上レポート.xlsx）
            $table->string('save_path');     // 実際の保存先パス（NASなど）
            $table->integer('file_size');    // ファイルのサイズ
            $table->timestamps(); // 作成日時・更新日時（自動記録）
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
