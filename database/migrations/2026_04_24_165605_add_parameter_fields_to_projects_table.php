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
            $table->text('parameter_text')->nullable()->after('notes'); // ベタ打ち用
            $table->string('parameter_file_path')->nullable()->after('parameter_text'); // ファイル用
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['parameter_text', 'parameter_file_path']);
        });
    }
};
