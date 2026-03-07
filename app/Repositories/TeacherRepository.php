<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherComplementary;
use App\QueryBuilder\Filters\DataSelectFilter;
use App\QueryBuilder\Filters\QueryFilters;
use App\QueryBuilder\Sort\IsActiveSort;
use App\QueryBuilder\Sort\RelatedTableSort;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class TeacherRepository extends BaseRepository
{
    public function __construct(Teacher $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginate($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {

            $query = QueryBuilder::for($this->model->query())
                ->with(['typeEducation:id,name', "jobPosition:id,name"])
                ->select(['teachers.id', 'teachers.name', 'last_name', 'email', "phone", "photo", "teachers.is_active", "teachers.type_education_id", "teachers.company_id", "job_position_id"])
                ->allowedFilters([
                    'name',
                    'last_name',
                    'email',
                    'phone',
                    'is_active',
                    AllowedFilter::callback('type_education_id', new DataSelectFilter),
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            $subQuery->orWhere('name', 'like', "%$value%");
                            $subQuery->orWhere('last_name', 'like', "%$value%");
                            $subQuery->orWhere('email', 'like', "%$value%");
                            $subQuery->orWhere('phone', 'like', "%$value%");

                            $subQuery->orWhereHas('typeEducation', function ($q) use ($value) {
                                $q->where('name', 'like', "%$value%");
                            });
                            $subQuery->orWhereHas('jobPosition', function ($q) use ($value) {
                                $q->where('name', 'like', "%$value%");
                            });

                            QueryFilters::filterByText($subQuery, $value, 'is_active', [
                                'activo' => 1,
                                'inactivo' => 0,
                            ]);
                        });
                    }),
                ])
                ->allowedSorts([
                    'name',
                    'last_name',
                    'email',
                    'phone',
                    AllowedSort::custom('is_active', new IsActiveSort),
                    AllowedSort::custom('type_education_name', new RelatedTableSort(
                        'teachers',
                        'type_education',
                        'name',
                        'type_education_id',
                    )),
                    AllowedSort::custom('job_position_name', new RelatedTableSort(
                        'teachers',
                        'job_positions',
                        'name',
                        'job_position_id',
                    )),
                ])
                ->where(function ($query) use ($request) {
                    if (! empty($request['company_id'])) {
                        $query->where('teachers.company_id', $request['company_id']);
                    }
                })
                ->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);

            return $query;
        }, Constants::REDIS_TTL);
    }


    public function list($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)->with($with)->where(function ($query) use ($request) {
            filterComponent($query, $request);

            if (! empty($request['name'])) {
                $query->where('name', 'like', '%' . $request['name'] . '%');
            }
            if (! empty($request['type_education_id'])) {
                $query->where('type_education_id', $request['type_education_id']);
            }

            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            } else {
                $query->whereNull('company_id');
            }
        })
            ->where(function ($query) use ($request) {
                if (! empty($request['searchQueryInfinite'])) {
                    $query->orWhere('name', 'like', '%' . $request['searchQueryInfinite'] . '%');
                }
            });

        if (empty($request['typeData'])) {
            $data = $data->paginate($request['perPage'] ?? Constants::ITEMS_PER_PAGE);
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function store($request)
    {
        $request = $this->clearNull($request);

        if (! empty($request['id'])) {
            $data = $this->model->find($request['id']);
        } else {
            $data = $this->model::newModelInstance();
        }

        foreach ($request as $key => $value) {
            $data[$key] = $request[$key];
        }
        $data->save();

        return $data;
    }

    public function deleteArrayComplementaries($arrayIds, $model)
    {
        $data = $model->complementaries()->whereNotIn('id', $arrayIds)->delete();

        return $data;
    }

    public function searchUser($request = [])
    {
        $data = $this->model->where(function ($query) use ($request) {
            $query->where('email', $request['user']);
        })->first();

        return $data;
    }

    public function countData($request = [])
    {
        $data = $this->model->where(function ($query) use ($request) {
            if (! empty($request['is_active'])) {
                $query->where('is_active', $request['is_active']);
            }
            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
        })->count();

        return $data;
    }

    public function selectList($request = [], $with = [], $select = [], $fieldValue = 'id', $fieldTitle = 'name')
    {
        $data = $this->model->with($with)->where(function ($query) use ($request) {
            if (! empty($request['idsAllowed'])) {
                $query->whereIn('id', $request['idsAllowed']);
            }
            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
            $query->where('is_active', true);
        })->get()->map(function ($value) use ($with, $select, $fieldValue, $fieldTitle) {
            $data = [
                'value' => $value->$fieldValue,
                'title' => $value->$fieldTitle,
            ];

            if (count($select) > 0) {
                foreach ($select as $s) {
                    $data[$s] = $value->$s;
                }
            }

            if (count($with) > 0) {
                foreach ($with as $s) {
                    $data[$s] = $value->$s;
                }
            }

            return $data;
        });

        return $data;
    }

    public function findByEmail($email)
    {
        return $this->model::where('email', $email)->first();
    }


    public function getTeacherActivityOptions(string $teacherId): array
    {
        // Traer complementaries del docente con grade y section
        $comps = TeacherComplementary::query()
            ->where('teacher_id', $teacherId)
            ->with([
                'grade:id,name,is_active,company_id',
                'section:id,name,is_active',
            ])
            ->get();

        // GRADES únicos (activos)
        $grades = $comps->pluck('grade')
            ->filter(fn($g) => $g && (bool) $g->is_active)
            ->unique('id')
            ->values()
            ->map(fn($g) => [
                'value' => $g->id,
                'title' => $g->name,
            ])
            ->values()
            ->all();

        // SECTIONS únicos (activos)
        $sections = $comps->pluck('section')
            ->filter(fn($s) => $s && (bool) $s->is_active)
            ->unique('id')
            ->values()
            ->map(fn($s) => [
                'value' => $s->id,
                'title' => $s->name,
            ])
            ->values()
            ->all();

        // SUBJECTS: parsear CSV de subject_ids en complementaries
        $subjectIds = $comps->pluck('subject_ids')
            ->filter()
            ->flatMap(function ($csv) {
                return collect(explode(',', (string) $csv))
                    ->map(fn($id) => trim($id))
                    ->filter(fn($id) => $id !== '');
            })
            ->unique()
            ->values()
            ->all();

        $subjects = Subject::query()
            ->select(['id', 'name'])
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get()
            ->map(fn($subj) => [
                'value' => $subj->id,
                'title' => $subj->name,
            ])
            ->values()
            ->all();

        // RULES: combinaciones permitidas grade + section => subjects
        $rules = $comps
            ->filter(fn($c) => $c->grade_id && $c->section_id)
            ->map(function ($c) {
                $ids = collect(explode(',', (string) $c->subject_ids))
                    ->map(fn($id) => trim($id))
                    ->filter(fn($id) => $id !== '')
                    ->values()
                    ->all();

                return [
                    'grade_id' => $c->grade_id,
                    'section_id' => $c->section_id,
                    'subject_ids' => $ids,
                ];
            })
            ->values()
            ->all();

        return [
            'grades' => $grades,
            'sections' => $sections,
            'subjects' => $subjects,
            'rules' => $rules,
        ];
    }
}
