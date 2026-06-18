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
        Schema::create('estimate_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_estimate_id')->constrained()->cascadeOnDelete();      // 対象の見積もり
            $table->decimal('old_cost_price', 15, 2)->nullable();                            // 変更前の金額（初回登録時はnull）
            $table->decimal('new_cost_price', 15, 2);                                        // 変更後の金額
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();  // 変更した人
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // 承認した上長（承認経由の場合）
            $table->text('reason')->nullable();                                              // 変更理由
            $table->foreignId('edit_request_id')->nullable()->constrained('estimate_edit_requests')->nullOnDelete(); // 元になった申請
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_price_histories');
    }
};
