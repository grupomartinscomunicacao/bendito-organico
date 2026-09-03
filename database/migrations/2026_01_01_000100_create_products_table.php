<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 180)->nullable();
            $table->text('description')->nullable();

            // Money is decimal, never float: cents must survive every round trip.
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();

            $table->string('unit', 20)->default('un');
            $table->string('image')->nullable();

            // Fractional stock so produce sold by weight works (0.5 kg, 1.5 kg).
            $table->decimal('stock', 10, 3)->default(0);
            $table->boolean('track_stock')->default(true);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Drives the storefront listing: active products, ordered manually.
            $table->index(['is_active', 'sort_order']);
            $table->index(['is_active', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
