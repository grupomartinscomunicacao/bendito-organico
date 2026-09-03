<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('gateway', 30)->default('mercadopago');

            // The gateway's own payment id. Unique per gateway so replaying a
            // webhook updates the existing row instead of duplicating it.
            $table->string('external_id', 100);
            $table->string('preference_id')->nullable();

            $table->string('status', 20)->default('pending');
            $table->string('gateway_status', 40)->nullable();
            $table->string('gateway_status_detail', 100)->nullable();

            $table->decimal('amount', 10, 2);
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('BRL');

            $table->string('payment_method', 50)->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->unsignedSmallInteger('installments')->nullable();
            $table->string('payer_email')->nullable();

            // Raw gateway response, kept verbatim for auditing and disputes.
            $table->json('payload')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'external_id']);
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
