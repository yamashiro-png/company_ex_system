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
        Schema::create('project_step5_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('supervisor_id')->constrained('users');
            // 💡 申請中（変更したい）のデータを保存するカラム
            $table->string('requested_device_model');
            $table->integer('requested_device_count');
            $table->date('requested_contract_date');
            $table->date('requested_completion_date');
            $table->string('requested_delivery_method');
            $table->text('reason'); // 編集理由
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_step5_edit_requests');
    }
};
