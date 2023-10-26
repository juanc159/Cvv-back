<?php

function getSubdomain($post)
{
    $host = $post->getHost(); // Obtiene el nombre de host completo
    $subdomain = explode('.', $host, 2)[0]; // Extrae el subdominio
    $subdomain = $subdomain == 'localhost' || $subdomain == '127' ? $subdomain = 'storage' : 'storage_'.$subdomain;

    return $subdomain.'/';
}

function getPermissionsList($query, $request, $branchRepository, $modules=[])
{
    $user = auth()->user();
    $query->where('user_created_id', $user->id);

    if (! empty($request['status'])) {
        $query->where('status', $request['status']);
    }

    $permissions = $user->permissions->pluck('name')->toArray();
    $arrayPermissions = collect();

    $branches = $branchRepository->list(['typeData' => 'all']);
    foreach ($branches as $branch) {
        foreach ($modules as $module_id) {
            $permissionName = $module_id.'.list.branch_'.$branch->id;
            if (in_array($permissionName, $permissions) || in_array($module_id.'.branch.list.national', $permissions)) {
                $arrayPermissions->push($branch->id);
            }
        }
    }

    $query->orWhereHas('userCreated.staff', function ($staffQuery) use ($arrayPermissions) {
        $staffQuery->whereIn('branch_id', $arrayPermissions);
    });
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
                    if ($value['type'] == "LIKE")
                        $search = "%" . $value['search'] . "%";

                    $query->orWhere($value['input'], $value['type'], $search);
                }
            }
        }
    });
}
