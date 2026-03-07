<?php

namespace App\Repositories;

use App\Enums\Activity\ActivityStatusEnum;
use App\Helpers\Constants;
use App\Models\Activity;
use App\QueryBuilder\Filters\QueryFilters;
use App\QueryBuilder\Sort\EnumDescriptionSort;
use App\QueryBuilder\Sort\IsActiveSort;
use App\QueryBuilder\Sort\RelatedTableSort;
use App\Traits\AttributableEnum;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityRepository extends BaseRepository
{
    public function __construct(Activity $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginate($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        // return $this->cacheService->remember($cacheKey, function () use ($request) {

        $query = QueryBuilder::for($this->model->query())
            ->with([
                'grade:id,name',
                'section:id,name',
                'subject:id,name',
            ])
            ->where('company_id', $request['company_id']) // viene del front
            ->where('teacher_id', $request['teacher_id']) // viene del controller
            ->allowedFilters([
                'status',
                AllowedFilter::callback('inputGeneral', function ($query, $value) {
                    $query->where(function ($subQuery) use ($value) {
                        $subQuery
                            ->where('title', 'LIKE', "%{$value}%")
                            ->orWhere('description', 'LIKE', "%{$value}%")
                            ->orWhere('status', 'LIKE', "%{$value}%");

                        $subQuery->orWhereHas('grade', function ($qq) use ($value) {
                            $qq->where('name', 'like', "%$value%");
                        });
                        $subQuery->orWhereHas('section', function ($qq) use ($value) {
                            $qq->where('name', 'like', "%$value%");
                        });
                        $subQuery->orWhereHas('subject', function ($qq) use ($value) {
                            $qq->where('name', 'like', "%$value%");
                        });

                        QueryFilters::filterByText(
                            $subQuery,
                            $value,
                            'status',
                            ActivityStatusEnum::toFilterMap() // ¡Automático!
                        );
                    });
                }),
            ])
            ->allowedSorts([
                "title",
                "deadline_at",
                AllowedSort::custom('status', new EnumDescriptionSort(ActivityStatusEnum::class)),

            ])
            ->defaultSort('-created_at')
            ->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);

        return $query;
        // }, Constants::REDIS_TTL);
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
}
