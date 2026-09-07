<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BulkTemplateExport implements WithMultipleSheets
{
    protected array $columns;
    protected array $sample;

    public function __construct(?array $columns = null, ?array $sample = null)
    {
        $this->columns = $columns ?? [
            'First Name',
            'Middle Name',
            'Last Name',
            'Suffix',
            'Birthdate (YYYY-MM-DD)',
            'Sex',
            'Phone Number (09XXXXXXXXX)',
            'Email Address',
            'Street / House No.',
            'Barangay',
            'City / Municipality',
            'Province'
        ];

        $this->sample = $sample ?? [
            'JUAN',
            'DELA',
            'CRUZ',
            'JR',
            '1995-10-24',
            'Male',
            '09123456789',
            'juan.delacruz@example.com',
            'Block 5 Lot 12 Atis St.',
            'Dadiangas West',
            'City of General Santos',
            'South Cotabato'
        ];
    }

    /**
     * Build the multi-sheet workbook: Sheet 1 is the main template, Sheet 2 is the PSGC Reference.
     */
    public function sheets(): array
    {
        return [
            new BulkTemplateMainSheet($this->columns, $this->sample),
            new BulkTemplateReferenceSheet(),
        ];
    }
}

/**
 * Main Data Entry Worksheet
 */
class BulkTemplateMainSheet implements FromArray, WithColumnFormatting, ShouldAutoSize, WithStyles, WithEvents, WithTitle
{
    protected array $columns;
    protected array $sample;

    public function __construct(array $columns, array $sample)
    {
        $this->columns = $columns;
        $this->sample = $sample;
    }

    public function title(): string
    {
        return 'Patient Data Entry';
    }

    public function array(): array
    {
        return [
            $this->columns,
            $this->sample
        ];
    }

