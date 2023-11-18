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
            'iframeGoogleMap' => $this->iframeGoogleMap,
            'social_networks' => $this->details->whereIn("type_detail_id", [1, 2, 3, 4, 5])->map(function ($value) {
                return [
                    "type_detail_name" => $value->typeDetail?->name,
                    "icon" => $value->icon,
                    "color" => $value->color,
                    "content" => is_array($value->content) ? explode(",", $value->content) : $value->content
                ];
            })->values(),
            'details' => $this->details->whereIn("type_detail_id", [6, 7, 8])->map(function ($value) {
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
            'teachers' => $this->teachers->whereIn("type_education_id", [1, 2])->map(function ($value) {
                $grade_name = "";
                $section_name = "";

                $info = $value->complementaries->first();
                if($info) {
                    $grade_name = $info->grade?->name;
                    $section_name = $info->section?->name;
                };
                return [
                    "fullName" => $value->name . ' ' . $value->last_name,
                    "photo" => $value->photo,
                    "type_education_id" => $value->type_education_id,
                    "type_education_name" => $value->typeEducation?->name,
                    "email" => $value->email,
                    "phone" => $value->phone,
                    "jobPosition" => $value->jobPosition?->name,
                    "backgroundColor" => generarColorPastelAleatorio(70),
                    "grade_name" => $grade_name,
                    "section_name" => $section_name,
                    "files" => [1,2,3],
                ];
            })->groupBy('type_education_name'),
        ];
    }
}
