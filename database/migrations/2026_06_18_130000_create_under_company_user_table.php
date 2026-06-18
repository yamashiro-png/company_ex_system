<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 担当依頼先会社を複数持てるようにする（多対多）
        Schema::create('under_company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('under_company_id')->constrained('under_companies')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'under_company_id']);
        });

        // 既存の単一カラムのデータをピボットへ移行
        if (Schema::hasColumn('users', 'assigned_under_company_id')) {
            $rows = DB::table('users')
                ->whereNotNull('assigned_under_company_id')
                ->get(['id', 'assigned_under_company_id']);

            foreach ($rows as $row) {
                DB::table('under_company_user')->insert([
                    'user_id'          => $row->id,
                    'under_company_id' => $row->assigned_under_company_id,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assigned_under_company_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('users', 'assigned_under_company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('assigned_under_company_id')->nullable()->after('company_name')
                      ->constrained('under_companies')->nullOnDelete();
            });

            // ピボットの先頭1件を単一カラムへ戻す
            $pairs = DB::table('under_company_user')->orderBy('id')->get();
            $seen = [];
            foreach ($pairs as $pair) {
                if (isset($seen[$pair->user_id])) continue;
                $seen[$pair->user_id] = true;
                DB::table('users')->where('id', $pair->user_id)
                    ->update(['assigned_under_company_id' => $pair->under_company_id]);
            }
        }

        Schema::dropIfExists('under_company_user');
    }
};
