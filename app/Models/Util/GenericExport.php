<?php
namespace App\Models\Util;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;

class GenericExport implements FromCollection, WithHeadings, WithColumnWidths, WithTitle
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
     * Column widths based on header lengths.
     */
    public function columnWidths(): array
    {
        $widths = [];

        foreach ($this->headings as $index => $heading) {
            // Calculate the width based on the length of the header
            $widths[$this->columnLetter($index + 1)] = max(strlen($heading) + 2, 10);
        }

        return $widths;
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
     * Convert a column index to a letter (1 => A, 2 => B, etc.)
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
