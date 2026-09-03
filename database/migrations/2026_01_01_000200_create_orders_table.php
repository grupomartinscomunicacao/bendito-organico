<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            // Doubles as the public route key, so it carries a random suffix:
            // readable for a human, not enumerable by a stranger.
            $table->string('public_number', 32)->unique();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 20);

            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('pending');

            // Denormalised pointers to the gateway so the admin can filter and
            // search without joining payments. Full history lives in payments.
            $table->string('gateway_preference_id')->nullable();
            $table->string('gateway_payment_id')->nullable();

            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Kept for dispute handling and abuse investigation.
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
            $table->index('customer_email');
            $table->index('created_at');
            $table->index(['status', 'payment_status']);
            $table->index('gateway_payment_id');
            $table->index('gateway_preference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
