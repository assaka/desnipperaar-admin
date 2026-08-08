<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De herplankolommen eruit.
 *
 * De herplanstroom is vervallen: verzetten gaat via de planpagina, waar de klant
 * kiest uit momenten die wij echt kunnen rijden. Deze vier kolommen zijn met die
 * stroom meegegaan. Er heeft nooit één order iets in gehad, dus er gaat geen
 * gegeven verloren.
 *
 * Bij terugdraaien komen ze terug als gewone tekstkolommen. Het venster was
 * ooit een enum, maar die CHECK-constraint is in
 * 2026_06_13_130000_relax_pickup_window_constraints al gesneuveld toen wij
 * uurblokken gingen aanbieden. Hem hier opnieuw als enum neerzetten zou een
 * constraint terugbrengen die wij met opzet hebben weggehaald.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'reschedule_requested_at',
                'reschedule_requested_date',
                'reschedule_requested_window',
                'reschedule_notes',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('reschedule_requested_at')->nullable();
            $table->date('reschedule_requested_date')->nullable();
            $table->string('reschedule_requested_window')->nullable();
            $table->text('reschedule_notes')->nullable();
        });
    }
};
