<?php

namespace App\Http\Resources\Teacher;

use App\Models\Subject;
use App\Models\TeacherPlanning;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherPlanningResource extends JsonResource
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
            'job_position_id' => $this->job_position_id,
            'complementaries' => $this->complementaries->map(function ($value) {
                $dataSub = [];
                $subject_ids = explode(',', $value->subject_ids);
                foreach ($subject_ids as $key => $sub) {
                    $x = Subject::find($sub);
                    if ($x) {
                        $files = TeacherPlanning::where(function ($query) use ($value, $x) {
                            $query->where('teacher_id', $value->teacher_id);
                            $query->where('grade_id', $value->grade_id);
                            $query->where('section_id', $value->section_id);
                            $query->where('subject_id', $x->id);
                        })->get()->map(function ($f) {
                            return [
                                'id' => $f->id,
                                'name' => $f->name,
                                'file' => $f->path,
                            ];
                        });

                        $dataSub[] = [
                            'value' => $sub,
                            'title' => $x->name,
                            'files' => $files,
                        ];
                    }

                }

                return [
                    'id' => $value->id,
                    'grade_id' => $value->grade_id,
                    'grade_name' => $value->grade?->name,
                    'section_id' => $value->section_id,
                    'section_name' => $value->section?->name,
                    'subjects' => $dataSub,
                ];
            }),
            'name' => $this->name,
            'last_name' => $this->last_name,
        ];
    }
}
