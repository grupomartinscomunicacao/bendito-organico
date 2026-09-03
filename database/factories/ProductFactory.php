<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @var array<int, string> */
    private const PRODUCE = [
        'Alface Crespa', 'Rúcula', 'Agrião', 'Couve Manteiga', 'Espinafre',
        'Brócolis Ninja', 'Tomate Italiano', 'Cenoura', 'Beterraba', 'Abobrinha',
        'Pepino Japonês', 'Berinjela', 'Salsinha', 'Cebolinha', 'Manjericão',
        'Alecrim', 'Pimentão Amarelo', 'Batata Doce', 'Chuchu', 'Quiabo',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::PRODUCE);
        $unit = fake()->randomElement([
            ProductUnit::Unit, ProductUnit::Bunch, ProductUnit::Kilogram, ProductUnit::Tray,
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'short_description' => 'Colhido no dia, sem agrotóxicos.',
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->randomFloat(2, 3.5, 28),
            'unit' => $unit,
            'stock' => fake()->randomFloat(3, 0, 60),
            'track_stock' => true,
            'is_active' => true,
            'is_featured' => fake()->boolean(25),
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock' => 0, 'track_stock' => true]);
    }
}
