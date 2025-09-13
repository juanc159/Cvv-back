<?php

namespace App\Http\Controllers;

use App\Http\Requests\Term\TermStoreRequest;
use App\Http\Resources\Term\TermFormResource;
use App\Http\Resources\Term\TermPaginateResource;
use App\Repositories\TermRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

class TermController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected TermRepository $termRepository,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->termRepository->paginate($request->all());
            $tableData = TermPaginateResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }


    public function create()
    {
        return $this->execute(function () {});
    }

    public function store(TermStoreRequest $request)
    {
        return $this->runTransaction(function () use ($request) {

            $data = $this->termRepository->store($request->all());
 
            return ['code' => 200, 'message' => 'Grado agregada correctamente'];
        });
    }

    public function edit($id)
    {
        return $this->runTransaction(function () use ($id) {

            $term = $this->termRepository->find($id);
            $form = new TermFormResource($term);

            return [
                'code' => 200,
                'form' => $form,
            ];
        });
    }

    public function update(TermStoreRequest $request, $id)
    {
        return $this->runTransaction(function () use ($request) {

            $data = $this->termRepository->store($request->except('image'));

            return ['code' => 200, 'message' => 'Grado modificado correctamente'];

        });
    }

    public function delete($id)
    {
        return $this->runTransaction(function () use ($id) {

            $term = $this->termRepository->find($id);
            if ($term) {
                $term->delete();
                $msg = 'Registro eliminado correctamente';
            } else {
                $msg = 'El registro no existe';
            }

            return ['code' => 200, 'message' => $msg];
        });
    }

    public function changeStatus(Request $request)
    {
        return  $this->runTransaction(function () use ($request) {
            $model = $this->termRepository->changeState(
                $request->input('id'),
                strval($request->input('value')),
                $request->input('field')
            );

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';


            return ['code' => 200, 'message' => 'term ' . $msg . ' con éxito'];
        });
    }
}
