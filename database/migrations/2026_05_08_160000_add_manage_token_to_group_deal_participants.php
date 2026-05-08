<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_deal_participants', function (Blueprint $table) {
            // Self-service manage URL: /groepsdeals/manage/{token}.
            // Random 32-char string; uniqueness enforced at the DB layer.
            $table->string('manage_token', 64)->nullable()->after('price_snapshot');
        });

        // Backfill existing rows.
        DB::table('group_deal_participants')->whereNull('manage_token')->orderBy('id')->each(function ($row) {
            DB::table('group_deal_participants')
                ->where('id', $row->id)
                ->update(['manage_token' => Str::random(32)]);
        });

        Schema::table('group_deal_participants', function (Blueprint $table) {
            $table->string('manage_token', 64)->nullable(false)->change();
            $table->unique('manage_token');
        });
    }

    public function down(): void
    {
        Schema::table('group_deal_participants', function (Blueprint $table) {
            $table->dropUnique(['manage_token']);
            $table->dropColumn('manage_token');
        });
    }
};
