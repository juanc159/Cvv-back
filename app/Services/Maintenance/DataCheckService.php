<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\DB;

/**
 * Revisión de consistencia de los datos del colegio.
 *
 * Cada chequeo busca una situación que hoy solo se descubre por sus síntomas semanas
 * después (una alumna que no sale en el consolidado, un botón que da error). Acá se ven
 * todos juntos.
 *
 * Es de SOLO LECTURA y no tiene limpieza automática: arreglar cada caso es una decisión
 * del colegio (a qué grado va un alumno, qué docente lleva una sección).
 */
class DataCheckService
{
    public const SEVERITY_ERROR = 'error';

    public const SEVERITY_WARNING = 'warning';

    /** Tope de casos guardados por chequeo. El total siempre es el real. */
    protected const MAX_ITEMS = 500;

    public function __construct(
        protected FilePathRegistry $registry,
    ) {}

    /**
     * Corre todos los chequeos.
     *
     * @param  string|null  $companyId  Limita al colegio indicado; null revisa todos
     * @return array{checks: array, summary: array}
     */
    public function run(?string $companyId = null): array
    {
        $checks = [
            $this->sectionsWithoutTeacher($companyId),
            $this->sectionsWithMissingSubjects($companyId),
            $this->inactiveTeachersWithAssignments($companyId),
            $this->studentsWithoutGrade($companyId),
            $this->duplicatedDocumentsAcrossCompanies(),
            $this->sharedFilePaths(),
        ];

        $withFindings = array_filter($checks, fn ($check) => $check['count'] > 0);

        return [
            'checks' => $checks,
            'summary' => [
                'total_checks' => count($checks),
                'with_findings' => count($withFindings),
                'errors' => count(array_filter($withFindings, fn ($c) => $c['severity'] === self::SEVERITY_ERROR)),
                'total_cases' => array_sum(array_column($checks, 'count')),
            ],
        ];
    }

    /**
     * Grados/secciones con alumnos pero sin ningún docente asignado.
     *
     * Es la causa de que un alumno no aparezca en el consolidado de notas: el reporte
     * recorre las asignaciones docentes, no los alumnos.
     */
    protected function sectionsWithoutTeacher(?string $companyId): array
    {
        $rows = DB::table('students as s')
            ->join('grades as g', 'g.id', '=', 's.grade_id')
            ->join('sections as sec', 'sec.id', '=', 's.section_id')
            ->leftJoin('type_education as te', 'te.id', '=', 's.type_education_id')
            ->selectRaw('te.name as tipo, g.name as grado, sec.name as seccion, s.grade_id, s.section_id, COUNT(*) as alumnos')
            ->where('s.is_active', 1)
            ->whereNotNull('s.grade_id')
            ->whereNotNull('s.section_id')
            ->when($companyId, fn ($q) => $q->where('s.company_id', $companyId))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('student_withdrawals as sw')
                ->whereColumn('sw.student_id', 's.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('teacher_complementaries as tc')
                ->join('teachers as t', 't.id', '=', 'tc.teacher_id')
                ->whereColumn('tc.grade_id', 's.grade_id')
                ->whereColumn('tc.section_id', 's.section_id')
                ->whereColumn('t.company_id', 's.company_id')
                ->whereColumn('t.type_education_id', 's.type_education_id'))
            ->groupBy('te.name', 'g.name', 'sec.name', 's.grade_id', 's.section_id')
            ->get();

        return $this->check(
            'sections_without_teacher',
            'Secciones sin docente asignado',
            'Tienen alumnos activos pero ningún docente. Esos alumnos no aparecen en el consolidado de notas.',
            self::SEVERITY_ERROR,
            $rows->map(fn ($r) => [
                'detalle' => "{$r->tipo} · {$r->grado} · Sección {$r->seccion}",
                'alumnos' => $r->alumnos,
            ])->all(),
            'Asignar un docente desde Docentes → editar → Información complementaria.'
        );
    }

