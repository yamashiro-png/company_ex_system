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
        Schema::create('project_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();              // 対象の案件
            $table->decimal('old_final_price', 15, 2)->nullable();                          // 変更前の最終見積（初回確定時はnull）
            $table->decimal('new_final_price', 15, 2);                                      // 変更後の最終見積
            $table->string('old_partner_name')->nullable();                                 // 変更前の採用企業
            $table->string('new_partner_name')->nullable();                                 // 変更後の採用企業
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();  // 変更した人
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // 承認した上長
            $table->text('reason')->nullable();                                             // 変更理由
            $table->foreignId('edit_request_id')->nullable()->constrained('project_edit_requests')->nullOnDelete(); // 元になった申請
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_price_histories');
    }
};
