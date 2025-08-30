<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\User\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Employee\{StoreEmployeeRequest, UpdateEmployeeRequest};
use App\Http\Resources\EmployeeResource;
use App\Models\User;
use App\Services\ViaCep\ViaCep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\{ResourceCollection};
use Illuminate\Support\Facades\{Gate, Log};
use Symfony\Component\HttpFoundation\Response;

class EmployeesController extends Controller
{
    public function index(): ResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return User::query()
            ->select('id', 'name', 'email', 'cpf', 'role', 'supervisor_id', 'created_at')
            ->with('supervisor:id,name,email')
            ->where('role', Role::Employee->value)
            ->orderByDesc('id')
            ->paginate(15)
            ->toResourceCollection(EmployeeResource::class);
    }

    public function store(StoreEmployeeRequest $request): EmployeeResource|JsonResponse
    {
        Gate::authorize('create', User::class);

        $payload = $request->validated();

        $address = $this->lookupAddressByZipcode($payload['zipcode']);

        if (!$address) {
            return response()->json([
                'message' => 'CEP inválido ou não encontrado.',
                'errors'  => ['zipcode' => ['CEP inválido ou não encontrado.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = array_merge($payload, $address);

        $employee = User::query()
            ->create($payload);

        return new EmployeeResource($employee);
    }

    public function show(User $employee): EmployeeResource
    {
        Gate::authorize('view', $employee);

        $employee->loadMissing('supervisor:id,name,email');

        return new EmployeeResource($employee);
    }

    public function update(UpdateEmployeeRequest $request, User $employee)
    {
        Gate::authorize('update', $employee);

        $payload = $request->validated();

        $address = $this->lookupAddressByZipcode($payload['zipcode']);

        if (!$address) {
            return response()->json([
                'message' => 'CEP inválido ou não encontrado.',
                'errors'  => ['zipcode' => ['CEP inválido ou não encontrado.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = array_merge($payload, $address);

        $employee->update($payload);

        return new EmployeeResource($employee);
    }

    public function destroy(User $employee): JsonResponse
    {
        Gate::authorize('delete', $employee);

        $employee->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    private function lookupAddressByZipcode(string $zipcode): ?array
    {
        try {
            $response = ViaCep::consultarCep($zipcode);

            if (!$response || data_get($response, 'erro', false)) {
                return null;
            }

            return [
                'street'       => data_get($response, 'logradouro'),
                'neighborhood' => data_get($response, 'bairro'),
                'city'         => data_get($response, 'localidade'),
                'state'        => data_get($response, 'uf', ),
            ];
        } catch (\Throwable $e) {
            Log::error('ViaCEP lookup failed', ['cep' => $zipcode, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
