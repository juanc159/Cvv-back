<?php

namespace App\Http\Controllers;

use App\Exports\PromotionBatchExport;
use App\Http\Requests\StudentPromotion\PromotionPreviewRequest;
use App\Models\Grade;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchRow;
use App\Models\Section;
use App\Models\Term;
use App\Models\TypeEducation;
use App\Services\StudentPromotionService;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Promoción de alumnos entre años escolares.
 *
 * Sustituye a la ruta /actualizar de web.php, donde el listado de cédulas se pegaba a
 * mano dentro del código y se aplicaba de una sola vez, sin previsualización ni registro.
 *
 * El flujo es: preview (analiza y no escribe nada) -> apply (escribe lo aprobado).
 */
class StudentPromotionController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected StudentPromotionService $promotionService,
    ) {}

    /**
     * Datos para armar la pantalla: períodos, tipos de educación, grados y secciones.
     *
     * Los grados se devuelven agrupados por tipo de educación para que al elegir
     * "Educación Media General" el selector de grado muestre solo los suyos.
     */
    public function dataForm(Request $request)
    {
        return $this->execute(function () use ($request) {
            $companyId = $request->input('company_id');

            $terms = Term::where('company_id', $companyId)
                ->orderByDesc('start_date')
                ->get(['id', 'name', 'start_date', 'end_date', 'is_active']);

            $typeEducations = TypeEducation::where('is_active', true)->get(['id', 'name']);

            $grades = Grade::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('name')
                ->get(['id', 'name', 'type_education_id']);

            $sections = Section::where('is_active', true)->orderBy('name')->get(['id', 'name']);

            return [
                'code' => 200,
                'terms' => $terms,
                'typeEducations' => $typeEducations,
                'grades' => $grades,
                'sections' => $sections,
                // El período activo se propone por defecto y su fecha de inicio sirve
                // como fecha de ingreso sugerida para los alumnos nuevos.
                'activeTerm' => $terms->firstWhere('is_active', true),
            ];
        });
    }

    /**
     * Historial de cargas del colegio.
     *
     * Sirve para dos cosas: saber qué se promovió y cuándo sin tener que abrir la base, y
     * poder deshacer una carga aplicada días después, cuando ya se cerró la pantalla en la
     * que se hizo.
     */
    public function history(Request $request)
    {
        return $this->execute(function () use ($request) {
            $batches = PromotionBatch::with(['grade', 'section', 'term', 'typeEducation', 'user'])
                ->where('company_id', $request->input('company_id'))
                ->when($request->input('term_id'), fn ($query, $termId) => $query->where('term_id', $termId))
                ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            return [
                'code' => 200,
                'batches' => $batches->map(fn ($batch) => [
                    'id' => $batch->id,
                    'status' => $batch->status,
                    'status_label' => $this->statusLabel($batch),
                    'file_name' => $batch->file_name,
                    'term' => $batch->term?->name,
                    'type_education' => $batch->typeEducation?->name,
                    'grade' => $batch->grade?->name,
                    'section' => $batch->section?->name,
                    'total_rows' => $batch->total_rows,
                    'applied_rows' => $batch->applied_rows,
                    'user' => $batch->user ? trim($batch->user->name . ' ' . $batch->user->surname) : null,
                    'created_at' => $batch->created_at?->format('d/m/Y H:i'),
                    'applied_at' => $batch->applied_at?->format('d/m/Y H:i'),
                ]),
            ];
        });
    }

    /**
     * Estado del lote en palabras, igual en la pantalla y en el Excel.
     */
    private function statusLabel(PromotionBatch $batch): string
    {
        return match ($batch->status) {
            'preview' => 'Sin aplicar',
            'applied' => 'Aplicada',
            'discarded' => 'Descartada',
            'reverted' => 'Deshecha',
            default => $batch->status,
        };
    }

    /**
     * Analiza el archivo y devuelve qué pasaría con cada alumno. No modifica nada.
     */
    public function preview(PromotionPreviewRequest $request)
    {
        return $this->execute(function () use ($request) {
            $batch = $this->promotionService->preview(
                array_merge($request->validated(), ['user_id' => auth()->id()]),
                $request->file('file')
            );

            return [
                'code' => 200,
                'batch' => $this->presentBatch($batch),
                'message' => 'Previsualización generada. Revise los casos marcados antes de confirmar.',
            ];
        });
    }

    /**
     * Devuelve un lote ya analizado, para volver a abrirlo sin subir el archivo de nuevo.
     */
    public function show($id)
    {
        return $this->execute(function () use ($id) {
            $batch = PromotionBatch::with('rows')->findOrFail($id);

            return ['code' => 200, 'batch' => $this->presentBatch($batch)];
        });
    }

    /**
     * Aplica el lote con las decisiones que tomó el usuario en pantalla.
     *
     * decisions llega como { id_de_fila: true|false }.
     */
    public function apply(Request $request, $id)
    {
        return $this->execute(function () use ($request, $id) {
            $batch = PromotionBatch::with('rows')->findOrFail($id);

            $batch = $this->promotionService->apply($batch, $request->input('decisions'));

            return [
                'code' => 200,
                'batch' => $this->presentBatch($batch),
                'message' => 'Promoción aplicada: ' . $batch->applied_rows . ' alumnos actualizados.',
            ];
        });
    }

    /**
     * Descarta una previsualización que no se va a aplicar.
     */
    public function discard($id)
    {
        return $this->execute(function () use ($id) {
            $batch = PromotionBatch::findOrFail($id);

            if ($batch->status === 'applied') {
                return ['code' => 500, 'message' => 'El lote ya fue aplicado y no se puede descartar.'];
            }

            $batch->update(['status' => 'discarded']);

            return ['code' => 200, 'message' => 'Previsualización descartada.'];
        });
    }

    /**
     * Deshace un lote ya aplicado y devuelve a cada alumno a donde estaba.
     *
     * Es la red de seguridad del módulo: si se subió el archivo equivocado o se eligió mal
     * el grado destino, se corrige con un clic en vez de rehacer las inscripciones a mano.
     */
    public function revert($id)
    {
        return $this->execute(function () use ($id) {
            $batch = PromotionBatch::with('rows')->findOrFail($id);

            $batch = $this->promotionService->revert($batch);

            return [
                'code' => 200,
                'batch' => $this->presentBatch($batch),
                'message' => 'La promoción fue deshecha: los alumnos volvieron a su grado y sección anteriores.',
            ];
        });
    }

    /**
     * Descarga el análisis en Excel.
     *
     * Con el parámetro action devuelve solo ese listado (el botón que hay en cada grupo);
     * sin él, un archivo con una hoja por grupo. Cada hoja lleva su título y el destino
     * completo, porque estos listados se imprimen y circulan fuera del sistema.
     */
    public function export(Request $request, $id)
    {
        return $this->execute(function () use ($request, $id) {
            $batch = PromotionBatch::with('rows')->findOrFail($id);
            $action = $request->input('action');

            if ($action && ! array_key_exists($action, PromotionBatchRow::actionLabels())) {
                return ['code' => 500, 'message' => 'El listado solicitado no existe.'];
            }

            $excel = Excel::raw(new PromotionBatchExport($batch, $action), \Maatwebsite\Excel\Excel::XLSX);

            $label = $action ? PromotionBatchRow::actionLabels()[$action] : 'Analisis completo';

            return [
                'code' => 200,
                'excel' => base64_encode($excel),
                'fileName' => $this->exportFileName($batch, $label),
            ];
        });
    }

    /**
     * Nombre del archivo: listado, grado y sección, para que no se confundan entre sí
     * cuando se descarguen varios.
     */
    private function exportFileName(PromotionBatch $batch, string $label): string
    {
        $parts = array_filter([
            $label,
            $batch->grade?->name,
            $batch->section?->name ? 'Seccion ' . $batch->section->name : null,
        ]);

        $name = implode(' - ', $parts);

        // Las tildes y la eñe se transliteran en lugar de borrarse: si no, "Primer Año"
        // se descarga como "Primer Ao" y el archivo deja de ser reconocible.
        $name = strtr($name, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 \-_]/', '', $name)));
    }

    /**
     * Da formato al lote para la pantalla, agrupando las filas por acción y traduciendo
     * los códigos internos a algo legible.
     */
    private function presentBatch(PromotionBatch $batch): array
    {
        $labels = PromotionBatchRow::actionLabels();

        $rows = $batch->rows->map(function ($row) use ($labels) {
            return [
                'id' => $row->id,
                'row_number' => $row->row_number,
                'action' => $row->action,
                'action_label' => $labels[$row->action] ?? $row->action,
                'match_type' => $row->match_type,
                'student_id' => $row->student_id,
                'identity_document' => $row->identity_document_normalized,
                'identity_document_raw' => $row->identity_document_raw,
                'previous_identity_document' => $row->previous_identity_document,
                'full_name' => $row->full_name_raw,
                'origin' => $row->previous_grade_name
                    ? $row->previous_grade_name . ' ' . ($row->previous_section_name ?? '')
                    : null,
                'messages' => $row->messages ?? [],
                'needs_review' => $row->needs_review,
                'is_approved' => $row->is_approved,
                'is_applied' => $row->is_applied,
            ];
        })->values();

        return [
            'id' => $batch->id,
            'status' => $batch->status,
            'status_label' => $this->statusLabel($batch),
            'file_name' => $batch->file_name,
            'total_rows' => $batch->total_rows,
            'applied_rows' => $batch->applied_rows,
            'applied_at' => $batch->applied_at,
            'entry_date' => $batch->entry_date?->format('Y-m-d'),
            'destination' => [
                'type_education' => $batch->typeEducation?->name,
                'grade' => $batch->grade?->name,
                'section' => $batch->section?->name,
                'term' => $batch->term?->name,
            ],
            'summary' => $batch->summary ?? [],
            'rows' => $rows,
        ];
    }
}
