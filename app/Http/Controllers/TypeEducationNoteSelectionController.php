<?php

namespace App\Http\Controllers;

use App\Models\TypeEducation;
use App\Models\TypeEducationNoteSelection;
use App\Repositories\TypeEducationRepository;
use Illuminate\Http\Request;


class TypeEducationNoteSelectionController extends Controller
{
    public function __construct(
        protected TypeEducationRepository $typeEducationRepository,
    ) {}

    public function show(Request $request)
    {
        // Obtener todos los tipos de educación
        $typeEducations = TypeEducation::get();
        $typeEducationNoteSelections = TypeEducationNoteSelection::get();

        // Crear un arreglo para almacenar los resultados
        $selectedNotes = [];

        foreach ($typeEducations as $typeEducation) {
            $selectedNotes[$typeEducation->id] = [];

            for ($i = 1; $i <= $typeEducation->cantNotes; $i++) {
                
                $selection = TypeEducationNoteSelection::where('type_education_id', $typeEducation->id)
                    ->where('note_number', $i)
                    ->first();
                    
                if ($selection) {
                    $selectedNotes[$typeEducation->id]['note_' . $i] = $selection->is_selected;
                } else {
                    $selectedNotes[$typeEducation->id]['note_' . $i] = false;
                    TypeEducationNoteSelection::create([
                        'type_education_id' => $typeEducation->id,
                        'note_number' => $i,
                        'is_selected' => false,
                    ]);
                }
            }
        }

        return response()->json(["code" => 200, "selectedNotes" => $selectedNotes]);
    }

    public function store(Request $request)
    {
        $selectedNotes = $request->input('selectedNotes');

        foreach ($selectedNotes as $typeEducationId => $notes) {
            foreach ($notes as $key => $value) {

                $newKey = (int)str_replace('note_', '', $key);

                $element = TypeEducationNoteSelection::where('type_education_id', $typeEducationId)
                    ->where('note_number', $newKey)
                    ->first();


                if ($element) {
                    $element->update(['is_selected' => $value]);
                } else {
                    TypeEducationNoteSelection::create([
                        'type_education_id' => $typeEducationId,
                        'note_number' => $newKey,
                        'is_selected' => $value,
                    ]);
                }
            }
        }

        return response()->json(["code" => 200, 'message' => 'Selección de notas actualizada correctamente']);
    }
}
