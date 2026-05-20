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
        Schema::create('project_estimates', function (Blueprint $table) {
            $table->id();
            // どの案件の見積もりか（案件が消えたら道連れで消える設定）
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            $table->string('partner_name')->comment('依頼先会社名');
            $table->integer('cost_price')->nullable()->comment('見積金額');
            $table->date('partner_completion_date')->nullable()->comment('回答納期');
            $table->text('partner_message')->nullable()->comment('備考');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_estimates');
    }
};
