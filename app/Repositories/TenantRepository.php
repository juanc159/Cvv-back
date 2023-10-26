<?php

namespace App\Repositories;

use App\Models\Tenant;

class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'])
    {
        $data = $this->model->select($select)
            ->with($with)
            ->where(function ($query) use ($request) {
                if (! empty($request['name'])) {
                    $query->where('id', 'like', '%'.$request['name'].'%');
                }
            })
            ->where(function ($query) use ($request) {
                if (! empty($request['searchQuery'])) {
                    $query->orWhere('name', 'like', '%'.$request['searchQuery'].'%');
                    // $query->orWhereHas("position", function ($x) use ($request) {
                    //     $x->where("name", "like", "%" . $request["searchQuery"] . "%");
                    // });
                }
            });
        if (empty($request['typeData'])) {
            $data = $data->paginate($request['perPage'] ?? 10);
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function store(array $request)
    {
        $data = $this->model::create(['id' => $request['name']]);
        $data->domains()->create(['domain' => $request['name'].'.'.env('TENANTBASE')]);

        return $data;
    }
}
