<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\User\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\User;
use Illuminate\Http\{Request};
use Illuminate\Http\Resources\Json\{ResourceCollection};
use Illuminate\Support\Facades\Gate;

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

    public function store(Request $request)
    {

    }

    public function show(User $employee): EmployeeResource
    {
        Gate::authorize('view', $employee);

        $employee->loadMissing('supervisor:id,name,email');

        return new EmployeeResource($employee);
    }

    public function update(Request $request, User $employee)
    {

    }

    public function destroy(User $employee)
    {

    }
}
