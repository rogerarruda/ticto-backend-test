<?php

use App\Models\{TimeRecord, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingAsAdminTR(): User
{
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    return $admin;
}

function actingAsEmployeeTR(): User
{
    $employee = User::factory()->employee()->create();
    Sanctum::actingAs($employee);

    return $employee;
}

it('denies non-admin users from listing or reporting time records', function () {
    actingAsEmployeeTR();

    $this->getJson('/api/admin/time-records')->assertForbidden();
    $this->getJson('/api/admin/time-records/report')->assertForbidden();
});

it('lists time records with employee and supervisor data ordered by recorded_at desc', function () {
    actingAsAdminTR();

    $supervisorA = User::factory()->admin()->create(['name' => 'Supervisor A']);
    $supervisorB = User::factory()->admin()->create(['name' => 'Supervisor B']);

    $emp1 = User::factory()->employeeOf($supervisorA)->create(['name' => 'Alice']);
    $emp2 = User::factory()->employeeOf($supervisorB)->create(['name' => 'Bob']);

    TimeRecord::factory()->createMany([
        ['user_id' => $emp1->id, 'recorded_at' => Carbon::parse('2025-08-02 08:00:00')],
        ['user_id' => $emp2->id, 'recorded_at' => Carbon::parse('2025-08-03 09:00:00')],
        ['user_id' => $emp1->id, 'recorded_at' => Carbon::parse('2025-08-01 07:00:00')],
    ]);

    $response = $this->getJson('/api/admin/time-records');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'recorded_at',
                    'employee' => [
                        'id', 'name', 'email', 'cpf', 'position', 'zipcode',
                        'street', 'neighborhood', 'city', 'state',
                        'role', 'supervisor_id', 'supervisor', 'created_at', 'updated_at',
                    ],
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ]);

    $records = $response->json('data');

    expect($records[0]['recorded_at'])->toBe('2025-08-03 09:00:00')
        ->and($records[1]['recorded_at'])->toBe('2025-08-02 08:00:00')
        ->and($records[2]['recorded_at'])->toBe('2025-08-01 07:00:00')
        ->and(data_get($records[0], 'employee.supervisor.id'))->not()->toBeNull();
});

it('filters by start_date, end_date, and between', function () {
    actingAsAdminTR();

    $emp = User::factory()->employee()->create();

    TimeRecord::factory()->createMany([
        ['user_id' => $emp->id, 'recorded_at' => '2025-08-01 08:00:00'],
        ['user_id' => $emp->id, 'recorded_at' => '2025-08-10 08:00:00'],
        ['user_id' => $emp->id, 'recorded_at' => '2025-08-20 08:00:00'],
    ]);

    $this->getJson('/api/admin/time-records?start_date=2025-08-10')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/admin/time-records?end_date=2025-08-10')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/admin/time-records?start_date=2025-08-05&end_date=2025-08-15')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.recorded_at', '2025-08-10 08:00:00');
});

it('filters by employee_name and supervisor_name', function () {
    actingAsAdminTR();

    $supervisorX = User::factory()->admin()->create(['name' => 'Xavier']);
    $supervisorY = User::factory()->admin()->create(['name' => 'Yolanda']);

    $empX = User::factory()->employeeOf($supervisorX)->create(['name' => 'Carla Souza']);
    $empY = User::factory()->employeeOf($supervisorY)->create(['name' => 'Daniel']);

    TimeRecord::factory()->createMany([
        ['user_id' => $empX->id, 'recorded_at' => '2025-08-01 08:00:00'],
        ['user_id' => $empY->id, 'recorded_at' => '2025-08-02 08:00:00'],
    ]);

    $this->getJson('/api/admin/time-records?employee_name=carla')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.employee.name', 'Carla Souza');

    $this->getJson('/api/admin/time-records?supervisor_name=Yol')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.employee.supervisor.name', 'Yolanda');
});

it('returns tabular report with total and filters; respects limit', function () {
    if (DB::connection()->getDriverName() === 'sqlite') {
        test()->markTestSkipped('Report query uses MySQL-specific functions; skipping on SQLite.');
    }

    actingAsAdminTR();

    $supervisor = User::factory()->admin()->create(['name' => 'Sup Report']);
    $emp1       = User::factory()->employeeOf($supervisor)->create(['name' => 'Report One', 'position' => 'Dev', 'birth_date' => '1990-01-01']);
    $emp2       = User::factory()->employeeOf($supervisor)->create(['name' => 'Report Two', 'position' => 'QA', 'birth_date' => '1995-01-01']);

    TimeRecord::factory()->createMany([
        ['user_id' => $emp1->id, 'recorded_at' => '2025-07-01 08:00:00'],
        ['user_id' => $emp2->id, 'recorded_at' => '2025-07-02 08:00:00'],
    ]);

    $resp = $this->getJson('/api/admin/time-records/report?limit=1');

    $resp->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['registro_id', 'nome_funcionario', 'cargo', 'idade', 'nome_supervisor', 'data_hora_registro'],
            ],
            'total',
            'filters' => ['start_date', 'end_date', 'employee_name', 'limit'],
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('filters.limit', '1');

    $resp2 = $this->getJson('/api/admin/time-records/report?start_date=2025-07-02');
    $resp2->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.nome_funcionario', $emp2->name);

    $resp3 = $this->getJson('/api/admin/time-records/report?employee_name=report');
    $resp3->assertOk()
        ->assertJsonPath('total', 2);
});
