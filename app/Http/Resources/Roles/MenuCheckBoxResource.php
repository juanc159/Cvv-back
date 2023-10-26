<?php

namespace App\Http\Resources\Roles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuCheckBoxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'estado' => false,
            'permissions' => $this->permissions->map(function ($value) {
                return [
                    'id' => $value->id,
                    'description' => $value->description,
                    'estado' => false,
                ];
            }),
        ];
    }
}
