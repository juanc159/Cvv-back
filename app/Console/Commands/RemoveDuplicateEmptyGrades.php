<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Elimina grados duplicados que no tienen ningún uso.
 *
 * Un grado se considera duplicado cuando existe otro con el mismo nombre, colegio y tipo
 * de educación. Solo se borra el que NO tiene absolutamente nada colgando: ni alumnos, ni
 * docentes, ni planificaciones, ni actividades, ni preinscripciones.
 *
 * Importa porque desde la interfaz los duplicados se ven idénticos en los desplegables:
 * si alguien matricula un alumno en el equivocado, queda en un grado sin docentes y sin
 * reportes, y no hay forma de darse cuenta.
 */
class RemoveDuplicateEmptyGrades extends Command
{
    protected $signature = 'grades:remove-duplicates
                            {--company= : Limitar a un colegio (company_id)}
                            {--apply : Aplica los cambios. Sin esta bandera solo simula}
                            {--force : Omite la confirmación interactiva}';

    protected $description = 'Elimina grados duplicados que no tienen alumnos ni ningún otro uso';

    /**
     * Tablas que apuntan a un grado. Un grado con filas en cualquiera de estas NO se borra.
     *
     * grade_subjects queda fuera a propósito: es el pénsum que el sistema carga solo al
     * crear el grado, así que tenerlo no significa que el grado esté en uso. Se borra junto
     * con el grado.
     */
    private const REFERENCING_TABLES = [
        'students',
        'teacher_complementaries',
        'teacher_plannings',
        'activities',
        'pending_registrations',
    ];

    /** Se borra junto al grado por ser dato derivado, no uso real. */
    private const CASCADE_TABLES = ['grade_subjects'];

    public function handle(): int
    {
        if (! $this->guardKnownReferences()) {
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $company = $this->option('company');

        $candidates = $this->findCandidates($company);

        $this->newLine();
        $this->line($apply
            ? '<fg=yellow>MODO APLICAR</> — los cambios se van a guardar.'
            : '<fg=cyan>MODO SIMULACIÓN</> — no se borra nada. Usá --apply para ejecutar.');
        $this->newLine();

        if (empty($candidates)) {
            $this->info('No hay grados duplicados sin uso. Nada que hacer.');

            return self::SUCCESS;
        }

        $this->info('Grados duplicados sin ningún uso:');
        $this->table(
            ['Colegio', 'Grado', 'ID a borrar', 'Creado', 'Se conserva'],
            array_map(fn ($c) => [
                $c['company_id'],
                $c['name'],
                $c['id'],
                $c['created_at'],
                $c['keeps'],
            ], $candidates)
        );

        $this->comment('Ninguno tiene alumnos, docentes, planificaciones, actividades ni preinscripciones.');

        if (! $apply) {
            $this->newLine();
            $this->comment('Simulación: se borrarían ' . count($candidates) . ' grado(s).');
            $this->comment('Volvé a correrlo con --apply para aplicarlo.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm('¿Borrar ' . count($candidates) . ' grado(s) duplicado(s)?', false)) {
            $this->warn('Cancelado. No se modificó nada.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $cascade = 0;

        DB::transaction(function () use ($candidates, &$deleted, &$cascade) {
            foreach ($candidates as $candidate) {
                // Se vuelve a verificar dentro de la transacción: entre el listado y el
                // borrado alguien pudo haber matriculado un alumno en ese grado.
                if ($this->usageCount($candidate['id']) > 0) {
                    $this->warn("El grado {$candidate['id']} pasó a tener uso. Se omite.");
                    continue;
                }

                foreach (self::CASCADE_TABLES as $table) {
                    $cascade += DB::table($table)->where('grade_id', $candidate['id'])->delete();
                }

                $deleted += DB::table('grades')->where('id', $candidate['id'])->delete();
            }
        });

        $this->newLine();
        $this->info("Listo. Se borraron {$deleted} grado(s) y {$cascade} fila(s) de pénsum.");

        $remaining = count($this->findCandidates($company));

        if ($remaining > 0) {
            $this->error("Quedaron {$remaining} duplicados sin borrar. Revisar.");

            return self::FAILURE;
        }

        $this->info('Verificado: no quedan grados duplicados sin uso.');

        return self::SUCCESS;
    }

    /**
     * Busca grados duplicados cuyo uso sea cero.
     *
     * @return array<int, array{id: string, name: string, company_id: string, created_at: string, keeps: string}>
     */
    private function findCandidates(?string $company): array
    {
        $grades = DB::table('grades')
            ->select('id', 'name', 'company_id', 'type_education_id', 'created_at')
            ->when($company, fn ($q) => $q->where('company_id', $company))
            ->orderBy('created_at')
            ->get();

        // Se agrupa por colegio + tipo + nombre normalizado: así se detectan los que la
        // interfaz muestra idénticos, aunque difieran en mayúsculas o espacios.
        $groups = [];

        foreach ($grades as $grade) {
            $key = $grade->company_id . '|' . $grade->type_education_id . '|'
                . mb_strtolower(trim(preg_replace('/\s+/', ' ', $grade->name)));

            $groups[$key][] = $grade;
        }

        $candidates = [];

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $unused = [];
            $used = [];

            foreach ($group as $grade) {
                if ($this->usageCount($grade->id) > 0) {
                    $used[] = $grade;
                } else {
                    $unused[] = $grade;
                }
            }

            // Nunca se borra el grupo entero: si ninguno tiene uso, se conserva el más
            // antiguo para no dejar al colegio sin ese grado.
            if (empty($used)) {
                array_shift($unused);
                $keeps = 'el más antiguo del grupo';
            } else {
                $keeps = $used[0]->id . ' (en uso)';
            }

            foreach ($unused as $grade) {
                $candidates[] = [
                    'id' => $grade->id,
                    'name' => $grade->name,
                    'company_id' => $grade->company_id,
                    'created_at' => (string) $grade->created_at,
                    'keeps' => $keeps,
                ];
            }
        }

        return $candidates;
    }

    /** Cuántas filas de todo el sistema apuntan a este grado. */
    private function usageCount(string $gradeId): int
    {
        $total = 0;

        foreach (self::REFERENCING_TABLES as $table) {
            $total += DB::table($table)->where('grade_id', $gradeId)->count();
        }

        return $total;
    }

    /**
     * Verifica que no exista ninguna tabla con grade_id que el comando no conozca.
     *
     * Sin esto, si mañana alguien agrega una tabla nueva que referencia grados, el comando
     * borraría grados que sí están en uso sin enterarse.
     */
    private function guardKnownReferences(): bool
    {
        $known = array_merge(self::REFERENCING_TABLES, self::CASCADE_TABLES);

        $found = DB::table('information_schema.COLUMNS')
            ->select('TABLE_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('COLUMN_NAME', 'grade_id')
            ->pluck('TABLE_NAME')
            ->all();

        $unknown = array_diff($found, $known);

        if (! empty($unknown)) {
            $this->error('Hay tablas con grade_id que este comando no conoce:');
            foreach ($unknown as $table) {
                $this->line('   - ' . $table);
            }
            $this->warn('Agregalas a REFERENCING_TABLES antes de continuar, o se borrarían');
            $this->warn('grados que en realidad están en uso.');

            return false;
        }

        return true;
    }
}
