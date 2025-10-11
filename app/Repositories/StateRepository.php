<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\State;

class StateRepository extends BaseRepository
{
    public function __construct(State $modelo)
    {
        parent::__construct($modelo);
    }

    public function list($request = [], $with = [], $select = ['*'], $idsAllowed = [])
    {
        $data = $this->model->select($select)
            ->with($with)
            ->where(function ($query) use ($request, $idsAllowed) {
                if (! empty($request['name'])) {
                    $query->where('id', 'like', '%'.$request['name'].'%');
                }
                if (count($idsAllowed) > 0) {
                    $query->whereIn('id', $idsAllowed);
                }
                if (! empty($request['idsAllowed']) && count($request['idsAllowed']) > 0) {
                    $query->whereIn('id', $request['idsAllowed']);
                }
            })
            ->where(function ($query) use ($request) {
                if (! empty($request['searchQuery'])) {
                    $query->orWhere('name', 'like', '%'.$request['searchQuery'].'%');
                }
            });
        if (empty($request['typeData'])) {
            $data = $data->paginate($request['perPage'] ?? Constants::ITEMS_PER_PAGE);
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function selectList($country_id)
    {
        return $this->model->where('country_id', $country_id)->get()->map(function ($value) {
            return [
                'value' => $value->id,
                'title' => $value->id . ' - ' .$value->name,
            ];
        });
    }
}
