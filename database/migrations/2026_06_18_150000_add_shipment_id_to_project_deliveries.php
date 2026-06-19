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
        Schema::table('project_deliveries', function (Blueprint $table) {
            // 対応する出荷予定（STEP 7）。実績と予定を照合するために紐づける
            $table->foreignId('shipment_id')->nullable()->after('project_id')
                  ->constrained('project_shipments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipment_id');
        });
    }
};
