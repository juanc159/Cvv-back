<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyFormResource extends JsonResource
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
            'name' => $this->name,
            'slogan' => $this->slogan,
            'image_principal' => $this->image_principal,
            'arrayDetails' => $this->details->map(function ($value) {
                return [
                    'id' => $value->id,
                    'type_detail_id' => $value->type_detail_id,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => $value->content,
                    'delete' => 0,
                ];
            }),
        ];
    }
}
