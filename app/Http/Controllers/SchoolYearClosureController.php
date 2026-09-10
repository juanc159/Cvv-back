<?php

namespace App\Http\Controllers;

use App\Models\TypeEducation;
use App\Services\SchoolYearClosureService;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

/**
 * Cierre del año escolar.
 *
 * Muestra a los alumnos que quedaron sin grado ni sección y permite decir qué son:
 * pendientes de inscribirse, retirados o egresados. Sin esta pantalla esos alumnos
 * desaparecen de todos los listados sin que nadie haya decidido nada sobre ellos.
 */
class SchoolYearClosureController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected SchoolYearClosureService $closureService,
    ) {}

    public function index(Request $request)
    {
        return $this->execute(function () use ($request) {
            $companyId = $request->input('company_id');

            $students = $this->closureService->unassigned([
                'company_id' => $companyId,
                'status' => $request->input('status'),
                'type_education_id' => $request->input('type_education_id'),
                'search' => $request->input('search'),
            ]);

            return [
                'code' => 200,
                'students' => $students,
                'summary' => $this->closureService->summary($companyId),
                'typeEducations' => TypeEducation::where('is_active', true)->get(['id', 'name']),
            ];
        });
    }

    /**
     * Aplica la decisión a los alumnos seleccionados.
     */
    public function classify(Request $request)
    {
        return $this->execute(function () use ($request) {
            $validated = $request->validate([
                'student_ids' => ['required', 'array', 'min:1'],
                'student_ids.*' => ['required', 'exists:students,id'],
                'action' => ['required', 'in:withdrawal,graduation,clear'],
                'date' => ['required_unless:action,clear', 'nullable', 'date'],
                'reason' => ['required_unless:action,clear', 'nullable', 'string', 'max:255'],
                'deactivate' => ['nullable', 'boolean'],
            ], [
                'student_ids.required' => 'Debe seleccionar al menos un alumno.',
                'action.in' => 'La acción indicada no es válida.',
                'date.required_unless' => 'Debe indicar la fecha.',
                'reason.required_unless' => 'Debe indicar el motivo.',
            ]);

            $affected = $this->closureService->classify(
                $validated['student_ids'],
                $validated['action'],
                [
                    'date' => $validated['date'] ?? now()->format('Y-m-d'),
                    'reason' => $validated['reason'] ?? '',
                    'deactivate' => $request->boolean('deactivate'),
                ]
            );

            $messages = [
                'withdrawal' => 'alumno(s) registrados como retirados.',
                'graduation' => 'alumno(s) registrados como egresados.',
                'clear' => 'alumno(s) devueltos a "sin decidir".',
            ];

            return [
                'code' => 200,
                'message' => $affected . ' ' . $messages[$validated['action']],
            ];
        });
    }
}
