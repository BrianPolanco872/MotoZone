<?php

namespace Database\Factories;

use App\Models\CustomerCredit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerCredit>
 */
class CustomerCreditFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(1000, 500000);

        return [
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('55########'),
            'description' => fake()->sentence(8),
            'total_cents' => $total,
            'balance_cents' => $total,
            'status' => CustomerCredit::STATUS_PENDING,
            'idempotency_key' => (string) Str::uuid(),
            'created_by' => User::factory(),
        ];
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'balance_cents' => 0,
            'status' => CustomerCredit::STATUS_SETTLED,
        ]);
    }
}
