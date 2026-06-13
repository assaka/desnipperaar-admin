<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The pickup_window / reschedule_requested_window columns were created as
 * enum('ochtend','middag','avond','flexibel'), which Postgres enforces with a
 * CHECK constraint. The planning UI and OrderController validation now also
 * accept hourly slots like '12:00-13:00', which pass Laravel validation but
 * violate the CHECK constraint, throwing a 500 on confirm-pickup.
 *
 * Drop the constraints so the columns accept any value matching the
 * controller's regex. The columns are already varchar, so no type change is
 * needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_pickup_window_check');
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_reschedule_requested_window_check');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_pickup_window_check CHECK (pickup_window IN ('ochtend','middag','avond','flexibel'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_reschedule_requested_window_check CHECK (reschedule_requested_window IN ('ochtend','middag','avond','flexibel'))");
    }
};
