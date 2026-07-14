<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BulkTemplateExport implements FromArray, WithColumnFormatting, ShouldAutoSize, WithStyles
{
    protected $columns;
    protected $sample;

    public function __construct(array $columns, array $sample)
    {
        $this->columns = $columns;
        $this->sample = $sample;
    }

    public function array(): array
    {
        return [
            $this->columns,
            $this->sample
        ];
    }

    /**
     * Forcibly define Text format for critical columns (Phone and Birthdate) 
     * to prevent Excel from auto-converting dates or stripping leading zeros.
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // Name
            'B' => '@',                       // Birthdate (Forces Text format so Excel leaves YYYY-MM-DD intact)
            'C' => NumberFormat::FORMAT_TEXT, // Sex
            'D' => '@',                       // Phone (Forces Text format to prevent stripping leading 0)
            'E' => NumberFormat::FORMAT_TEXT, // Email
            'F' => NumberFormat::FORMAT_TEXT, // Address
        ];
    }

    /**
     * Style the template worksheet with Medscreen clinical branding (Oxford Blue header with Shamrock Green text)
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row (Row 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '19D38C'], // Shamrock Green (#19d38c)
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1C232D'], // Oxford Blue (#1c232d)
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ],
            // Style the sample row (Row 2) to clearly guide the user
            2 => [
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '64748B'], // Slate grey for sample text
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                ]
            ]
        ];
    }
}