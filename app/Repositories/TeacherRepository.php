<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\Teacher;
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

        return $this->cacheService->remember($cacheKey, function () {

        $query = QueryBuilder::for($this->model->query())
            ->with(['typeEducation:id,name', "jobPosition:id,name"])
            ->select(['teachers.id', 'teachers.name', 'last_name', 'email', "phone", "photo", "teachers.is_active","teachers.type_education_id","job_position_id"])
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
}
