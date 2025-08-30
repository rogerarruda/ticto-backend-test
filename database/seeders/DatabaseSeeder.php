<?php

namespace Database\Seeders;

use App\Models\{TimeRecord, User};
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->admin()->create([
            'email' => 'admin@email.com',
        ]);

        User::factory(10)
            ->employee()
            ->has(TimeRecord::factory()->count(random_int(5, 15)))
            ->create();
    }
}