    /**
     * Define Text format for all columns across the entire sheet to preserve leading zeros
     * on mobile numbers and prevent Excel from auto-converting date strings to numeric serials.
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => '@',
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => '@',
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * Style the template worksheet with Medscreen clinical branding.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Header Row (Row 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '19D38C'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1C232D'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ],
            // Sample Guide Row (Row 2)
            2 => [
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '64748B'],
                    'size' => 10,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                ]
            ],
            // Data Input Rows (Rows 3 to 500)
            'A3:L500' => [
                'font' => [
                    'bold' => false,
                    'italic' => false,
                    'color' => ['rgb' => '1C232D'],
                    'size' => 10,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ]
        ];
    }

    /**
     * Register cell guidance prompts and dropdowns across all entry rows (2 to 500).
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $maxRows = 500;

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $provincesCount = count(BulkTemplateReferenceSheet::getProvinces());
                $citiesCount = count(BulkTemplateReferenceSheet::getCities());
                $barangaysCount = count(BulkTemplateReferenceSheet::getBarangays());

                $provEndRow = $provincesCount + 1;
                $cityEndRow = $citiesCount + 1;
                $brgyEndRow = $barangaysCount + 1;

                for ($row = 2; $row <= $maxRows; $row++) {
                    // A. First Name (Col A)
                    $vA = $sheet->getCell("A{$row}")->getDataValidation();
                    $vA->setType(DataValidation::TYPE_CUSTOM);
                    $vA->setShowInputMessage(true);
                    $vA->setPromptTitle('First Name');
                    $vA->setPrompt('Required. Letters, spaces, periods, hyphens, and apostrophes only (Max 60 chars).');

                    // B. Middle Name (Col B)
                    $vB = $sheet->getCell("B{$row}")->getDataValidation();
                    $vB->setType(DataValidation::TYPE_CUSTOM);
                    $vB->setShowInputMessage(true);
                    $vB->setPromptTitle('Middle Name');
                    $vB->setPrompt('Optional. Leave blank or enter N/A if patient has no middle name.');

                    // C. Last Name (Col C)
                    $vC = $sheet->getCell("C{$row}")->getDataValidation();
                    $vC->setType(DataValidation::TYPE_CUSTOM);
                    $vC->setShowInputMessage(true);
                    $vC->setPromptTitle('Last Name');
                    $vC->setPrompt('Required. Patient family surname (Max 60 chars).');

                    // D. Suffix Dropdown (Col D)
                    $vD = $sheet->getCell("D{$row}")->getDataValidation();
                    $vD->setType(DataValidation::TYPE_LIST);
                    $vD->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $vD->setAllowBlank(true);
                    $vD->setShowDropDown(true);
                    $vD->setShowInputMessage(true);
                    $vD->setPromptTitle('Suffix (Optional)');
                    $vD->setPrompt('Select from dropdown or leave blank (e.g. JR, SR, II, III, IV, V).');
                    $vD->setFormula1('"JR,SR,II,III,IV,V"');

                    // E. Birthdate (Col E) - Format Guidance Prompt with copy-row instruction
                    $vE = $sheet->getCell("E{$row}")->getDataValidation();
                    $vE->setType(DataValidation::TYPE_CUSTOM);
                    $vE->setShowInputMessage(true);
                    $vE->setPromptTitle('Birthdate (18+ Only)');
                    $vE->setPrompt("Required format: YYYY-MM-DD (e.g. 1995-10-24). Patients must be at least 18 years old.\n\nTip: You can copy the cell or row above to preserve format.");

                    // F. Sex Dropdown (Col F)
                    $vF = $sheet->getCell("F{$row}")->getDataValidation();
                    $vF->setType(DataValidation::TYPE_LIST);
                    $vF->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $vF->setAllowBlank(false);
                    $vF->setShowDropDown(true);
                    $vF->setShowInputMessage(true);
                    $vF->setPromptTitle('Sex');
                    $vF->setPrompt('Required. Select Male or Female from the dropdown list.');
                    $vF->setFormula1('"Male,Female"');

                    // G. Phone Number (Col G) - Guidance Prompt with copy-row instruction
                    $vG = $sheet->getCell("G{$row}")->getDataValidation();
                    $vG->setType(DataValidation::TYPE_CUSTOM);
                    $vG->setShowInputMessage(true);
                    $vG->setPromptTitle('Phone Number (09XXXXXXXXX)');
                    $vG->setPrompt("Required. 11 digits starting with 09 (e.g. 09123456789). Ensure leading zero is preserved.\n\nTip: You can copy the cell or row above to preserve format.");

                    // H. Email Address (Col H)
                    $vH = $sheet->getCell("H{$row}")->getDataValidation();
                    $vH->setType(DataValidation::TYPE_CUSTOM);
                    $vH->setShowInputMessage(true);
                    $vH->setPromptTitle('Email Address');
                    $vH->setPrompt('Required. Valid email address to receive digital results (e.g. name@domain.com).');

                    // I. Street / House No. (Col I)
                    $vI = $sheet->getCell("I{$row}")->getDataValidation();
                    $vI->setType(DataValidation::TYPE_CUSTOM);
                    $vI->setShowInputMessage(true);
                    $vI->setPromptTitle('Street Address');
                    $vI->setPrompt('Required. House/Lot/Block/Street Name.');

                    // J. Barangay Dropdown (Col J) - Linked to Reference Sheet Column C
                    $vJ = $sheet->getCell("J{$row}")->getDataValidation();
                    $vJ->setType(DataValidation::TYPE_LIST);
                    $vJ->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $vJ->setAllowBlank(false);
                    $vJ->setShowDropDown(true);
                    $vJ->setShowInputMessage(true);
                    $vJ->setPromptTitle('Barangay');
                    $vJ->setPrompt('Select from barangay dropdown list or type complete barangay name.');
                    $vJ->setFormula1("'PSGC_Reference'!\$C\$2:\$C\${$brgyEndRow}");

                    // K. City / Municipality Dropdown (Col K) - Linked to Reference Sheet Column B
                    $vK = $sheet->getCell("K{$row}")->getDataValidation();
                    $vK->setType(DataValidation::TYPE_LIST);
                    $vK->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $vK->setAllowBlank(false);
                    $vK->setShowDropDown(true);
                    $vK->setShowInputMessage(true);
                    $vK->setPromptTitle('City / Municipality');
                    $vK->setPrompt('Select from common cities dropdown or type complete city/municipality name.');
                    $vK->setFormula1("'PSGC_Reference'!\$B\$2:\$B\${$cityEndRow}");

                    // L. Province Dropdown (Col L) - Linked to Reference Sheet Column A (All 82 Provinces)
                    $vL = $sheet->getCell("L{$row}")->getDataValidation();
                    $vL->setType(DataValidation::TYPE_LIST);
                    $vL->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $vL->setAllowBlank(false);
                    $vL->setShowDropDown(true);
                    $vL->setShowInputMessage(true);
                    $vL->setPromptTitle('Province');
                    $vL->setPrompt('Select from dropdown list of all 82 Philippine provinces.');
                    $vL->setFormula1("'PSGC_Reference'!\$A\$2:\$A\${$provEndRow}");
                }
            }
        ];
    }
}

/**
 * PSGC Reference Sheet (Provinces, Cities/Municipalities, and Barangays)
 */
class BulkTemplateReferenceSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'PSGC_Reference';
    }

    /**
     * All 82 Philippine Provinces
     */
    public static function getProvinces(): array
    {
        return [
            'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao', 'Aurora',
            'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol', 'Bukidnon',
            'Bulacan', 'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin', 'Capiz', 'Catanduanes', 'Cavite',
            'Cebu', 'Cotabato', 'Davao de Oro', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental',
            'Dinagat Islands', 'Eastern Samar', 'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo',
            'Isabela', 'Kalinga', 'La Union', 'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'Leyte',
            'Maguindanao del Norte', 'Maguindanao del Sur', 'Marinduque', 'Masbate', 'Metro Manila',
            'Misamis Occidental', 'Misamis Oriental', 'Mountain Province', 'Negros Occidental', 'Negros Oriental',
            'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya', 'Occidental Mindoro', 'Oriental Mindoro',
            'Palawan', 'Pampanga', 'Pangasinan', 'Quezon', 'Quirino', 'Rizal', 'Romblon', 'Samar',
            'Sarangani', 'Siquijor', 'Sorsogon', 'South Cotabato', 'Southern Leyte', 'Sultan Kudarat', 'Sulu',
            'Surigao del Norte', 'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales', 'Zamboanga del Norte',
            'Zamboanga del Sur', 'Zamboanga Sibugay'
        ];
    }

    /**
     * Region XII, Mindanao, and Major National Cities & Municipalities
     */
    public static function getCities(): array
    {
        return [
            // Region XII (SOCCSKSARGEN) & Mindanao Core Focus
            'City of General Santos', 'City of Koronadal', 'Polomolok', 'Tupi', 'Surallah', 'Banga',
            'Norala', 'Tantangan', 'Santo Niño', 'Lake Sebu', 'Tboli',
            'Alabel', 'Malungon', 'Glan', 'Kiamba', 'Maasim', 'Maitum', 'Malapatan',
            'City of Tacurong', 'Isulan', 'Bagumbayan', 'President Quirino', 'Esperanza', 'Kalamansig', 'Lebak', 'Palimbang', 'Sen. Ninoy Aquino', 'Lambayong', 'Lutayan',
            'City of Kidapawan', 'Cotabato City', 'Midsayap', 'Pigcawayan', 'Pikit', 'Kabacan', 'Carmen', 'Makilala', 'Matalam', 'Tulunan', 'Antipas', 'Arakan', 'Banisilan', 'Magpet', 'President Roxas',
            'Davao City', 'Tagum City', 'Digos City', 'Panabo City', 'City of Mati', 'Island Garden City of Samal',
            'Zamboanga City', 'Cagayan de Oro City', 'Iligan City', 'Butuan City', 'Valencia City', 'Malaybalay City',
            // Major National Centers
            'City of Manila', 'Quezon City', 'Caloocan City', 'Makati City', 'Taguig City', 'Pasig City',
            'Parañaque City', 'Las Piñas City', 'Muntinlupa City', 'Mandaluyong City', 'Marikina City',
            'Pasay City', 'Valenzuela City', 'Navotas City', 'Malabon City', 'San Juan City',
            'Cebu City', 'Mandaue City', 'Lapu-Lapu City', 'Bacolod City', 'Iloilo City', 'Baguio City',
            'Angeles City', 'City of San Fernando', 'Batangas City', 'Lipa City', 'Antipolo City'
        ];
    }

    /**
     * Standard Barangays Focus: General Santos City (All 26), South Cotabato, Sarangani, & Nearby
     */
    public static function getBarangays(): array
    {
        $bar = [
            // 1. General Santos City (Complete 26 Barangays)
            'Baluan', 'Batomelong', 'Bawing', 'Bula', 'Buayan', 'Calumpang', 'City Heights', 'Conel',
            'Dadiangas East', 'Dadiangas North', 'Dadiangas South', 'Dadiangas West', 'Fatima', 'Katangawan',
            'Labangal', 'Lagao', 'Ligaya', 'Mabuhay', 'Olympog', 'San Isidro', 'San Jose', 'Siguel',
            'Sinawal', 'Tambler', 'Tinagacan', 'Upper Labay',

            // 2. City of Koronadal (South Cotabato Capital)
            'Assumption', 'Avanceña', 'Bagong Silang', 'Cacub', 'Caloocan', 'Carpenter Hill', 'Concepcion',
            'Esperanza', 'General Paulino Santos', 'Mabini', 'Magsaysay', 'Mambucaba', 'Morales', 'Namnama',
            'New Pangasinan', 'Paraiso', 'Poblacion', 'Rotonda', 'San Roque', 'Santa Cruz', 'Santo Niño',
            'Saravia', 'Topland', 'Zone I', 'Zone II', 'Zone III', 'Zone IV',

            // 3. Polomolok (South Cotabato)
            'Bentung', 'Cannery Site', 'Crossing Pangi', 'Glamang', 'Kinilis', 'Klinan 6', 'Koronadal Proper',
            'Lam-Caliaf', 'Lapu', 'Lumakil', 'Maligo', 'Palkan', 'Polo', 'Rubber', 'Silway 7', 'Silway 8',
            'Sulit', 'Sumbakil', 'Upper Klinan',

            // 4. Tupi (South Cotabato)
            'Acmonan', 'Bololmala', 'Bunao', 'Cabuling', 'Crossing Rubber', 'Elok', 'Linan', 'Lunen',
            'Palian', 'Polonuling', 'Simbo', 'Tubeng',

            // 5. Surallah (South Cotabato)
            'Buenavista', 'Centrala', 'Colonghulo', 'Dajay', 'Duengas', 'Canajay', 'Libertad', 'Little Baguio',
            'Moloy', 'Ndayon', 'Talahik', 'Tubiala', 'Upper Sepaka',

            // 6. Banga (South Cotabato)
            'Benitez', 'Cabudian', 'Cinco', 'Derilon', 'El Nonok', 'Improgo', 'Kusan', 'Lam-Apos', 'Lamba',
            'Lambingi', 'Liwanay', 'Malaya', 'Punong Grande', 'Rang-ay', 'Reyes', 'Rizal', 'Tanub', 'Yangco',

            // 7. Norala (South Cotabato)
            'Dumaguil', 'Fabrica', 'F. Faller', 'Kibudoc', 'Lapuz', 'Liberty', 'Lopez Jaena', 'Matapol',
            'Puti', 'Simsiman', 'Tinago',

            // 8. Santo Niño & Tantangan (South Cotabato)
            'Ambalgan', 'Guinsang-an', 'Katipunan', 'Manuel Roxas', 'Panay', 'Teresita',
            'Bukay Pait', 'Dumadalig', 'Libas', 'Magon', 'Maibo', 'Mangilala', 'New Lambunao', 'San Felipe', 'Tinongcop',

            // 9. Tboli & Lake Sebu (South Cotabato)
            'Kematu', 'Laconon', 'Lambangan', 'Edwards', 'Sinolon', 'Tudok',
            'Bacdulong', 'Halilan', 'Klubi', 'Lamlahak', 'Luhib', 'Ned', 'Seloton', 'Tasiman',

            // 10. Sarangani Province (Alabel, Malungon, Glan, Kiamba, Maasim, Maitum, Malapatan)
            'Alegria', 'Bagacay', 'Baluntay', 'Datal Anggas', 'Domolok', 'Kawas', 'Ladol', 'Maribulan', 'Spring',
            'Amparo', 'Banahaw', 'Alkikan', 'Datal Batak', 'J.P. Laurel', 'Malandag', 'Nagpan', 'Upper Biangan',
            'Baliton', 'Batulaki', 'Burias', 'Calpidong', 'Gumasa', 'Kaltuad', 'Lago', 'Pangyan', 'Taluya', 'Tango',
            'Badtasan', 'Datu Dani', 'Gasi', 'Kapate', 'Katubao', 'Kling', 'Lebe', 'Lomuyon', 'Salakit', 'Suli', 'Tambilil',
            'Amsipit', 'Bales', 'Colon', 'Daliao', 'Kanalo', 'Lumasal', 'Malbang', 'Nomoh', 'Pananag', 'Seven Hills', 'Tinoto',
            'Bati-an', 'Kalaneg', 'Kalaong', 'Kiambing', 'Maguling', 'Malalag', 'Mindupok', 'Pangi', 'Pinol', 'Sison', 'Ticulab', 'Upo', 'Wali',
            'Daan Suyan', 'Kihan', 'Kinam', 'Libi', 'Lun Masila', 'Lun Padidu', 'Patag', 'Sapu Masla', 'Sapu Padidu', 'Tuyan',

            // 11. Tacurong City & Sultan Kudarat
            'Baras', 'Buenaflor', 'Calean', 'Carmen', 'Griño', 'Kalandagan', 'Lancheta', 'Lower Katungal',
            'New Isabela', 'New Kalandagan', 'New Lagao', 'Rajah Muda', 'San Antonio', 'San Emmanuel', 'San Pablo', 'Upper Katungal',

            // 12. Standard Regional / National Common Reference Barangays
            'Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5', 'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10',
            'Buhangin', 'Matina Crossing', 'Talomo Proper', 'Agdao', 'Calinan Poblacion', 'Toril Poblacion'
        ];

        $unique = array_values(array_unique($bar));
        sort($unique);
        return $unique;
    }

    public function array(): array
    {
        $provinces = self::getProvinces();
        $cities = self::getCities();
        $barangays = self::getBarangays();

        $rows = [
            ['Standard Provinces (All 82)', 'Standard Cities & Municipalities', 'Standard Barangays (General Santos & Region XII)']
        ];

        $maxCount = max(count($provinces), count($cities), count($barangays));
        for ($i = 0; $i < $maxCount; $i++) {
            $rows[] = [
                $provinces[$i] ?? '',
                $cities[$i] ?? '',
                $barangays[$i] ?? ''
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1C232D']
                ]
            ]
        ];
    }
}