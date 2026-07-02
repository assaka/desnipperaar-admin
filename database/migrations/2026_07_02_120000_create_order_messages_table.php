<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 8);            // 'out' | 'in'
            $table->string('channel', 16)->default('email');
            $table->string('from_email')->nullable();
            $table->string('to_email')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('external_id')->nullable(); // resend message-id / imap uid
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'occurred_at']);
            $table->unique(['channel', 'direction', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_messages');
    }
};
