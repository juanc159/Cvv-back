<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class StudentListExport implements FromView, ShouldAutoSize, WithEvents
{
    use Exportable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $data = collect($this->data)->map(function ($value) {
            return [
                'id' => $value->id,

                "type_education_name" => $value->type_education?->name,
                "grade_name" => $value->grade?->name,
                "section_name" => $value->section?->name,
                "country_name" => $value->country?->name,
                "state_name" => $value->state?->name,
                "city_name" => $value->city?->name,
                "nationalized" => $value->nationalized ? "Si" : "No",
                "type_document_name" => $value->type_document?->name,
                "identity_document" => $value->identity_document,
                "full_name" => $value->full_name,
                "gender" => $value->gender,
                "birthday" => Carbon::parse($value->birthday)->format('d/m/Y'),
                "photo" => !empty($value->photo) ? "Si" : "No",
            ];
        });

        return view('Exports.Student.StudentListExportExcel', ['data' => $data]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtener el objeto hoja de cálculo
                $sheet = $event->sheet;

                // Obtener el rango de celdas con datos
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
                $range = 'A1:' . $highestColumn . $highestRow;

                // Establecer el filtro automático en el rango de celdas
                $sheet->setAutoFilter($range);
            },
        ];
    }
}
