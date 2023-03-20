<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commission>
 */
class CommissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'recharge',
            'operator_type' => $this->faker->randomElement(['mobile prepaid', 'mobile postpaid']),
            'operator_name' => $this->faker->randomElement(['airtel', 'vi', 'jio']),
        ];
    }
}
