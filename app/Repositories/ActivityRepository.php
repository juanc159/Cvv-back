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

        return $this->cacheService->remember($cacheKey, function () use ($request) {

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

                            $subQuery->orWhere('title', 'LIKE', "%{$value}%");

                            $subQuery->orWhereHas('grade', function ($qq) use ($value) { // Filtro por Año
                                $qq->where('name', 'like', "%$value%");
                            });
                            $subQuery->orWhereHas('section', function ($qq) use ($value) { // Filtro por Sección
                                $qq->where('name', 'like', "%$value%");
                            });
                            $subQuery->orWhereHas('subject', function ($qq) use ($value) { // Filtro por Materia
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
                    AllowedSort::custom('grade', new RelatedTableSort('activities', 'grades', 'name', 'grade_id')),
                    AllowedSort::custom('section', new RelatedTableSort('activities', 'sections', 'name', 'section_id')),

                ])
                ->defaultSort('-created_at')
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

    /**
     * Obtiene las actividades visibles para un estudiante específico.
     * Filtra por Grado, Sección y que el estado sea PUBLICADO.
     */
    public function getStudentActivities($companyId, $gradeId, $sectionId, $studentId, $perPage = 10)
    {
        return $this->model
            ->with([
                'subject:id,name', // Traemos el nombre de la materia
                'teacher:id,user_id', // Datos básicos del profesor
                'teacher.user:id,name,surname',
                'latestSubmission' => function ($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                },
            ])
            ->where('company_id', $companyId)
            ->where('grade_id', $gradeId)
            ->where('section_id', $sectionId)
            // FILTRO CLAVE: Solo mostramos lo que el profesor ya publicó
            ->where('status', \App\Enums\Activity\ActivityStatusEnum::ACTIVITY_STATUS_002->value)
            // Ordenar: Primero las que están por vencer (deadline más antigua a más futura)
            ->orderBy('deadline_at', 'asc')
            ->paginate($perPage);
    }
}
