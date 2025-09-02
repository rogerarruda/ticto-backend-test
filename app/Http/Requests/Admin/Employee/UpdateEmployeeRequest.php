<?php

namespace App\Http\Requests\Admin\Employee;

use App\Enums\User\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge(['cpf' => preg_replace('/\D+/', '', (string) $this->input('cpf'))]);
        }

        if ($this->has('zipcode')) {
            $this->merge(['zipcode' => preg_replace('/\D+/', '', (string) $this->input('zipcode'))]);
        }

        if ($this->has('role')) {
            $this->merge(['role' => Role::Employee->value]);
        }
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'email'         => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'cpf'           => ['sometimes', 'required', 'cpf', Rule::unique('users', 'cpf')->ignore($employee->id)],
            'birth_date'    => ['sometimes', 'required', 'date'],
            'position'      => ['sometimes', 'required', 'string', 'max:255'],
            'zipcode'       => ['sometimes', 'required', 'digits:8'],
            'number'        => ['nullable', 'string', 'max:50'],
            'complement'    => ['nullable', 'string', 'max:255'],
            'supervisor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && !User::query()->where('id', $value)->where('role', Role::Admin)->exists()) {
                        $fail('O supervisor deve ter permissão de administrador.');
                    }
                },
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role'     => ['in:' . Role::Employee->value],
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
