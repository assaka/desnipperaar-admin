<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bezorging wordt een eigen ordertype.
 *
 * Het werd tot nu toe aangeduid met delivery_mode = 'breng', en dat betekent in
 * deze applicatie al iets anders: de brengservice, waarbij de KLANT zijn
 * materiaal bij ons brengt. Wij gebruikten dezelfde waarde voor het omgekeerde,
 * namelijk dat wij een container bij de klant afleveren.
 *
 * Dat is niet alleen verwarrend in de code, het stond ook op papier: de bon
 * drukt ucfirst(mode)."bon" af, dus op de bezorgbon van een abonnement stond
 * "Brengbon", wat voor de klant leest als "u heeft dit gebracht".
 *
 * Vanaf nu is type = 'bezorging' leidend en zegt delivery_mode niets meer over
 * de richting van zo'n rit. Bestaande bezorgingen zijn te herkennen aan de
 * combinatie die alleen wij aanmaakten: een rit onder een abonnement met
 * delivery_mode 'breng'.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_type_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_type_check CHECK (type IN ('direct','quote','abonnement','bezorging'))");

        DB::statement("
            UPDATE orders
            SET type = 'bezorging', delivery_mode = 'ophaal'
            WHERE subscription_order_id IS NOT NULL AND delivery_mode = 'breng'
        ");

        // De bon kent dezelfde richting, en drukt hem af. 'bezorging' moet daar
        // dus ook een geldige waarde zijn.
        DB::statement('ALTER TABLE bons DROP CONSTRAINT IF EXISTS bons_mode_check');
        DB::statement("ALTER TABLE bons ADD CONSTRAINT bons_mode_check CHECK (mode IN ('ophaal','breng','mobiel','bezorging'))");

        DB::statement("
            UPDATE bons SET mode = 'bezorging'
            WHERE order_id IN (SELECT id FROM orders WHERE type = 'bezorging')
        ");
    }

    public function down(): void
    {
        DB::statement("UPDATE bons SET mode = 'breng' WHERE mode = 'bezorging'");
        DB::statement('ALTER TABLE bons DROP CONSTRAINT IF EXISTS bons_mode_check');
        DB::statement("ALTER TABLE bons ADD CONSTRAINT bons_mode_check CHECK (mode IN ('ophaal','breng','mobiel'))");

        DB::statement("UPDATE orders SET type = 'direct', delivery_mode = 'breng' WHERE type = 'bezorging'");
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_type_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_type_check CHECK (type IN ('direct','quote','abonnement'))");
    }
};
