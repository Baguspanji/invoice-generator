<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([CustomerType::INDIVIDUAL, CustomerType::COMPANY]);

        return [
            'type' => $type,
            'identity_number' => $type === CustomerType::INDIVIDUAL ? fake()->numerify('################') : null,
            'name' => $type === CustomerType::COMPANY ? fake()->company() : fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerType::INDIVIDUAL,
            'identity_number' => fake()->numerify('################'),
            'name' => fake()->name(),
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerType::COMPANY,
            'identity_number' => null,
            'name' => fake()->company(),
        ]);
    }
}
