<?php

declare(strict_types=1);

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes "meu pedido" searchable by phone number.
 *
 * Two things are needed for that: the column has to be indexed, and every row
 * already in the table has to hold the same canonical form new orders are
 * written in — bare national digits. Rows created before the checkout started
 * stripping masks may still read "(77) 99999-9999", and those would never be
 * found by an exact match.
 *
 * Nothing is deleted: the values are rewritten in place, digit for digit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index('customer_phone');
        });

        DB::table('orders')
            ->select('id', 'customer_phone')
            ->chunkById(500, function ($orders): void {
                foreach ($orders as $order) {
                    $normalized = Phone::normalize($order->customer_phone);

                    // Leave anything unparseable exactly as it is rather than
                    // blanking a record we cannot interpret.
                    if ($normalized === '' || $normalized === $order->customer_phone) {
                        continue;
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['customer_phone' => $normalized]);
                }
            });
    }

    /**
     * Only the index comes off. The old punctuation is not restored — it
     * carried no information the digits do not already have.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['customer_phone']);
        });
    }
};
