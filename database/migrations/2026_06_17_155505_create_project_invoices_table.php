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
        Schema::create('project_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');                 // 案件内の請求通番（枝番）1,2,3...
            $table->date('billing_date');                        // 請求日
            $table->unsignedInteger('billing_count');            // 今回の請求台数
            $table->decimal('billing_shipping_cost', 15, 2)->nullable(); // 今回の出荷費用
            $table->decimal('amount_total', 15, 2)->default(0);  // 今回の請求合計（税込）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invoices');
    }
};
