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
        Schema::create('project_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();              // 対象の案件
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();     // 申請者
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();    // 承認する上長
            $table->text('reason');                                          // 編集理由
            $table->decimal('requested_final_price', 15, 2);                 // 申請する最終見積金額
            $table->string('requested_partner_name')->nullable();            // 申請する採用企業
            $table->string('status')->default('pending');                    // pending / approved / rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_edit_requests');
    }
};
