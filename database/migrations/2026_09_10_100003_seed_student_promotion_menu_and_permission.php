<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Da de alta el módulo de Promoción de alumnos en el menú y crea su permiso.
 *
 * El permiso se asigna únicamente al Super Administrador: la promoción mueve de grado a
 * secciones completas y saca de su sección a quien no venga en el archivo, así que no
 * conviene que quede abierto por defecto. Desde el módulo de Roles se le puede dar a
 * quien corresponda.
 */
return new class extends Migration
{
    private const PERMISSION = 'studentPromotion.list';

    public function up(): void
    {
        if (DB::table('permissions')->where('name', self::PERMISSION)->exists()) {
            return;
        }

        // El menú puede haber quedado de un intento anterior: se reutiliza en vez de
        // duplicar la entrada.
        $menu = DB::table('menus')->where('requiredPermission', self::PERMISSION)->first();

        $menuId = $menu?->id ?? DB::table('menus')->insertGetId([
            'order' => 105,
            'title' => 'Promoción de alumnos',
            'to' => 'StudentPromotion-Index',
            'icon' => 'tabler-arrow-big-up-lines',
            'requiredPermission' => self::PERMISSION,
            'father' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // permissions.id es char(36) y no autoincremental, así que el id va explícito.
        $permissionId = (string) Str::uuid();

        DB::table('permissions')->insert([
            'id' => $permissionId,
            'name' => self::PERMISSION,
            'guard_name' => 'api',
            'description' => 'Visualizar Módulo Promoción de alumnos',
            'menu_id' => $menuId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $superAdmin = DB::table('roles')->where('name', 'Super Administrador')->first();

        if ($superAdmin) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId,
                'role_id' => $superAdmin->id,
            ]);
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('name', self::PERMISSION)->first();

        if (! $permission) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
        DB::table('permissions')->where('id', $permission->id)->delete();

        if ($permission->menu_id) {
            DB::table('menus')->where('id', $permission->menu_id)->delete();
        }
    }
};
