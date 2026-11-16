<?php

namespace Database\Factories;

use App\Models\HomeCard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HomeCard>
 */
class HomeCardFactory extends Factory
{
    protected $model = HomeCard::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'section' => 'products',
            'sort_order' => 0,
            'is_active' => true,
            'title' => Str::title($title),
            'description' => fake()->sentence(10),
            'price' => fake()->randomFloat(2, 80, 5000),
        ];
    }

    public function category(): static
    {
        return $this->state(['section' => 'categories']);
    }

    public function novelty(): static
    {
        return $this->state(['section' => 'new']);
    }
}
