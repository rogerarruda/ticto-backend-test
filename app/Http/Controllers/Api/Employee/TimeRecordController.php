<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeRecordResource;
use App\Models\TimeRecord;
use Carbon\Carbon;
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\Gate;

class TimeRecordController extends Controller
{
    public function store(Request $request): TimeRecordResource
    {
        Gate::authorize('create', TimeRecord::class);

        $user = $request->user();

        $timeRecord = TimeRecord::query()->create([
            'user_id'     => $user->id,
            'recorded_at' => Carbon::now(),
        ]);

        return new TimeRecordResource($timeRecord);
    }
}
