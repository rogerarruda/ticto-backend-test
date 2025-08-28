<?php

namespace Database\Factories;

use App\Enums\User\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'cpf'               => fake()->cpf(false),
            'birth_date'        => fake()->date(),
            'position'          => fake()->jobTitle(),
            'zipcode'           => str(fake()->postcode())->numbers(),
            'street'            => fake()->streetName(),
            'number'            => fake()->buildingNumber(),
            'complement'        => fake()->secondaryAddress(),
            'neighborhood'      => fake()->colorName(),
            'city'              => fake()->city(),
            'state'             => fake()->state(),
            'role'              => fake()->randomElement(Role::cases()),
            'supervisor_id'     => null,
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'          => Role::Admin,
            'supervisor_id' => null,
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Employee,
        ])->afterCreating(function (User $user) {
            $admin = User::query()
                ->where('role', Role::Admin)
                ->inRandomOrder()
                ->first();

            if (!$admin) {
                $admin = User::factory()->admin()->create();
            }

            $user->update(['supervisor_id' => $admin->id]);
        });
    }

    public function employeeOf(User $supervisor): static
    {
        return $this->state(fn (array $attributes) => [
            'role'          => Role::Employee,
            'supervisor_id' => $supervisor->id,
        ]);
    }
}
