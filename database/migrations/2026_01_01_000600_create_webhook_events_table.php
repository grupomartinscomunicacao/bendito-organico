<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->id();

            $table->string('source', 30)->default('mercadopago');

            // Gateway delivery id. Mercado Pago retries every 15 minutes until
            // it sees a 2xx, so the same event arrives many times: this unique
            // key is what makes processing idempotent.
            $table->string('event_id', 120);

            $table->string('topic', 50)->nullable();
            $table->string('action', 50)->nullable();
            $table->string('resource_id', 100)->nullable();

            $table->string('status', 20)->default('received');
            $table->text('error')->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'event_id']);
            $table->index('resource_id');
            $table->index(['source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
