<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@benditoorganico.com.br');
        $password = (string) env('ADMIN_PASSWORD', 'BenditoOrganico@2026');

        $user = User::withTrashed()->firstWhere('email', $email);

        if ($user !== null) {
            $this->command?->info("Administrador já existe: {$email}");

            return;
        }

        User::create([
            'name' => env('ADMIN_NAME', 'Administrador'),
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Administrador criado: {$email}");

        // Printed once, on the machine running the seeder. In production the
        // credentials come from ADMIN_EMAIL / ADMIN_PASSWORD in .env.
        if (! app()->isProduction()) {
            $this->command?->warn("Senha inicial: {$password} — troque após o primeiro acesso.");
        }
    }
}
