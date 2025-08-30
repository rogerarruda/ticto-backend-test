<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'cpf'           => $this->cpf,
            'birth_date'    => $this->birth_date?->format('Y-m-d'),
            'position'      => $this->position,
            'zipcode'       => $this->zipcode,
            'street'        => $this->street,
            'number'        => $this->number,
            'complement'    => $this->complement,
            'neighborhood'  => $this->neighborhood,
            'city'          => $this->city,
            'state'         => $this->state,
            'role'          => $this->role,
            'supervisor_id' => $this->supervisor_id,
            'supervisor'    => SupervisorResource::make($this->whenLoaded('supervisor')),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
