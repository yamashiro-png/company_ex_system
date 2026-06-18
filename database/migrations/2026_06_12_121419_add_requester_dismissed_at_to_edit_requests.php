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
        // 申請者が結果通知（承認・却下）を消したときの記録用
        Schema::table('estimate_edit_requests', function (Blueprint $table) {
            $table->timestamp('requester_dismissed_at')->nullable()->after('status');
        });

        Schema::table('project_edit_requests', function (Blueprint $table) {
            $table->timestamp('requester_dismissed_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimate_edit_requests', function (Blueprint $table) {
            $table->dropColumn('requester_dismissed_at');
        });

        Schema::table('project_edit_requests', function (Blueprint $table) {
            $table->dropColumn('requester_dismissed_at');
        });
    }
};
