<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\PendingRegistration;
use App\QueryBuilder\Sort\RelatedTableSort; 
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder; 

class PendingRegistrationRepository extends BaseRepository
{
    public function __construct(PendingRegistration $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginate($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        // return $this->cacheService->remember($cacheKey, function () {


        $query = QueryBuilder::for($this->model->query())
            ->with(["term:id,name"])
            ->select(['pending_registrations.id', 'term_id', "section_name"])
            ->withCount(['students'])
            ->allowedFilters([
                AllowedFilter::callback('inputGeneral', function ($query, $value) {
                    $query->orWhere('section_name', 'like', "%$value%");
                    $query->orWhereHas("term", function ($subQuery) use ( $value) {
                        $subQuery->where('name', 'like', "%$value%");
                    });
                }),
            ])
            ->allowedSorts([
                'section_name',
                'students_count',
                AllowedSort::custom('term_name', new RelatedTableSort(
                    'pending_registrations',
                    'terms',
                    'name',
                    'term_id',
                )),

            ]);

        if (empty($request['typeData'])) {
            $query = $query->paginate($request['perPage'] ?? Constants::ITEMS_PER_PAGE);
        } else {
            $query = $query->get();
        }
        return $query;
        // }, Constants::REDIS_TTL);
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

    public function generateSectionName($periodName, $code)
    {
        $periodName = str_replace(' ', '_', $periodName);
        $sequence = $this->model::where('section_name', 'like', $periodName . '-%')->count() + 1;
        $sequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        return "{$periodName}-{$code}";
    }

    public function findByStudentAndTerm($studentId, $termId)
    {
        return $this->model->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->first();
    }

    public function findByCompanyId($company_id)
    {
        return $this->model::with(['company', 'term', 'type_education', 'grade'])
            ->where('company_id', $company_id)
            ->get();
    }

   
}
