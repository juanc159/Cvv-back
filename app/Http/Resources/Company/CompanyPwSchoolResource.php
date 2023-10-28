<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyPwSchoolResource extends JsonResource
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
            'iframeGoogleMap' => $this->slogan,
            'social_networks' => $this->details->whereIn("type_detail_id", [1,2,3,4,5])->map(function ($value) {
                return [
                    "type_detail_name" => $value->typeDetail?->name,
                    "icon" => $value->icon,
                    "color" => $value->color,
                    "content" => is_array($value->content) ? explode(",", $value->content) : $value->content
                ];
            })->values(),
            'details' => $this->details->whereIn("type_detail_id", [6,7,8])->map(function ($value) {
                return [
                    "type_detail_name" => $value->typeDetail?->name,
                    "icon" => $value->icon,
                    "color" => $value->color,
                    "content" => explode(",", $value->content)
                ];
            })->values(),
            'banners' => $this->banners->map(function ($value) {
                return [
                    "path" => $value->path
                ];
            }),
        ];
    }
}
