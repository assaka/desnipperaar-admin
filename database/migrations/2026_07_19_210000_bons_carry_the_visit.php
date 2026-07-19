<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Eén order per abonnement, met de bezoeken als bons.
 *
 * Elke ophaling kreeg een eigen order, zodat hij op het planbord kwam en een bon
 * en certificaat kon krijgen. Daardoor stonden er bij één abonnement een dozijn
 * orders in de lijst, terwijl er maar één afspraak is met de klant.
 *
 * De juiste vorm is: één order (het abonnement), meerdere bons (de ritten),
 * meerdere facturen (de periodes) en meerdere certificaten (de vernietigingen).
 * De facturen hingen al aan het abonnement, dus daar verandert niets.
 *
 * Wat een bon miste om dit te kunnen dragen:
 *
 * - een geplande datum. Die stond op de order (pickup_date), en met één order
 *   per abonnement kan dat niet meer.
 * - de richting 'retour', voor de rit waarmee de container aan het eind weer
 *   wordt opgehaald. Die stond tot nu toe alleen in de opzegmail en werd
 *   nergens ingepland.
 * - de ritmedatum, om te weten welke geplande rit al bestaat zonder af te gaan
 *   op de eventueel verschoven datum.
 *
 * En een certificaat hoorde bij een order; nu bij de bon, want per abonnement
 * zijn er meerdere vernietigingen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bons', function (Blueprint $table) {
            $table->date('planned_for')->nullable()->after('mode');
            $table->string('planned_window', 20)->nullable()->after('planned_for');
            $table->date('scheduled_for')->nullable()->after('planned_window');
            // Herinnering hoort bij de rit, niet bij de order: bij een abonnement
            // zijn er meerdere ritten op dezelfde order.
            $table->timestamp('reminder_sent_at')->nullable()->after('scheduled_for');
            $table->index(['planned_for']);
            $table->unique(['order_id', 'scheduled_for'], 'bons_order_scheduled_unique');
        });

        DB::statement('ALTER TABLE bons DROP CONSTRAINT IF EXISTS bons_mode_check');
        DB::statement("ALTER TABLE bons ADD CONSTRAINT bons_mode_check CHECK (mode IN ('ophaal','breng','mobiel','bezorging','retour'))");

        // Bestaande bons horen bij een losse order; hun datum staat daar.
        DB::statement('UPDATE bons SET planned_for = orders.pickup_date, planned_window = orders.pickup_window
                       FROM orders WHERE bons.order_id = orders.id AND orders.pickup_date IS NOT NULL');

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('bon_id')->nullable()->after('order_id')->constrained('bons')->nullOnDelete();
        });

        // Bestaande certificaten aan hun enige bon koppelen.
        DB::statement('UPDATE certificates SET bon_id = b.id
                       FROM (SELECT DISTINCT ON (order_id) id, order_id FROM bons ORDER BY order_id, id) b
                       WHERE certificates.order_id = b.order_id AND certificates.bon_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bon_id');
        });

        Schema::table('bons', function (Blueprint $table) {
            $table->dropUnique('bons_order_scheduled_unique');
            $table->dropIndex(['planned_for']);
            $table->dropColumn(['planned_for', 'planned_window', 'scheduled_for', 'reminder_sent_at']);
        });

        DB::statement("UPDATE bons SET mode = 'ophaal' WHERE mode IN ('bezorging','retour')");
        DB::statement('ALTER TABLE bons DROP CONSTRAINT IF EXISTS bons_mode_check');
        DB::statement("ALTER TABLE bons ADD CONSTRAINT bons_mode_check CHECK (mode IN ('ophaal','breng','mobiel'))");
    }
};
