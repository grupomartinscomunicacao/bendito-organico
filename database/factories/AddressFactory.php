<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'zip_code' => fake()->numerify('#####-###'),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'PR', 'RS', 'SC', 'BA', 'GO']),
            'city' => fake()->city(),
            'district' => fake()->randomElement(['Centro', 'Jardim Bela Vista', 'Vila Nova', 'Alto da Serra', 'Parque das Flores']),
            'street' => fake()->streetName(),
            'number' => (string) fake()->buildingNumber(),
            'complement' => fake()->boolean(40) ? 'Apto '.fake()->numberBetween(11, 180) : null,
            'reference' => fake()->boolean(30) ? 'Portão verde, ao lado da padaria' : null,
        ];
    }
}
