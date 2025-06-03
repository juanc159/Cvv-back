<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\Grade;
use App\QueryBuilder\Filters\DataSelectFilter;
use App\QueryBuilder\Sort\RelatedTableSort;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class GradeRepository extends BaseRepository
{
    public function __construct(Grade $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginate($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () {

            $query = QueryBuilder::for($this->model->query())
                ->with(['typeEducation:id,name'])
                ->select(['grades.id', 'grades.name', 'type_education_id'])
                ->allowedFilters([
                    'name',
                    AllowedFilter::callback('type_education_id', new DataSelectFilter),
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            $subQuery->orWhere('name', 'like', "%$value%");
                        });
                    }),
                ])
                ->allowedSorts([
                    'name',
                    AllowedSort::custom('type_education_name', new RelatedTableSort(
                        'grades',
                        'type_education',
                        'name',
                        'type_education_id',
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
}
