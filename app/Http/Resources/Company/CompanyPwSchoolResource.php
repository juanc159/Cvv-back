<?php

namespace App\Http\Resources\Company;

use App\Models\TeacherPlanning;
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
            'social_networks' => $this->details->whereIn('type_detail_id', [1, 2, 3, 4, 5])->map(function ($value) {
                return [
                    'type_detail_name' => $value->typeDetail?->name,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => is_array($value->content) ? explode(',', $value->content) : $value->content,
                ];
            })->values(),
            'details' => $this->details->whereIn('type_detail_id', [6, 7, 8])->map(function ($value) {
                return [
                    'type_detail_name' => $value->typeDetail?->name,
                    'icon' => $value->icon,
                    'color' => $value->color,
                    'content' => explode(',', $value->content),
                ];
            })->values(),
            'banners' => $this->banners->map(function ($value) {
                return [
                    'path' => $value->path,
                ];
            }),

            // 'teachers2' => $this->teachers->whereIn('type_education_id', [1, 2])->map(function ($value) {
            //     $grade_name = '';
            //     $section_name = '';
            //     foreach ($value->complementaries as $key => $value) {
            //         $subjects = explode(',', $value['subject_ids']);
            //         foreach ($subjects as $sub) {
            //             $subject = $this->subjectRepository->find($sub);

            //             $files = TeacherPlanning::where(function ($query) use ($value, $subject) {
            //                 $query->where('teacher_id', $value->teacher_id);
            //                 $query->where('grade_id', $value->grade_id);
            //                 $query->where('section_id', $value->section_id);
            //                 $query->where('subject_id', $subject->id);
            //             })->get()->map(function ($f) {
            //                 return [
            //                     'name' => $f->name,
            //                     'path' => $f->path,
            //                     'id' => $f->id,
            //                 ];
            //             });

            //             $color = generarColorPastelAleatorio(70);
            //             $teachers[] = [
            //                 'subject_name' => $subject->name,
            //                 'fullName' => $value['teacher']['name'] . ' ' . $value['teacher']['last_name'],
            //                 'photo' => $value['teacher']['photo'],
            //                 'email' => $value['teacher']['email'],
            //                 'phone' => $value['teacher']['phone'],
            //                 'jobPosition' => $value['teacher']['jobPosition']['name'],
            //                 'files' => $files,
            //                 'backgroundColor' => $color,
            //             ];
            //         }
            //     }


            //     return $subjects;
            // }),


            'teachers' => $this->teachers->whereIn('type_education_id', [1, 2])->map(function ($value) {
                $grade_name = '';
                $section_name = '';

                $files=[];
                $info = $value->complementaries->first();
                if ($info) {
                    $grade_name = $info->grade?->name;
                    $section_name = $info->section?->name;

                   $files = TeacherPlanning::where(function ($query) use ($info) {
                        $query->where('teacher_id', $info->teacher_id);
                        $query->where('grade_id', $info->grade_id);
                        $query->where('section_id', $info->section_id);
                        $query->where('subject_id', $info->subject_ids);
                    })->get()->map(function ($f) {
                        return [
                            'name' => $f->name,
                            'path' => $f->path,
                            'id' => $f->id,
                        ];
                    });
                }

                return [
                    // 'info' => $info,
                    'fullName' => $value->name . ' ' . $value->last_name,
                    'photo' => $value->photo,
                    'type_education_id' => $value->type_education_id,
                    'type_education_name' => $value->typeEducation?->name,
                    'email' => $value->email,
                    'phone' => $value->phone,
                    'jobPosition' => $value->jobPosition?->name,
                    'backgroundColor' => generarColorPastelAleatorio(70),
                    'grade_name' => $grade_name,
                    'section_name' => $section_name,
                    'files' => $files,
                ];
            })->groupBy('type_education_name'),
        ];
    }
}
