<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class PendingRegistrationExport implements FromView, ShouldAutoSize, WithEvents
{
    use Exportable;

    public $data;
    public $attempts;

    public function __construct($data,$attempts)
    {
        $this->data = $data;
        $this->attempts = $attempts;
    }

    public function view(): View
    {
        return view('Exports.PendingRegistration.PendingRegistration', [
            'data' => $this->data,
            'attempts' => $this->attempts
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],

                ]);
            },
        ];
    }
}
