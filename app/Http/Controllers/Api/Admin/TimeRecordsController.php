<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeRecordResource;
use App\Models\TimeRecord;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Gate};

class TimeRecordsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TimeRecord::class);

        $validated = $request->validate([
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'employee_name'   => 'nullable|string|max:255',
            'supervisor_name' => 'nullable|string|max:255',
        ]);

        $startDate      = $validated['start_date'] ?? null;
        $endDate        = $validated['end_date'] ?? null;
        $employeeName   = $validated['employee_name'] ?? null;
        $supervisorName = $validated['supervisor_name'] ?? null;

        return TimeRecord::query()
            ->with([
                'user:id,name,cpf,email,position,supervisor_id',
                'user.supervisor:id,name,email',
            ])
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('recorded_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('recorded_at', '<=', $endDate);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('recorded_at', [$startDate, $endDate]);
            })
            ->when($employeeName, function ($query) use ($employeeName) {
                $query->whereHas('user', function ($query) use ($employeeName) {
                    $query->whereLike('name', '%' . $employeeName . '%');
                });
            })
            ->when($supervisorName, function ($query) use ($supervisorName) {
                $query->whereHas('user.supervisor', function ($query) use ($supervisorName) {
                    $query->whereLike('name', '%' . $supervisorName . '%');
                });
            })
            ->orderByDesc('recorded_at')
            ->paginate(15)
            ->toResourceCollection(TimeRecordResource::class);
    }

    public function report(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', TimeRecord::class);

        $validated = $request->validate([
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'employee_name' => 'nullable|string|max:255',
            'limit'         => 'nullable|integer|min:1|max:5000',
        ]);

        $limit        = $validated['limit'] ?? 1000;
        $startDate    = $validated['start_date'] ?? null;
        $endDate      = $validated['end_date'] ?? null;
        $employeeName = $validated['employee_name'] ?? null;

        $baseQuery = "
            SELECT 
                time_records.id as registro_id,
                users.name as nome_funcionario,
                users.position as cargo,
                YEAR(CURDATE()) - YEAR(users.birth_date) as idade,
                COALESCE(supervisor.name, 'Sem supervisor') as nome_supervisor,
                DATE_FORMAT(time_records.recorded_at, '%d/%m/%Y %H:%i:%s') as data_hora_registro
            FROM time_records
            JOIN users ON time_records.user_id = users.id
            LEFT JOIN users supervisor ON users.supervisor_id = supervisor.id
        ";

        $params = [];

        if ($startDate && !$endDate) {
            $baseQuery .= " WHERE time_records.recorded_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        }

        if ($endDate && !$startDate) {
            $baseQuery .= " WHERE time_records.recorded_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }

        if ($startDate && $endDate) {
            $baseQuery .= " WHERE time_records.recorded_at BETWEEN ? AND ?";
            $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        }

        if ($employeeName) {
            $baseQuery .= " WHERE users.name LIKE ?";
            $params[] = '%' . $employeeName . '%';
        }

        $baseQuery .= " ORDER BY time_records.recorded_at DESC LIMIT ?";
        $params[] = $limit;

        $records = DB::select($baseQuery, $params);

        return response()->json([
            'data'    => $records,
            'total'   => count($records),
            'filters' => [
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'employee_name' => $employeeName,
                'limit'         => $limit,
            ],
        ]);
    }
}
