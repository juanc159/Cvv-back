<?php

namespace App\Repositories;

use App\Interfaces\TodoInterface;
use Illuminate\Support\Facades\Redis;

class TodoRepositoryRedis implements TodoInterface
{
    // Nombre de la clave de Redis que almacenará los todos
    private $redisKey = 'todos';

    public function getTodos()
    {
        // Obtener todos los "todos" desde Redis, puedes almacenarlos como un conjunto o hash
        return Redis::hgetall($this->redisKey);
    }

    public function saveTodo($data)
    {
        // Aquí creamos un identificador único para el "todo", por ejemplo, un ID incremental
        $id = Redis::incr('todo:id'); // Incrementamos un contador para el ID
        $data['id'] = $id;

        // Guardamos el "todo" como un hash
        Redis::hmset("todo:$id", $data);

        return $data; // Retornamos los datos almacenados
    }

    public function getById($id)
    {
        // Obtener un solo "todo" usando el ID
        return Redis::hgetall("todo:$id");
    }

    public function updateTodo($id, $data)
    {
        // Actualizar un "todo" con el ID especificado
        if (Redis::exists("todo:$id")) {
            Redis::hmset("todo:$id", $data);
            return $data;
        }

        // Si no existe el "todo", lanzar un error
        throw new \Exception("Todo not found");
    }

    public function deleteTodo($id)
    {
        // Eliminar un "todo" de Redis usando el ID
        if (Redis::exists("todo:$id")) {
            Redis::del("todo:$id");
        } else {
            throw new \Exception("Todo not found");
        }
    }

    public function changeStatus($id, $status)
    {
        // Cambiar el estado de un "todo"
        if (Redis::exists("todo:$id")) {
            Redis::hset("todo:$id", 'status', $status);
            return Redis::hgetall("todo:$id");
        }

        // Si no existe el "todo", lanzar un error
        throw new \Exception("Todo not found");
    }
}
