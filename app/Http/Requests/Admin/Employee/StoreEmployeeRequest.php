<?php

namespace App\Http\Requests\Admin\Employee;

use App\Enums\User\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $cpf        = preg_replace('/\D+/', '', (string) $this->input('cpf'));
        $zipcode    = preg_replace('/\D+/', '', (string) $this->input('zipcode'));
        $birth_date = Carbon::parse($this->input('birth_date'));

        $this->merge([
            'cpf'           => $cpf,
            'zipcode'       => $zipcode,
            'role'          => Role::Employee->value,
            'birth_date'    => $birth_date,
            'supervisor_id' => Auth::id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'cpf'           => ['required', 'cpf', Rule::unique('users', 'cpf')],
            'birth_date'    => ['required', 'date:Y-m-d'],
            'position'      => ['required', 'string', 'max:255'],
            'zipcode'       => ['required', 'digits:8'],
            'number'        => ['nullable', 'string', 'max:50'],
            'complement'    => ['nullable', 'string', 'max:255'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'password'      => ['required', 'string', 'min:8'],
            'role'          => ['in:' . Role::Employee->value],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'       => 'nome',
            'email'      => 'e-mail',
            'cpf'        => 'CPF',
            'birth_date' => 'data de nascimento',
            'position'   => 'cargo',
            'zipcode'    => 'CEP',
            'number'     => 'número',
            'complement' => 'complemento',
            'password'   => 'senha',
        ];
    }
}
