<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\User\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'cpf',
        'zipcode',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'role',
        'user_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => Role::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function timeRecords(): HasMany
    {
        return $this->hasMany(TimeRecord::class)->latest();
    }
}
