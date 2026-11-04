<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'sku' => strtoupper(fake()->unique()->bothify('MZ-####-???')),
            'barcode' => strtoupper(fake()->unique()->numerify('750123456####')),
            'brand' => fake()->randomElement(['Honda', 'Yamaha', 'Bajaj', 'Italika', 'DID', 'NGK']),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 90, 6500),
            'cost' => fake()->randomFloat(2, 50, 4000),
            'wholesale_price' => null,
            'tax_rate' => 0.1600,
            'stock' => fake()->numberBetween(0, 40),
            'min_stock' => fake()->numberBetween(0, 5),
            'track_inventory' => true,
            'featured' => fake()->boolean(25),
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => fake()->numberBetween(1, Product::LOW_STOCK_THRESHOLD),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
