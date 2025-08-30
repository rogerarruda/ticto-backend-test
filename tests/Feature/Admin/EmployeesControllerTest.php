<?php

use App\Enums\User\Role;
use App\Models\User;
use App\Services\ViaCep\ViaCep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingAsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    return $admin;
}

function fakeAddressResponse(array $overrides = []): array
{
    return array_merge([
        'logradouro' => 'Rua Emerson José Moreira',
        'bairro'     => 'Chácara Primavera',
        'localidade' => 'Campinas',
        'uf'         => 'SP',
    ], $overrides);
}

it('lists only employees with pagination', function () {
    actingAsAdmin();

    User::factory()->count(2)->employee()->create();
    User::factory()->admin()->create();

    $response = $this->getJson('/api/admin/employees');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'name', 'email', 'cpf', 'position', 'zipcode',
                    'street', 'neighborhood', 'city', 'state',
                    'role', 'supervisor_id', 'supervisor', 'created_at', 'updated_at',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ]);

    $roles = collect($response->json('data'))->pluck('role')->unique();

    expect($roles->count())->toBe(1)
        ->and($roles->first())->toBe(Role::Employee->value);
});

it('creates an employee merging address from ViaCep when zipcode is valid', function () {
    $admin = actingAsAdmin();

    ViaCep::shouldReceive('consultarCep')
        ->once()
        ->with('13087441')
        ->andReturn(fakeAddressResponse());

    $payload = [
        'name'       => 'João Dias',
        'email'      => 'joao@email.com',
        'cpf'        => fake()->cpf(),
        'birth_date' => '1990-01-01',
        'position'   => 'Developer',
        'zipcode'    => '13087-441',
        'number'     => '123',
        'complement' => 'Apto 10',
        'password'   => 'password',
    ];

    $response = $this->postJson('/api/admin/employees', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'João Dias')
        ->assertJsonPath('data.email', 'joao@email.com')
        ->assertJsonPath('data.zipcode', '13087441')
        ->assertJsonPath('data.street', 'Rua Emerson José Moreira')
        ->assertJsonPath('data.neighborhood', 'Chácara Primavera')
        ->assertJsonPath('data.city', 'Campinas')
        ->assertJsonPath('data.state', 'SP')
        ->assertJsonPath('data.role', Role::Employee->value)
        ->assertJsonPath('data.supervisor_id', $admin->id);

    $this->assertDatabaseHas('users', [
        'email'         => 'joao@email.com',
        'role'          => Role::Employee->value,
        'supervisor_id' => $admin->id,
        'street'        => 'Rua Emerson José Moreira',
        'neighborhood'  => 'Chácara Primavera',
        'city'          => 'Campinas',
        'state'         => 'SP',
    ]);
});

it('returns 422 when zipcode is invalid on store', function () {
    actingAsAdmin();

    ViaCep::shouldReceive('consultarCep')
        ->once()
        ->with('00000000')
        ->andReturn(['erro' => true]);

    $payload = [
        'name'       => 'Joana Doe',
        'email'      => 'joana@email.com',
        'cpf'        => fake()->cpf(),
        'birth_date' => '1990-01-01',
        'position'   => 'QA',
        'zipcode'    => '00000000',
        'password'   => 'password',
    ];

    $response = $this->postJson('/api/admin/employees', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['zipcode'])
        ->assertJsonPath('message', 'CEP inválido ou não encontrado.');
});

it('shows an employee', function () {
    actingAsAdmin();

    $employee = User::factory()->employee()->create();

    $response = $this->getJson('/api/admin/employees/' . $employee->id);

    $response->assertOk()
        ->assertJsonPath('data.id', $employee->id)
        ->assertJsonPath('data.supervisor.id', $employee->supervisor_id);
});

it('updates an employee and merges address when zipcode valid', function () {
    actingAsAdmin();

    $employee = User::factory()->employee()->create(['zipcode' => '99999999']);

    ViaCep::shouldReceive('consultarCep')
        ->once()
        ->with('13087441')
        ->andReturn(fakeAddressResponse());

    $response = $this->patchJson('/api/admin/employees/' . $employee->id, [
        'zipcode' => '13087441',
        'number'  => '321',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.zipcode', '13087441')
        ->assertJsonPath('data.street', 'Rua Emerson José Moreira')
        ->assertJsonPath('data.number', '321');

    $this->assertDatabaseHas('users', [
        'id'           => $employee->id,
        'zipcode'      => '13087441',
        'street'       => 'Rua Emerson José Moreira',
        'neighborhood' => 'Chácara Primavera',
        'city'         => 'Campinas',
        'state'        => 'SP',
        'number'       => '321',
    ]);
});

it('returns 422 when zipcode is invalid on update', function () {
    actingAsAdmin();

    $employee = User::factory()->employee()->create(['zipcode' => '99999999']);

    ViaCep::shouldReceive('consultarCep')
        ->once()
        ->with('00000000')
        ->andReturn(['erro' => true]);

    $response = $this->patchJson('/api/admin/employees/' . $employee->id, [
        'zipcode' => '00000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['zipcode'])
        ->assertJsonPath('message', 'CEP inválido ou não encontrado.');
});

it('deletes an employee', function () {
    actingAsAdmin();

    $employee = User::factory()->employee()->create();

    $response = $this->deleteJson('/api/admin/employees/' . $employee->id);

    $response->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $employee->id]);
});
