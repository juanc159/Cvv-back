<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\Comment;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CommentRepository extends BaseRepository
{
    public function __construct(Comment $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginate($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        // Eliminamos el Cache por ahora para probar (o asegúrate de limpiar cache al guardar)
        // return $this->cacheService->remember($cacheKey, function () use ($request) {

        $query = QueryBuilder::for($this->model->query())
            ->with(['user', 'user.role', 'attachments']) // <--- CARGA ANSIOSA DE ADJUNTOS
            ->allowedFilters([
                AllowedFilter::callback('inputGeneral', function ($query, $value) {
                    $query->where('body', 'like', "%$value%");
                }),
            ])
            ->allowedSorts(['body', 'created_at'])
            ->where(function ($query) use ($request) {
                if (! empty($request['company_id'])) {
                    $query->where('company_id', $request['company_id']);
                }
                if (! empty($request['commentable_id'])) {
                    $query->where('commentable_id', $request['commentable_id']);
                }

                if (! empty($request['commentable_type'])) {
                    // Limpieza del string por si viene con namespace o sin él
                    $type = str_replace('App\\Models\\', '', $request['commentable_type']);
                    $query->where('commentable_type', 'App\\Models\\'.$type);
                }
            })
            ->defaultSort('-created_at')
            ->paginate(2);

        return $query;
        // }, Constants::REDIS_TTL);
    }

    public function store(array $request, $id = null)
    {
        $request = $this->clearNull($request);

        // Determinar el ID a utilizar para buscar o crear el modelo
        $idToUse = ($id === null || $id === 'null') && ! empty($request['id']) && $request['id'] !== 'null' ? $request['id'] : $id;

        if (! empty($idToUse)) {
            $data = $this->model->find($idToUse);
        } else {
            $data = $this->model::newModelInstance();
        }

        foreach ($request as $key => $value) {
            $data[$key] = is_array($request[$key]) ? $request[$key]['value'] : $request[$key];
        }

        $data->save();

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
