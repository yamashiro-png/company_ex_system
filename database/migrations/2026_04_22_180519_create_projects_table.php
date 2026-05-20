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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // 顧客テーブルとの紐付け（顧客が削除されたら、紐づく案件も一緒に削除する設定）
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            
            $table->string('status'); // ステータス
            $table->string('name'); // 案件・請負業務名
            $table->integer('price')->nullable(); // 契約金額（空っぽも許可）
            $table->date('contract_date')->nullable(); // 契約日（空っぽも許可）
            $table->date('completion_date')->nullable(); // 完了予定日（空っぽも許可）
            $table->text('notes')->nullable(); // 備考（空っぽも許可）
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
