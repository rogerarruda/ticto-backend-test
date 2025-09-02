<?php

use App\Models\{TimeRecord, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingAsEmployee(): User
{
    $employee = User::factory()->employee()->create();
    Sanctum::actingAs($employee);

    return $employee;
}

function actingAsAdminUser(): User
{
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    return $admin;
}

it('allows an employee to register a time record once and persists it', function () {
    $employee = actingAsEmployee();

    $response = $this->postJson('/api/time-records');

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'recorded_at',
            ],
        ]);

    $this->assertDatabaseHas('time_records', [
        'user_id' => $employee->id,
    ]);
});

it('throttles consecutive time record requests from the same employee', function () {
    $employee = actingAsEmployee();

    $first = $this->postJson('/api/time-records');
    $first->assertCreated();

    $second = $this->postJson('/api/time-records');
    $second->assertStatus(429);

    $second->assertHeader('Retry-After');

    expect(TimeRecord::query()->where('user_id', $employee->id)->count())->toBe(1);

    $other = User::factory()->employee()->create();
    Sanctum::actingAs($other);

    $third = $this->postJson('/api/time-records');
    $third->assertCreated();
});

it('forbids non-employee (admin) from registering a time record', function () {
    actingAsAdminUser();

    $response = $this->postJson('/api/time-records');

    $response->assertForbidden();
});
