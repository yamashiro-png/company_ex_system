<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('cost_price')->nullable()->after('price'); // 依頼先見積額（原価）
            $table->date('partner_completion_date')->nullable()->after('completion_date'); // 依頼先回答納期
            $table->text('partner_message')->nullable()->after('parameter_text'); // 依頼先からの備考・回答内容
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            //
        });
    }
};
