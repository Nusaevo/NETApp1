<?php

namespace App\Models\Util;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class GenericExport implements FromCollection, WithHeadings, WithEvents, WithTitle
{
    protected $data;
    protected $headings;
    protected $sheetName;

    /**
     * Constructor to initialize data, headings, and sheet name.
     *
     * @param array $data
     * @param array $headings
     * @param string $sheetName
     */
    public function __construct($data, $headings = [], $sheetName = 'Sheet1')
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->sheetName = $sheetName;
    }

    /**
     * Collection method to return the data collection.
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * Headings for the sheet.
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * Define the title of the sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return $this->sheetName;
    }

    /**
     * Register events to handle column auto-sizing and header styling.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Ensure there are valid headings
                if (empty($this->headings)) {
                    throw new \Exception("Headings array is empty. Cannot determine column widths.");
                }

                // Dynamically adjust each column width based on headers
                foreach ($this->headings as $index => $header) {
                    $column = $this->columnLetter($index + 1); // Convert index to column letter
                    $event->sheet->getDelegate()->getColumnDimension($column)->setAutoSize(true);

                    // Check if the header contains an asterisk (*)
                    if (strpos($header, '*') !== false) {
                        // Apply red color to the header text if it contains an asterisk
                        $cell = "{$column}1"; // First row, current column
                        $event->sheet->getDelegate()->getStyle($cell)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FF0000'], // Red color
                            ],
                            'alignment' => ['horizontal' => 'center'],
                        ]);
                    }
                }

                // Optional: Apply general styling to all headers
                $lastColumn = $this->columnLetter(count($this->headings));
                $event->sheet->getDelegate()->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                ]);
            },
        ];
    }

    /**
     * Convert a column index to a letter (1 => A, 2 => B, etc.).
     *
     * @param int $index
     * @return string
     */
    protected function columnLetter($index)
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr($index % 26 + 65) . $letter;
            $index = (int) ($index / 26);
        }
        return $letter;
    }
}
