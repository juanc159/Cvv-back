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
            'iframeGoogleMap' => $this->iframeGoogleMap,
            'students_pending_subject' => $this->students_pending_subject,
            'arrayDetails' => $this->details->map(function ($value) {
                return [
                    'id' => $value->id,
                    'type_detail_id' => $value->type_detail_id,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => $value->content,
                ];
            }),
        ];
    }
}
