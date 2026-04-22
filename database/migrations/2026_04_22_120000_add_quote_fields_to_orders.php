<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('quoted_amount_excl_btw', 10, 2)->nullable()->after('first_box_free');
            $table->text('quote_body')->nullable()->after('quoted_amount_excl_btw');
            $table->timestamp('quote_sent_at')->nullable();
            $table->date('quote_valid_until')->nullable();
            $table->timestamp('quote_accepted_at')->nullable();
            $table->string('quote_acceptance_ip', 45)->nullable();
            $table->string('quote_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'quoted_amount_excl_btw', 'quote_body', 'quote_sent_at',
                'quote_valid_until', 'quote_accepted_at', 'quote_acceptance_ip', 'quote_token',
            ]);
        });
    }
};
