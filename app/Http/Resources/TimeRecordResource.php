<?php

namespace App\Http\Resources;

use App\Models\TimeRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeRecord */
class TimeRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'employee'    => EmployeeResource::make($this->whenLoaded('user')),
            'recorded_at' => $this->recorded_at->format('Y-m-d H:i:s'),
        ];
    }
}