    /**
     * Secciones donde, sumando todas sus asignaciones, faltan materias del nivel.
     *
     * Los alumnos salen en el consolidado pero con columnas incompletas.
     */
    protected function sectionsWithMissingSubjects(?string $companyId): array
    {
        // Las materias se asignan POR GRADO (grade_subjects), no por nivel: en Media cada
        // año tiene su propio pénsum. Comparar contra todas las del nivel daría falsos
        // positivos en masa.
        $subjectsByGrade = DB::table('grade_subjects as gs')
            ->join('subjects as sub', 'sub.id', '=', 'gs.subject_id')
            ->select('gs.grade_id', 'gs.subject_id')
            ->where('sub.is_active', 1)
            ->when($companyId, fn ($q) => $q->where('gs.company_id', $companyId))
            ->get()
            ->groupBy('grade_id')
            ->map(fn ($rows) => $rows->pluck('subject_id')->map(fn ($id) => (string) $id)->all());

        $assignments = DB::table('teacher_complementaries as tc')
            ->join('teachers as t', 't.id', '=', 'tc.teacher_id')
            ->leftJoin('grades as g', 'g.id', '=', 'tc.grade_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'tc.section_id')
            ->leftJoin('type_education as te', 'te.id', '=', 't.type_education_id')
            ->selectRaw('t.type_education_id, te.name as tipo, tc.grade_id, tc.section_id, g.name as grado, sec.name as seccion, tc.subject_ids')
            ->when($companyId, fn ($q) => $q->where('t.company_id', $companyId))
            ->get();

        // Se unen las materias de todas las asignaciones de cada grado+sección: entre
        // varios docentes pueden cubrir el total, y ahí no falta nada.
        $covered = [];

        foreach ($assignments as $row) {
            $key = $row->grade_id . '|' . $row->section_id;

            if (! isset($covered[$key])) {
                $covered[$key] = [
                    'tipo' => $row->tipo,
                    'grado' => $row->grado,
                    'seccion' => $row->seccion,
                    'grade_id' => $row->grade_id,
                    'subjects' => [],
                ];
            }

            foreach (array_filter(array_map('trim', explode(',', (string) $row->subject_ids))) as $id) {
                $covered[$key]['subjects'][$id] = true;
            }
        }

        $items = [];

        foreach ($covered as $entry) {
            $expected = $subjectsByGrade[$entry['grade_id']] ?? [];

            // Si el grado no tiene pénsum cargado no se puede saber qué falta.
            if (empty($expected)) {
                continue;
            }

            $missing = array_diff($expected, array_keys($entry['subjects']));

            if (empty($missing)) {
                continue;
            }

            $items[] = [
                'detalle' => "{$entry['tipo']} · {$entry['grado']} · Sección {$entry['seccion']}",
                'materias_faltantes' => count($missing),
                'del_pensum' => count($expected),
            ];
        }

        return $this->check(
            'sections_missing_subjects',
            'Secciones con materias sin asignar',
            'Sumando todos sus docentes, a estas secciones les faltan materias del nivel. Sus alumnos salen en el consolidado con columnas incompletas.',
            self::SEVERITY_WARNING,
            $items,
            'Completar las materias en la información complementaria del docente.'
        );
    }

    /**
     * Docentes inactivos que siguen figurando como responsables de una sección.
     */
    protected function inactiveTeachersWithAssignments(?string $companyId): array
    {
        $rows = DB::table('teachers as t')
            ->join('teacher_complementaries as tc', 'tc.teacher_id', '=', 't.id')
            ->selectRaw("CONCAT(t.name, ' ', t.last_name) as docente, COUNT(tc.id) as asignaciones")
            ->where('t.is_active', 0)
            ->when($companyId, fn ($q) => $q->where('t.company_id', $companyId))
            ->groupBy('t.id', 't.name', 't.last_name')
            ->get();

        return $this->check(
            'inactive_teachers_with_assignments',
            'Docentes inactivos con secciones a cargo',
            'Están marcados como inactivos pero siguen siendo el único vínculo entre esas secciones y sus materias.',
            self::SEVERITY_WARNING,
            $rows->map(fn ($r) => ['detalle' => $r->docente, 'asignaciones' => $r->asignaciones])->all(),
            'Reasignar esas secciones a un docente activo.'
        );
    }

    /**
     * Alumnos activos sin grado ni sección.
     */
    protected function studentsWithoutGrade(?string $companyId): array
    {
        $rows = DB::table('students as s')
            ->leftJoin('type_education as te', 'te.id', '=', 's.type_education_id')
            ->selectRaw('te.name as tipo, COUNT(*) as alumnos')
            ->where('s.is_active', 1)
            ->whereNull('s.grade_id')
            ->when($companyId, fn ($q) => $q->where('s.company_id', $companyId))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('student_withdrawals as sw')
                ->whereColumn('sw.student_id', 's.id'))
            ->groupBy('te.name')
            ->get();

        return $this->check(
            'students_without_grade',
            'Alumnos activos sin grado ni sección',
            'Figuran como activos pero sin asignación. Suele ser un retiro que nunca se registró como tal.',
            self::SEVERITY_ERROR,
            $rows->map(fn ($r) => ['detalle' => $r->tipo ?? '(sin tipo)', 'alumnos' => $r->alumnos])->all(),
            'Asignarles grado y sección, o registrarlos como retirados.'
        );
    }

