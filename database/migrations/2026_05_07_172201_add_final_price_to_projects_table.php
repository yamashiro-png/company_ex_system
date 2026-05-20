<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // STEP 1の price の後ろに、STEP 4用の final_price を追加します
            $table->integer('final_price')->nullable()->after('price')->comment('最終提出見積額');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('final_price');
        });
    }
};