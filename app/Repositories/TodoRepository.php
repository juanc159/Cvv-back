<?php

namespace App\Repositories;

use App\Interfaces\TodoInterface;
use App\Models\Service;

class TodoRepository implements TodoInterface
{
    private $model;
    public function __construct()
    {
        $this->model = new Service();
    }

    public function getTodos()
    {
        return $this->model::all();
    }

    public function saveTodo($data)
    {
        $model = new $this->model();
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function getById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function updateTodo($id, $data)
    {
        $todo = $this->model->findOrFail($id);
        $todo->fill($data);
        $todo->save();
        return $todo;
    }

    public function deleteTodo($id)
    {
        $todo = $this->model->findOrFail($id);
        $todo->delete();
    }

    public function changeStatus($id, $status)
    {
        $todo = $this->model->findOrFail($id);
        $todo->status = $status;
        $todo->save();
        return $todo;
    }
    
}
