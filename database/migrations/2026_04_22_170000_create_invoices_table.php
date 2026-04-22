<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bon_id')->nullable()->constrained('bons')->nullOnDelete();

            // Snapshots of customer data at time of issuing (invoice is immutable).
            $table->string('customer_name');
            $table->string('customer_company')->nullable();
            $table->string('customer_email');
            $table->string('customer_address')->nullable();
            $table->string('customer_postcode', 10)->nullable();
            $table->string('customer_city')->nullable();

            // Lines as JSON snapshot (label, qty, unit_excl, subtotal_excl).
            $table->jsonb('lines');

            $table->decimal('amount_excl_btw', 10, 2);
            $table->decimal('vat_rate', 4, 3)->default(0.210);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('amount_incl_btw', 10, 2);

            $table->date('issued_at');
            $table->date('due_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->enum('status', ['draft', 'sent', 'paid', 'canceled'])->default('draft');
            $table->string('pdf_path')->nullable();

            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
