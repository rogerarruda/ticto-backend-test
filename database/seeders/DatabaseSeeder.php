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
        collect(range(1, 2))->each(function ($adminIndex) {
            $admin = User::factory()->admin()->create([
                'name'  => "Admin {$adminIndex}",
                'email' => "admin{$adminIndex}@email.com",
            ]);

            collect(range(1, 5))->each(function ($empIndex) use ($admin, $adminIndex) {
                $employeeNumber = ($adminIndex - 1) * 10 + $empIndex;

                $employee = User::factory()->employeeOf($admin)->create([
                    'name'  => "Funcionário {$employeeNumber}",
                    'email' => "funcionario{$employeeNumber}@email.com",
                ]);

                TimeRecord::factory()
                    ->count(random_int(3, 8))
                    ->for($employee, 'user')
                    ->create();
            });
        });
    }
}
