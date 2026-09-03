<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ProductSeeder::class,
        ]);

        // Demo orders are for looking around the panel, not for a real shop.
        if (! app()->isProduction()) {
            $this->call(DemoOrderSeeder::class);
        }
    }
}
