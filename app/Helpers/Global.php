<?php

function getSubdomain($post)
{
    $host = $post->getHost(); // Obtiene el nombre de host completo
    $subdomain = explode('.', $host, 2)[0]; // Extrae el subdominio
    $subdomain = $subdomain == 'localhost' || $subdomain == '127' ? $subdomain = 'storage' : 'storage_' . $subdomain;

    return $subdomain . '/';
}

function filterComponent($query, $request, $model = null)
{
    if (isset($request["searchQuery"]) && is_string($request["searchQuery"])) {
        $request["searchQuery"] = json_decode($request["searchQuery"], 1);
    }

    // Aplicar búsqueda global si existe el término de búsqueda
    if (!empty($request["searchQuery"]['generalSearch'])) {
        $relations = $request["searchQuery"]['relationsGeneral'] ?? [];
        $query->search($request["searchQuery"]['generalSearch'], $relations);
    }


    $query->where(function ($query) use ($request, $model) {
        if (isset($request['searchQuery']['arrayFilter']) && count($request['searchQuery']['arrayFilter']) > 0) {
            foreach ($request['searchQuery']['arrayFilter'] as $value) {
                if (isset($value['custom_search']) && $value['custom_search']) {
                    continue;
                }

                //Si existe el elemento relacion y es un string debo pasarlo a array
                if (isset($value['relation']) && is_string($value['relation'])) {
                    $value['relation'] = [$value['relation']];
                }

                //Busquedas si tiene relacion o no
                if (isset($value['type']) && !empty($value['type']) && $value['type'] == 'has' && isset($value['relation']) && !empty($value['relation'])) {

                    foreach ($value['relation'] as $key => $relation) {

                        $findRelation = $relation; //relaciona  buscar
                        if ((strpos($relation, '.') !== false)) { // si la relacion o palabra tiene "."
                            $findRelation = explode(".", $relation);
                            $findRelation = $findRelation[0]; // debo obtener el primer valor y solo este se busca en la class o modelo
                        }
                        //si se pasa el modelo, la relacion debe existir en el modelo, pero si no se pasa el modelo se entiende que es sobre el modelo, de donde se usa esta funcion
                        if ((!empty($model) && method_exists($model, $findRelation)) || is_null($model)) { //busco la relacion en mi modelo
                            if ($value['search'] === 1 || $value['search'] === '1') {
                                $query->has($relation);
                            } elseif ($value['search'] === 0 || $value['search'] === '0') {
                                $query->doesntHave($relation);
                            }
                        }
                    }
                }

                // Verifica si el campo es null o está vacío
                if (isset($value['type']) && !empty($value['type']) && $value['type'] == 'null') {
                    if (isset($value['search_key']) && !empty($value['search_key'])) { // Asegúrate de que el campo esté definido
                        $field = "photo"; // Asigna el campo dinámicamente

                        if ($value['search'] === 0 || $value['search'] === '0') {
                            // Verifica si el campo es null o está vacío
                            $query->where(function ($query) use ($field) {
                                $query->whereNull($field)
                                    ->orWhere($field, ''); // Campo vacío
                            });
                        } elseif ($value['search'] === 1 || $value['search'] === '1') {
                            // Verifica si el campo no es null y no está vacío
                            $query->whereNotNull($field)
                                ->where($field, '<>', ''); // No está vacío
                        }
                    }
                }

                //Busqueda normal
                if (!empty($value['input_type']) && isset($value['search']) && !empty($value['search_key'])) {

                    if ($value['input_type'] == 'date') {
                        $query->whereDate($value['search_key'], $value['search']);
                    } elseif ($value['input_type'] == 'dateRange') {
                        $dates = explode(' to ', $value['search']);
                        $query->whereDate($value['search_key'], '>=', $dates[0])->whereDate($value['search_key'], '<=', $dates[1]);
                    } else {
                        $search = $value['search'];

                        if ($value['type'] == 'LIKE' && !is_array($search)) {
                            $search = '%' . $value['search'] . '%';
                        }
                        if (isset($value['relation'])) {
                            foreach ($value['relation'] as $key => $relation) {
                                $findRelation = $relation; //relaciona  buscar
                                if ((strpos($relation, '.') !== false)) { // si la relacion o palabra tiene "."
                                    $findRelation = explode(".", $relation);
                                    $findRelation = $findRelation[0]; // debo obtener el primer valor y solo este se busca en la class o modelo
                                }

                                //si se pasa el modelo, la relacion debe existir en el modelo, pero si no se pasa el modelo se entiende que es sobre el modelo, de donde se usa esta funcion
                                if ((!empty($model) && method_exists($model, $findRelation)) || is_null($model)) { //busco la relacion en mi modelo
                                    $query->whereHas($relation, function ($x) use ($value, $search) {
                                        if (is_array($search)) {
                                            // Verificar si es un array de objetos con clave "value"
                                            if (isset($search[0]['value'])) {
                                                $search = collect($search)->pluck('value')->toArray();
                                            }
                                            $x->whereIn($value['relation_key'], $search);
                                        } else {
                                            // Maneja el caso del valor cero
                                            if ($search === 0 || $search === '0' || !empty($search)) {
                                                $x->where($value['relation_key'], $value['type'], $search);
                                            }
                                        }
                                    });
                                }
                            }
                        } else {
                            if (is_array($search)) {
                                // Verificar si es un array de objetos con clave "value"
                                if (isset($search[0]['value'])) {
                                    $search = collect($search)->pluck('value')->toArray();
                                }
                                // Maneja el caso de valores múltiples con whereIn
                                $query->whereIn($value['search_key'], $search);
                            } else {
                                // Maneja el caso del valor cero
                                if ($search === 0 || $search === '0' || !empty($search)) {
                                    $query->where($value['search_key'], $value['type'], $search);
                                }
                            }
                        }
                    }
                }
            }
        }
    });
}

function generarColorPastelAleatorio($intensidad = 0)
{
    $min = 150 + $intensidad; // Rango mínimo ajustado por la intensidad
    $max = 255; // Rango máximo invariable

    $r = mt_rand($min, $max); // Rango para el canal rojo
    $g = mt_rand($min, $max); // Rango para el canal verde
    $b = mt_rand($min, $max); // Rango para el canal azul

    // Formatear los valores RGB como una cadena hexadecimal
    $color = sprintf('#%02X%02X%02X', $r, $g, $b);

    return $color;
}
