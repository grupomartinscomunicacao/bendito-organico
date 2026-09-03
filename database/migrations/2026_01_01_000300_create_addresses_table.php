<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();

            // One delivery address per order; deleting the order removes it.
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('zip_code', 9);
            $table->string('state', 2);
            $table->string('city', 120);
            $table->string('district', 120);
            $table->string('street', 180);
            $table->string('number', 20);
            $table->string('complement', 120)->nullable();
            $table->string('reference', 180)->nullable();

            $table->timestamps();

            $table->unique('order_id');
            $table->index(['state', 'city']);
            $table->index('zip_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
