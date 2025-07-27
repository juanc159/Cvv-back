<?php

namespace App\Interfaces;

interface TodoInterface
{
    public function getTodos();

    public function saveTodo($data);

    public function getById($id);

    public function updateTodo($id, $data);

    public function deleteTodo($id);

    public function changeStatus($id, $status);
}
