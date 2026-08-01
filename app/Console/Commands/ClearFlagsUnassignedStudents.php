<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Apaga solvencia / boletín / notas a los alumnos que quedaron sin grado ni sección.
 *
 * Cuando un alumno se retira o no se reinscribe, el colegio le quita el grado y la sección,
 * pero las banderas de documentos quedaban encendidas. Eso hacía que el portal les siguiera
 * ofreciendo la constancia (y en Primaria reventaba con un 500 porque no hay grado que buscar).
 */
class ClearFlagsUnassignedStudents extends Command
{
    protected $signature = 'students:clear-flags-unassigned
                            {--company= : Limitar a una empresa (company_id)}
                            {--type= : Limitar a un tipo de educación (type_education_id)}
                            {--apply : Aplica los cambios. Sin esta bandera solo simula}
                            {--force : Omite la confirmación interactiva}';

    protected $description = 'Pone en 0 solvencyCertificate, boletin_active y pdf a los alumnos sin grado ni sección';

    /** Banderas de documentos que se apagan. */
    private const FLAGS = ['solvencyCertificate', 'boletin_active', 'pdf'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        // Criterio: sin grado NI sección. Se exigen ambos para no tocar a un alumno
        // al que solo le falte uno de los dos por un error de captura.
        $query = Student::query()
            ->whereNull('grade_id')
            ->whereNull('section_id');

        if ($company = $this->option('company')) {
            $query->where('company_id', $company);
        }

        if ($type = $this->option('type')) {
            $query->where('type_education_id', $type);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No hay alumnos sin grado ni sección con esos filtros. Nada que hacer.');

            return self::SUCCESS;
        }

        // Solo interesan los que tienen al menos una bandera encendida.
        $pending = (clone $query)->where(function ($q) {
            foreach (self::FLAGS as $flag) {
                $q->orWhere($flag, 1);
            }
        });

        $affected = (clone $pending)->count();

        $this->newLine();
        $this->line($apply ? '<fg=yellow>MODO APLICAR</> — los cambios se van a guardar.' : '<fg=cyan>MODO SIMULACIÓN</> — no se guarda nada. Usá --apply para ejecutar.');
        $this->newLine();

        $this->info("Alumnos sin grado ni sección: {$total}");
        $this->info("De esos, con banderas encendidas: {$affected}");
        $this->newLine();

        $this->table(
            ['Tipo educación', 'Alumnos', 'Solvencia', 'Boletín', 'Notas (pdf)'],
            $this->breakdown(clone $query)
        );

        if ($affected === 0) {
            $this->info('Todas las banderas ya están en 0. Nada que cambiar.');

            return self::SUCCESS;
        }

        if (! $apply) {
            $this->comment("Simulación: se apagarían las banderas de {$affected} alumnos.");
            $this->comment('Volvé a correrlo con --apply para aplicarlo.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("¿Apagar solvencia, boletín y notas de {$affected} alumnos?", false)) {
            $this->warn('Cancelado. No se modificó nada.');

            return self::SUCCESS;
        }

        $updated = 0;

        DB::transaction(function () use ($pending, &$updated) {
            // Por lotes para no cargar todo en memoria si el volumen crece.
            $pending->select('id')->chunkById(500, function ($students) use (&$updated) {
                $updated += Student::whereIn('id', $students->pluck('id'))->update([
                    'solvencyCertificate' => 0,
                    'boletin_active' => 0,
                    'pdf' => 0,
                ]);
            });
        });

        $this->newLine();
        $this->info("Listo. Se actualizaron {$updated} alumnos.");

        // Verificación: no debe quedar ninguno con banderas encendidas.
        $remaining = (clone $query)->where(function ($q) {
            foreach (self::FLAGS as $flag) {
                $q->orWhere($flag, 1);
            }
        })->count();

        if ($remaining > 0) {
            $this->error("Quedaron {$remaining} alumnos con banderas encendidas. Revisar.");

            return self::FAILURE;
        }

        $this->info('Verificado: ningún alumno sin grado ni sección conserva banderas encendidas.');

        return self::SUCCESS;
    }

    /**
     * Resumen por tipo de educación de las banderas que están encendidas.
     */
    private function breakdown($query): array
    {
        return $query->selectRaw('type_education_id')
            ->selectRaw('COUNT(*) as alumnos')
            ->selectRaw('SUM(solvencyCertificate = 1) as solvencia')
            ->selectRaw('SUM(boletin_active = 1) as boletin')
            ->selectRaw('SUM(pdf = 1) as notas')
            ->with('type_education:id,name')
            ->groupBy('type_education_id')
            ->get()
            ->map(fn ($row) => [
                $row->type_education?->name ?? '(sin tipo)',
                $row->alumnos,
                $row->solvencia,
                $row->boletin,
                $row->notas,
            ])
            ->all();
    }
}
