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
        Schema::table('project_edit_requests', function (Blueprint $table) {
            // 採用する見積もりを ID で特定する（同名・金額違いの取り違え防止）
            $table->foreignId('requested_estimate_id')->nullable()->after('requested_partner_name')
                  ->constrained('project_estimates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_edit_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_estimate_id');
        });
    }
};
