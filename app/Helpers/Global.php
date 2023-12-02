<?php

function getSubdomain($post)
{
    $host = $post->getHost(); // Obtiene el nombre de host completo
    $subdomain = explode('.', $host, 2)[0]; // Extrae el subdominio
    $subdomain = $subdomain == 'localhost' || $subdomain == '127' ? $subdomain = 'storage' : 'storage_'.$subdomain;

    return $subdomain.'/';
}

function filterComponent($query, $request)
{
    $query->where(function ($query) use ($request) {
        if (isset($request['searchQuery']) && count($request['searchQuery']) > 0) {
            foreach ($request['searchQuery'] as $value) {
                if ($value['input_type'] == 'date') {
                    $dates = explode(' to ', $value['search']);

                    $query->whereDate($value['input'], '>=', $dates[0])->whereDate($value['input'], '<=', $dates[1]);
                } else {
                    $search = $value['search'];
                    if ($value['type'] == 'LIKE') {
                        $search = '%'.$value['search'].'%';
                    }

                    $query->orWhere($value['input'], $value['type'], $search);
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
