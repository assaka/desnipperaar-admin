<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soms kan de klant niet tekenen: niemand bevoegd aanwezig, de receptie neemt
 * het over, of het scherm doet het niet. De bon moet dan toch afgerond kunnen
 * worden, maar niet stilzwijgend: de chauffeur legt in vrije tekst vast waarom
 * er geen handtekening is, en wie dat heeft vastgelegd.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bons', function (Blueprint $table) {
            $table->timestamp('customer_signature_waived_at')->nullable()->after('customer_signature_path');
            $table->text('customer_signature_waiver_reason')->nullable()->after('customer_signature_waived_at');
            $table->string('customer_signature_waived_by')->nullable()->after('customer_signature_waiver_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bons', function (Blueprint $table) {
            $table->dropColumn([
                'customer_signature_waived_at',
                'customer_signature_waiver_reason',
                'customer_signature_waived_by',
            ]);
        });
    }
};
