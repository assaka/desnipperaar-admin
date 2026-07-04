<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Opaque per-order reply reference, embedded as [ref:xxxx] in every
            // customer email subject so replies link back to the order history.
            $table->string('reply_ref', 16)->nullable()->unique()->after('quote_reference');
        });

        // Backfill existing orders so their in-flight threads keep working.
        Order::whereNull('reply_ref')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $order->reply_ref = Order::generateReplyRef();
                $order->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('reply_ref');
        });
    }
};
