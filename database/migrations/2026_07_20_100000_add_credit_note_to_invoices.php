<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creditfacturen.
 *
 * Halen wij door onze eigen schuld niet op, dan crediteren wij die periode. Dat
 * is beloofd op de site en in de activatiemail, maar er was geen knop voor.
 *
 * Een verstuurde factuur wordt niet aangepast of verwijderd: de klant heeft hem
 * al, en in de boekhouding hoort een verstuurd stuk te blijven staan. In plaats
 * daarvan komt er een tegenboeking, een creditfactuur met negatieve bedragen die
 * naar het origineel verwijst. Het origineel blijft dus zichtbaar en het saldo
 * klopt.
 *
 * credits_invoice_id vult alleen de creditfactuur; is hij gevuld, dan IS dit
 * stuk een creditfactuur. Of een factuur gecrediteerd is leiden we af uit het
 * bestaan van zo'n tegenboeking, zodat er geen tweede waarheid ontstaat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('credits_invoice_id')->nullable()->after('order_id')
                ->constrained('invoices')->nullOnDelete();
            $table->string('credit_reason', 300)->nullable()->after('credits_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credits_invoice_id');
            $table->dropColumn('credit_reason');
        });
    }
};
