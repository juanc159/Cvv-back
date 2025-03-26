<?php

namespace App\Services;

use App\Interfaces\TodoInterface;
use Exception;

class TodoService
{

    public function __construct(protected TodoInterface $todoInterface)
    {
        $this->todoInterface = $todoInterface;
    }

    public function createTodoAndAssignStatus($data)
    {
        return $this->todoInterface->saveTodo($data);
    }

    public function getAllTodos()
    {
        return $this->todoInterface->getTodos();
    }

    public function getTodoById($id)
    {
        return $this->todoInterface->getById($id);
    }

    public function updateTodoDetails($id, $data)
    {
        $todo = $this->todoInterface->getById($id);
        $todo->fill($data);
        $todo->save();
        return $todo;
    }

    public function updateTodoStatus($id, $status)
    {
        if (!in_array($status, ['completed', 'pending'])) {
            throw new Exception("Status no válido");
        }

        return $this->todoInterface->changeStatus($id, $status);
    }

    public function deleteTodoById($id)
    {
        $todo = $this->todoInterface->getById($id);
        if (!$todo) {
            throw new Exception('Todo no encontrado');
        }

        $this->todoInterface->deleteTodo($id);
    }
}
