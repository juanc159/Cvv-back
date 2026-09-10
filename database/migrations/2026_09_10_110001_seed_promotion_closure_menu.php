<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Convierte "Promoción de alumnos" en un menú con dos opciones: la carga de archivos y el
 * cierre del período.
 *
 * El cierre lleva permiso propio porque es donde se decide quién queda retirado o egresado,
 * que no es lo mismo que mover alumnos de grado.
 */
return new class extends Migration
{
    private const PARENT_PERMISSION = 'studentPromotion.menu';
    private const CLOSURE_PERMISSION = 'studentPromotion.closure';

    public function up(): void
    {
        $listPermission = DB::table('permissions')->where('name', 'studentPromotion.list')->first();

        if (! $listPermission) {
            return;
        }

        // El menú que hoy apunta a la carga pasa a ser el padre, sin destino propio.
        $currentMenuId = $listPermission->menu_id;

        DB::table('menus')->where('id', $currentMenuId)->update([
            'to' => null,
            'requiredPermission' => self::PARENT_PERMISSION,
            'updated_at' => now(),
        ]);

        $childMenus = [
            [
                'order' => 1,
                'title' => 'Cargar promoción',
                'to' => 'StudentPromotion-Index',
                'permission' => 'studentPromotion.list',
                'description' => 'Visualizar Módulo Promoción de alumnos',
            ],
            [
                'order' => 2,
                'title' => 'Cierre del período',
                'to' => 'StudentPromotion-Closure',
                'permission' => self::CLOSURE_PERMISSION,
                'description' => 'Visualizar Cierre del período escolar',
            ],
        ];

        $superAdmin = DB::table('roles')->where('name', 'Super Administrador')->first();

        foreach ($childMenus as $child) {
            if (DB::table('menus')->where('requiredPermission', $child['permission'])->where('father', $currentMenuId)->exists()) {
                continue;
            }

            $menuId = DB::table('menus')->insertGetId([
                'order' => $child['order'],
                'title' => $child['title'],
                'to' => $child['to'],
                'icon' => '',
                'requiredPermission' => $child['permission'],
                'father' => $currentMenuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existing = DB::table('permissions')->where('name', $child['permission'])->first();

            if ($existing) {
                // El permiso de la carga ya existía: solo se reapunta al menú hijo.
                DB::table('permissions')->where('id', $existing->id)->update([
                    'menu_id' => $menuId,
                    'updated_at' => now(),
                ]);

                continue;
            }

            $permissionId = (string) Str::uuid();

            DB::table('permissions')->insert([
                'id' => $permissionId,
                'name' => $child['permission'],
                'guard_name' => 'api',
                'description' => $child['description'],
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($superAdmin) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $superAdmin->id,
                ]);
            }
        }

        // Permiso del menú padre: sin él la sección no se dibuja.
        if (! DB::table('permissions')->where('name', self::PARENT_PERMISSION)->exists()) {
            $parentPermissionId = (string) Str::uuid();

            DB::table('permissions')->insert([
                'id' => $parentPermissionId,
                'name' => self::PARENT_PERMISSION,
                'guard_name' => 'api',
                'description' => 'Visualizar menú Promoción de alumnos',
                'menu_id' => $currentMenuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($superAdmin) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $parentPermissionId,
                    'role_id' => $superAdmin->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ([self::PARENT_PERMISSION, self::CLOSURE_PERMISSION] as $name) {
            $permission = DB::table('permissions')->where('name', $name)->first();

            if (! $permission) {
                continue;
            }

            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }

        DB::table('menus')->whereIn('requiredPermission', [self::CLOSURE_PERMISSION, 'studentPromotion.list'])
            ->whereNotNull('father')->delete();
    }
};
