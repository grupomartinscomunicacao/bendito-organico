<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // Null on delete rather than cascade: removing a product from the
            // catalog must never erase the history of what was actually sold.
            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Snapshot of the product at purchase time. Later edits to the
            // catalog cannot rewrite an order that was already placed.
            $table->string('product_name');
            $table->string('product_slug');
            $table->string('product_unit', 20);
            $table->string('product_image')->nullable();

            $table->decimal('unit_price', 10, 2);
            $table->decimal('quantity', 8, 3);
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
