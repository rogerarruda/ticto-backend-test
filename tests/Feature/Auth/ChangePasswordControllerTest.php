<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingUser(): User
{
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    Sanctum::actingAs($user);

    return $user;
}

it('requires authentication', function () {
    $this->postJson('/api/password/change', [])->assertUnauthorized();
});

it('changes password with valid data', function () {
    $user = actingUser();

    $payload = [
        'current_password'      => 'old-password',
        'password'              => 'new-strong-pass',
        'password_confirmation' => 'new-strong-pass',
    ];

    $resp = $this->postJson('/api/password/change', $payload);

    $resp->assertOk()
        ->assertJsonPath('message', 'Senha alterada com sucesso.');

    $user->refresh();

    expect(Hash::check('new-strong-pass', $user->password))->toBeTrue();
});

it('fails when current password is invalid', function () {
    actingUser();

    $payload = [
        'current_password'      => 'wrong-one',
        'password'              => 'new-strong-pass',
        'password_confirmation' => 'new-strong-pass',
    ];

    $this->postJson('/api/password/change', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

it('fails when confirmation does not match', function () {
    actingUser();

    $payload = [
        'current_password'      => 'old-password',
        'password'              => 'new-strong-pass',
        'password_confirmation' => 'mismatch',
    ];

    $this->postJson('/api/password/change', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails when new password equals current password', function () {
    actingUser();

    $payload = [
        'current_password'      => 'old-password',
        'password'              => 'old-password',
        'password_confirmation' => 'old-password',
    ];

    $this->postJson('/api/password/change', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
