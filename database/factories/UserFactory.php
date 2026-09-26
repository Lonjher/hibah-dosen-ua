<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'nidn'              => fake()->unique()->numerify('##########'),
            'full_name'         => fake()->name(),
            'birthday'          => fake()->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
            'gender'            => fake()->randomElement(['laki-laki', 'perempuan']),
            'address'           => fake()->address(),
            'phone_number'      => '628'.fake()->numerify('##########'),
            'avatar'            => null,
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role_id'           => Role::query()->inRandomOrder()->value('id'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /**
     * Assign role by role_code (UPPERCASE).
     */
    public function role(string $roleCode): static
    {
        $roleId = Role::query()->where('role_code', $roleCode)->value('id');

        return $this->state(fn () => ['role_id' => $roleId]);
    }

    public function superAdmin(): static
    {
        return $this->role('SUPERADMIN');
    }

    public function admin(): static
    {
        return $this->role('ADMIN');
    }

    public function reviewer(): static
    {
        return $this->role('REVIEWER');
    }

    public function user(): static
    {
        return $this->role('USER');
    }
}
