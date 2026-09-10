<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrayData = [
            [
                'id' => 1,
                'order' => 10,
                'title' => 'Inicio',
                'to' => 'Home',
                'icon' => 'tabler-home',
                'requiredPermission' => 'menu.home',
            ],
            [
                'id' => 2,
                'order' => 20,
                'title' => 'Compañias',
                'to' => 'Company-List',
                'icon' => ' tabler-building',
                'requiredPermission' => 'company.list',
            ],
            [
                'id' => 3,
                'order' => 30,
                'title' => 'Usuarios',
                'icon' => 'tabler-user-shield',
                'requiredPermission' => 'menu.user.father',
            ],
            [
                'id' => 4,
                'order' => 40,
                'title' => 'Usuarios',
                'to' => 'User-List',
                'icon' => '',
                'father' => 3,
                'requiredPermission' => 'menu.user',
            ],
            [
                'id' => 5,
                'order' => 50,
                'title' => 'Roles',
                'to' => 'Role-List',
                'icon' => '',
                'father' => 3,
                'requiredPermission' => 'menu.role',
            ],
            [
                'id' => 6,
                'order' => 60,
                'title' => 'Banner',
                'to' => 'Banner-List',
                'icon' => 'tabler-border-inner',
                'requiredPermission' => 'banner.list',
            ],
            [
                'id' => 7,
                'order' => 70,
                'title' => 'Materias',
                'to' => 'Subject-List',
                'icon' => 'tabler-checklist',
                'requiredPermission' => 'subject.list',
            ],
            [
                'id' => 8,
                'order' => 80,
                'title' => 'Grados',
                'to' => 'Grade-List',
                'icon' => 'tabler-layers-intersect',
                'requiredPermission' => 'grade.list',
            ],
            [
                'id' => 9,
                'order' => 90,
                'title' => 'Servicios',
                'to' => 'Service-List',
                'icon' => 'tabler-device-gamepad-3',
                'requiredPermission' => 'service.list',
            ],
            [
                'id' => 10,
                'order' => 100,
                'title' => 'Estudiantes',
                'to' => 'Student-List',
                'icon' => 'tabler-users-group',
                'requiredPermission' => 'student.list',
            ],
            [
                'id' => 11,
                'order' => 110,
                'title' => 'Docentes',
                'to' => 'Teacher-List',
                'icon' => 'tabler-user-square-rounded',
                'requiredPermission' => 'teacher.list',
            ],
            [
                'id' => 12,
                'order' => 120,
                'title' => 'Notas',
                'to' => 'Note-Index',
                'icon' => 'tabler-database-edit',
                'requiredPermission' => 'note.index',
            ],
            [
                'id' => 13,
                'order' => 130,
                'title' => 'Ingresos/Egresos',
                'to' => 'Enrollments&Exits-Index',
                'icon' => 'tabler-database-edit',
                'requiredPermission' => 'menu.enrollments&Exits',
            ],
            [
                'id' => 14,
                'order' => 140,
                'title' => 'Periodos',
                'to' => 'Term-List',
                'icon' => 'tabler-calendar',
                'requiredPermission' => 'term.list',
            ],
            [
                'id' => 15,
                'order' => 150,
                'title' => 'Materia Pendiente',
                'icon' => 'tabler-parking',
                'to' => 'PendingRegistration-List',
                'requiredPermission' => 'pendingRegistration.list',
            ],
            [
                'id' => 16,
                'order' => 160,
                'title' => 'Constancias',
                'icon' => 'tabler-file',
                'requiredPermission' => 'documents.menu',
            ],
            [
                'id' => 17,
                'order' => 170,
                'title' => 'Individuales',
                'icon' => 'tabler-file',
                'to' => 'Documents-Individual',
                'father' => 16,
                'requiredPermission' => 'documents.individual',
            ],
            [
                'id' => 18,
                'order' => 180,
                'title' => 'Masivas',
                'icon' => 'tabler-file',
                'to' => 'Documents-Masive',
                'father' => 16,
                'requiredPermission' => 'documents.masive',
            ],
            [
                'id' => 19,
                'order' => 900,
                'title' => 'Mantenimiento',
                'icon' => 'tabler-tool',
                'to' => null,
                'requiredPermission' => 'maintenance.menu',
            ],
            [
                'id' => 20,
                'order' => 910,
                'title' => 'Auditoría de archivos',
                'icon' => 'tabler-file-search',
                'to' => 'Maintenance-Files',
                'father' => 19,
                'requiredPermission' => 'maintenance.files',
            ],
            [
                'id' => 21,
                'order' => 920,
                'title' => 'Revisión de datos',
                'icon' => 'tabler-checkup-list',
                'to' => 'Maintenance-Data',
                'father' => 19,
                'requiredPermission' => 'maintenance.data',
            ],
            // Promoción de alumnos. Sin id fijo a propósito: estas entradas ya las crea la
            // migración del módulo, así que aquí se buscan por su permiso para actualizar
            // las que existan en vez de duplicarlas con otro id.
            [
                'order' => 105,
                'title' => 'Promoción de alumnos',
                'icon' => 'tabler-arrow-big-up-lines',
                'requiredPermission' => 'studentPromotion.menu',
            ],
            [
                'order' => 1,
                'title' => 'Cargar promoción',
                'to' => 'StudentPromotion-Index',
                'icon' => '',
                'father_permission' => 'studentPromotion.menu',
                'requiredPermission' => 'studentPromotion.list',
            ],
            [
                'order' => 2,
                'title' => 'Cierre del período',
                'to' => 'StudentPromotion-Closure',
                'icon' => '',
                'father_permission' => 'studentPromotion.menu',
                'requiredPermission' => 'studentPromotion.closure',
            ],
        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($arrayData));

        foreach ($arrayData as $key => $value) {
            // Las entradas con id fijo se buscan por id, como siempre. Las que no lo traen
            // se identifican por su permiso, que es único y no depende del autoincremento.
            $data = isset($value['id'])
                ? Menu::find($value['id'])
                : Menu::where('requiredPermission', $value['requiredPermission'])->first();

            if (! $data) {
                $data = new Menu;
            }

            if (isset($value['id'])) {
                $data->id = $value['id'];
            }

            $data->order = $value['order'];
            $data->title = $value['title'];
            $data->to = $value['to'] ?? null;
            $data->icon = $value['icon'];

            // El padre puede venir por id o por el permiso del menú padre, para los casos
            // en que su id lo asignó el autoincremento y varía entre entornos.
            $data->father = isset($value['father_permission'])
                ? Menu::where('requiredPermission', $value['father_permission'])->value('id')
                : ($value['father'] ?? null);

            $data->requiredPermission = $value['requiredPermission'];
            $data->save();
        }

        $bar->finish(); // Finalizar la barra
    }
}
