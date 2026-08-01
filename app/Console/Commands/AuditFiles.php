<?php

namespace App\Console\Commands;

use App\Services\Maintenance\FileAuditService;
use Illuminate\Console\Command;

/**
 * Audita el disco contra las rutas registradas en la base. Solo reporta, no borra nada.
 */
class AuditFiles extends Command
{
    protected $signature = 'maintenance:audit-files
                            {--limit=20 : Cuántos huérfanos listar en el detalle}';

    protected $description = 'Reporta archivos huérfanos en el disco y referencias rotas en la base';

    public function handle(FileAuditService $service): int
    {
        $this->info('Recorriendo el disco y cruzando con la base...');

        $bar = null;

        $result = $service->run(function ($processed, $total) use (&$bar) {
            if (! $bar) {
                $bar = $this->output->createProgressBar($total);
            }
            $bar->setProgress($processed);
        });

        if ($bar) {
            $bar->finish();
        }

        $summary = $result['summary'];

        $this->newLine(2);
        $this->table(['Concepto', 'Cantidad'], [
            ['Archivos en el disco', $summary['disk_files']],
            ['Referenciados en la base', $summary['referenced']],
            ['En carpetas ignoradas (temporales)', $summary['ignored']],
            ['<fg=yellow>Huérfanos (en disco, sin referencia)</>', $summary['orphans']],
            ['<fg=yellow>Espacio de los huérfanos</>', $this->humanBytes($summary['orphan_bytes'])],
            ['<fg=red>Referencias rotas (en base, sin archivo)</>', $summary['missing']],
        ]);

        // Aviso crítico: si falta una columna del registro, sus archivos van a figurar
        // como huérfanos aunque estén perfectamente en uso.
        if (! empty($result['skippedSources'])) {
            $this->newLine();
            $this->error('  ATENCIÓN: estas columnas del registro no existen en esta base:  ');
            foreach ($result['skippedSources'] as $source) {
                $this->line('   - ' . $source);
            }
            $this->warn('  Los archivos de esas columnas aparecen abajo como huérfanos SIN SERLO.');
            $this->warn('  No borres nada hasta resolverlo.');
        }

        if (! empty($result['byFolder'])) {
            $this->newLine();
            $this->line('<options=bold>Huérfanos por carpeta</> — si una carpeta entera aparece acá,');
            $this->line('lo más probable es que falte registrar su columna en FilePathRegistry.');
            $this->table(
                ['Carpeta', 'Archivos', 'Espacio'],
                array_map(fn ($f) => [$f['folder'], $f['count'], $this->humanBytes($f['bytes'])], $result['byFolder'])
            );
        }

        $limit = (int) $this->option('limit');

        if (! empty($result['orphans']) && $limit > 0) {
            $this->newLine();
            $this->line("<options=bold>Primeros {$limit} huérfanos</>");
            $this->table(
                ['Ruta', 'Tamaño', 'Modificado'],
                array_map(
                    fn ($o) => [$o['path'], $this->humanBytes($o['size']), $o['modified_at'] ?? '-'],
                    array_slice($result['orphans'], 0, $limit)
                )
            );
        }

        if (! empty($result['missing']) && $limit > 0) {
            $this->newLine();
            $this->line("<options=bold>Primeras {$limit} referencias rotas</>");
            $this->table(
                ['Ruta', 'Usada en'],
                array_map(
                    fn ($m) => [$m['path'], $m['source']],
                    array_slice($result['missing'], 0, $limit)
                )
            );
        }

        $this->newLine();
        $this->comment('Auditoría de solo lectura: no se modificó ni borró ningún archivo.');

        return self::SUCCESS;
    }

    protected function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
