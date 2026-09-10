<?php

namespace App\Services;

use App\Models\PromotionBatchRow;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cierre del año escolar: qué pasa con los alumnos que quedaron sin grado ni sección.
 *
 * Cuando se promueve sección por sección, los alumnos que no aparecen en ningún archivo
 * quedan sin grado. Eso mezcla tres situaciones muy distintas que hasta ahora eran
 * indistinguibles entre sí:
 *
 *   - todavía no vino a inscribirse,
 *   - se retiró del colegio,
 *   - terminó su último año y egresó.
 *
 * Este servicio permite separarlas. Mientras no se separen, esos alumnos no salen en
 * ningún listado ni en el consolidado y nadie se entera de que existen.
 */
class SchoolYearClosureService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_WITHDRAWAL = 'withdrawal';
    public const STATUS_GRADUATION = 'graduation';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Sin decidir',
        self::STATUS_WITHDRAWAL => 'Retirado',
        self::STATUS_GRADUATION => 'Egresado',
    ];

    /**
     * Alumnos sin grado ni sección del colegio, con lo último que se sabe de cada uno.
     *
     * @param  array  $filters  company_id, status, type_education_id, search
     */
    public function unassigned(array $filters): Collection
    {
        $students = Student::with(['type_education'])
            ->where('company_id', $filters['company_id'])
            ->whereNull('grade_id')
            ->when($filters['type_education_id'] ?? null,
                fn ($query, $id) => $query->where('type_education_id', $id))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhere('identity_document', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->get();

        if ($students->isEmpty()) {
            return collect();
        }

        $studentIds = $students->pluck('id');
        $records = $this->activeRecords($studentIds);
        $origins = $this->lastKnownOrigins($studentIds);

        $result = $students->map(function (Student $student) use ($records, $origins) {
            $record = $records->get($student->id);
            $status = $record ? $record->type : self::STATUS_PENDING;

            return [
                'id' => $student->id,
                'identity_document' => $student->identity_document,
                'full_name' => $student->full_name,
                'type_education' => $student->type_education?->name,
                'is_active' => (bool) $student->is_active,
                'status' => $status,
                'status_label' => self::STATUS_LABELS[$status] ?? $status,
                'date' => $record?->date,
                'reason' => $record?->reason,
                // De dónde salió, si fue una promoción la que lo dejó sin grado.
                'origin' => $origins[$student->id] ?? null,
            ];
        });

        if (! empty($filters['status'])) {
            $result = $result->where('status', $filters['status'])->values();
        }

        return $result;
    }

    /**
     * Registra la decisión sobre un grupo de alumnos.
     *
     * @param  string  $action  withdrawal, graduation o clear
     */
    public function classify(array $studentIds, string $action, array $data): int
    {
        if (empty($studentIds)) {
            return 0;
        }

        $affected = 0;

        DB::transaction(function () use ($studentIds, $action, $data, &$affected) {
            foreach ($studentIds as $studentId) {
                $student = Student::find($studentId);

                if (! $student) {
                    continue;
                }

                // Solo puede haber una situación vigente por alumno: la anterior se cierra
                // antes de abrir la nueva, para no acumular registros contradictorios.
                StudentWithdrawal::where('student_id', $studentId)
                    ->whereNull('reactivated_at')
                    ->update(['reactivated_at' => now()]);

                if ($action === 'clear') {
                    // Vuelve a "sin decidir" y se reactiva, porque la baja era lo que lo
                    // mantenía fuera.
                    if (! $student->is_active) {
                        $student->is_active = true;
                        $student->save();
                    }

                    $affected++;

                    continue;
                }

                StudentWithdrawal::create([
                    'student_id' => $studentId,
                    'type' => $action,
                    'date' => $data['date'],
                    'reason' => $data['reason'],
                ]);

                // Desactivar es opcional: un alumno retirado que quizá vuelva el mes que
                // viene puede conservar su acceso hasta que el colegio decida lo contrario.
                if (! empty($data['deactivate'])) {
                    $student->is_active = false;
                    $student->save();
                }

                $affected++;
            }
        });

        return $affected;
    }

    /**
     * Resumen por situación, para las tarjetas de la pantalla.
     */
    public function summary(string $companyId): array
    {
        $studentIds = Student::where('company_id', $companyId)->whereNull('grade_id')->pluck('id');

        $records = $this->activeRecords($studentIds);

        $counts = [
            self::STATUS_PENDING => 0,
            self::STATUS_WITHDRAWAL => 0,
            self::STATUS_GRADUATION => 0,
        ];

        foreach ($studentIds as $studentId) {
            $status = $records->get($studentId)?->type ?? self::STATUS_PENDING;
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        $counts['total'] = $studentIds->count();

        return $counts;
    }

    /**
     * La baja o el egreso vigente de cada alumno (los cerrados por un reintegro no cuentan).
     */
    private function activeRecords(Collection $studentIds): Collection
    {
        return StudentWithdrawal::whereIn('student_id', $studentIds)
            ->whereNull('reactivated_at')
            ->orderBy('created_at')
            ->get()
            ->keyBy('student_id');
    }

    /**
     * Último grado y sección conocidos, según la promoción que dejó al alumno sin grado.
     *
     * Los alumnos que quedaron sin grado antes de que existiera este módulo no tienen este
     * dato: en su caso no hay a dónde mirar y se muestra vacío.
     */
    private function lastKnownOrigins(Collection $studentIds): array
    {
        return PromotionBatchRow::whereIn('student_id', $studentIds)
            ->where('action', PromotionBatchRow::ACTION_UNASSIGN)
            ->where('is_applied', true)
            ->orderBy('created_at')
            ->get(['student_id', 'previous_grade_name', 'previous_section_name'])
            ->mapWithKeys(fn ($row) => [
                $row->student_id => trim($row->previous_grade_name . ' ' . ($row->previous_section_name ?? '')),
            ])
            ->all();
    }
}