    /**
     * Misma cédula en más de un colegio.
     *
     * El índice único es por colegio, así que es válido, pero cualquier búsqueda que
     * olvide filtrar por colegio le puede pegar al alumno equivocado.
     */
    protected function duplicatedDocumentsAcrossCompanies(): array
    {
        $rows = DB::table('students')
            ->selectRaw('identity_document, COUNT(*) as registros, COUNT(DISTINCT company_id) as colegios')
            ->whereNotNull('identity_document')
            ->where('identity_document', '<>', '')
            ->groupBy('identity_document')
            ->havingRaw('COUNT(DISTINCT company_id) > 1')
            ->get();

        return $this->check(
            'duplicated_documents',
            'Cédulas repetidas entre colegios',
            'El mismo documento existe en más de una institución. Cualquier proceso que busque por cédula sin filtrar por colegio puede afectar al alumno equivocado.',
            self::SEVERITY_WARNING,
            $rows->map(fn ($r) => [
                'detalle' => $r->identity_document,
                'registros' => $r->registros,
                'colegios' => $r->colegios,
            ])->all(),
            'Verificar si son la misma persona matriculada en dos colegios.'
        );
    }

    /**
     * Una misma ruta de archivo usada por más de un registro.
     *
     * Importa porque al reemplazar el archivo de uno se borraría el del otro.
     */
    protected function sharedFilePaths(): array
    {
        $items = [];

        foreach (FilePathRegistry::PATH_COLUMNS as $source) {
            if (! $this->registry->hasSource($source)) {
                continue;
            }

            $rows = DB::table($source['table'])
                ->selectRaw($source['column'] . ' as path, COUNT(*) as veces')
                ->whereNotNull($source['column'])
                ->where($source['column'], '<>', '')
                ->groupBy($source['column'])
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($rows as $row) {
                $items[] = [
                    'detalle' => $row->path,
                    'origen' => $source['label'],
                    'registros' => $row->veces,
                ];
            }
        }

        return $this->check(
            'shared_file_paths',
            'Archivos compartidos por varios registros',
            'La misma ruta figura en más de una fila. Al reemplazar el archivo de una, se borra el de la otra.',
            self::SEVERITY_ERROR,
            $items,
            'Volver a cargar el archivo en cada registro para que cada uno tenga el suyo.'
        );
    }

    /**
     * Da forma a un chequeo, recortando el detalle pero conservando el total real.
     */
    protected function check(string $key, string $label, string $description, string $severity, array $items, string $hint): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'severity' => $severity,
            'count' => count($items),
            'truncated' => count($items) > self::MAX_ITEMS,
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'hint' => $hint,
        ];
    }
}
