<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wat het certificaat verklaart, los van order en bon.
 *
 * Een order uit een offerte op maat heeft geen dozen of containers, alleen
 * afgesproken prijsregels, en een getekende bon is vergrendeld. Het certificaat
 * kwam daardoor zonder materialen uit. De vernietigingslocatie volgde de
 * methode die de klant in het offerteformulier koos ('mobiel'), ook als wij
 * het materiaal ophaalden en in het depot vernietigden.
 *
 * Leeg betekent: afleiden zoals voorheen (bon, dan order). Gevuld gaat voor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('destruction_location', 20)->nullable()->after('destruction_method');
            $table->unsignedInteger('boxes_final')->nullable()->after('weight_kg_final');
            $table->unsignedInteger('containers_final')->nullable()->after('boxes_final');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['destruction_location', 'boxes_final', 'containers_final']);
        });
    }
};
