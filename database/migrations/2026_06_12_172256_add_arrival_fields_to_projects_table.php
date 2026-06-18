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
        Schema::table('projects', function (Blueprint $table) {
            $table->date('arrival_date')->nullable()->after('completion_date');           // 入荷日
            $table->unsignedInteger('arrival_count')->nullable()->after('arrival_date');  // 入荷台数
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['arrival_date', 'arrival_count']);
        });
    }
};
