<?php

namespace App\Http\Controllers;
 
use App\Services\TodoService;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected TodoService $todoService,
    ) {}

    public function list()
    {
        return $this->todoService->getAllTodos();
    }

    public function store(Request $request)
    {
        return $this->runTransaction(function () use ($request) {

            $request->validate([
                "company_id" => "required",
                "title" => "required",
                "image" => "required",
                "html" => "required",
            ]);

            $data = $this->todoService->createTodoAndAssignStatus($request->all());

            return [
                "code" => 200,
                "message" => "Registrado con éxito",
                "data" => $data,
            ];
        }, debug: true);
    }

    public function edit($id)
    {
        return $this->runTransaction(function () use ($id) {

            $data = $this->todoService->getTodoById($id);

            return [
                'code' => 200,
                'message' => 'Elemento encontrado',
                'data' => $data,
            ];

        });
    }

    public function update(Request $request, $id)
    {
        return $this->runTransaction(function () use ($request, $id) {

            $request->validate([
                "company_id" => "required",
                "title" => "required",
                "image" => "required",
                "html" => "required",
            ]);

            $data = $this->todoService->updateTodoDetails($id, $request->all());

            return [
                "code" => 200,
                "message" => "Actualizado con éxito",
                "data" => $data,
            ];
        }, debug: true);
    }

    public function delete($id)
    {
        return $this->runTransaction(function () use ($id) {
            $this->todoService->deleteTodoById($id);
            return [
                "code" => 200,
                "message" => "Eliminado con éxito",
            ];
        }, debug: true);
    }

    public function changeStatus(Request $request)
    {
        return $this->runTransaction(function () use ($request) {
            $request->validate([
                'id' => 'required|exists:todos,id',
                'status' => 'required|in:completed,pending', // Suponiendo que los estados son 'completed' o 'pending'
            ]);

            $todo = $this->todoService->updateTodoStatus($request->id, $request->status);

            return [
                "code" => 200,
                "message" => "Estado actualizado con éxito",
                "data" => $todo,
            ];
        }, debug: true);
    }
}
