<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\User;
use App\QueryBuilder\Filters\QueryFilters;
use App\QueryBuilder\Sort\IsActiveSort;
use App\QueryBuilder\Sort\RelatedTableSort;
use App\QueryBuilder\Sort\RoleDescriptionSort;
use App\QueryBuilder\Sort\UserFullNameSort;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class UserRepository extends BaseRepository
{
    public function __construct(User $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginate($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () {

            $query = QueryBuilder::for($this->model->query())
                ->select('users.id', 'users.name', 'users.surname', 'users.email', 'users.role_id', 'users.is_active')
                ->allowedFilters([
                    'is_active',
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            $subQuery->orWhere('email', 'like', "%$value%");

                            $subQuery->orWhereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$value}%"]);

                            $subQuery->orWhereHas('role', function ($q) use ($value) {
                                $q->where('description', 'like', "%$value%");
                            });

                            QueryFilters::filterByText($subQuery, $value, 'is_active', [
                                'activo' => 1,
                                'inactivo' => 0,
                            ]);
                        });
                    }),
                ])
                ->allowedSorts([
                    'email',
                    AllowedSort::custom('is_active', new IsActiveSort),
                    AllowedSort::custom('role_description', new RelatedTableSort(
                        'users',
                        'roles',
                        'description',
                        'role_id',
                    )), 
                    AllowedSort::custom('full_name', new UserFullNameSort),

                ])
                ->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function list($request = [], $with = [], $select = ['*'], $order = [])
    {
        $data = $this->model->select($select)
            ->with($with)
            ->where(function ($query) use ($request) {
                filterComponent($query, $request);

                if (! empty($request['name'])) {
                    $query->where('name', 'like', '%'.$request['name'].'%');
                }

                // idsAllowed
                if (! empty($request['idsAllowed']) && count($request['idsAllowed']) > 0) {
                    $query->whereIn('id', $request['idsAllowed']);
                }

                // idsNotAllowed
                if (! empty($request['idsNotAllowed']) && count($request['idsNotAllowed']) > 0) {
                    $query->whereNotIn('id', $request['idsNotAllowed']);
                }

                if (! empty($request['company_id'])) {
                    $query->where('company_id', $request['company_id']);
                }
            });

        if (count($order) > 0) {
            foreach ($order as $key => $value) {
                $data = $data->orderBy($value['field'], $value['type']);
            }
        }
        if (empty($request['typeData'])) {
            $data = $data->paginate($request['perPage'] ?? Constants::ITEMS_PER_PAGE);
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function store($request, $id = null, $withCompany = true)
    {
        $validatedData = $this->clearNull($request);

        $idToUse = $id ?? ($validatedData['id'] ?? null);

        if ($idToUse) {
            $data = $this->model->find($idToUse);
        } else {
            $data = $this->model::newModelInstance();
            if ($withCompany) {
                $data->company_id = auth()->user()->company_id;
            }
        }

        foreach ($request as $key => $value) {
            $data[$key] = is_array($request[$key]) ? $request[$key]['value'] : $request[$key];
        }

        if (! empty($validatedData['password'])) {
            $data->password = $validatedData['password'];
        } else {
            unset($data->password);
        }

        $data->save();

        return $data;
    }

    public function register($request)
    {
        $data = $this->model;

        foreach ($request as $key => $value) {
            $data[$key] = $request[$key];
        }

        $data->save();

        return $data;
    }

    public function findByEmail($email)
    {
        return $this->model::where('email', $email)->first();
    }

    public function selectList($request = [], $with = [], $select = [], $fieldValue = 'id', $fieldTitle = 'name')
    {
        $data = $this->model->with($with)->where(function ($query) use ($request) {
            if (! empty($request['idsAllowed'])) {
                $query->whereIn('id', $request['idsAllowed']);
            }

            $query->where('is_active', true);
            $query->where('company_id', auth()->user()->company_id);
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

    public function searchUser($request = [])
    {
        $data = $this->model->where(function ($query) use ($request) {
            $query->where('email', $request['user']);
        })->first();

        return $data;
    }
}
