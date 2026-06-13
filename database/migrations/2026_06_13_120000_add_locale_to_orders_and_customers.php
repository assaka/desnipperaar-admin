<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("orders", function (Blueprint $table) {
            $table->string("locale", 2)->default("nl")->after("customer_city");
        });
        Schema::table("customers", function (Blueprint $table) {
            $table->string("locale", 2)->default("nl");
        });
    }

    public function down(): void
    {
        Schema::table("orders", fn (Blueprint $t) => $t->dropColumn("locale"));
        Schema::table("customers", fn (Blueprint $t) => $t->dropColumn("locale"));
    }
};
