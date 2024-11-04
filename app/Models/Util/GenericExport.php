<?php
namespace App\Models\Util;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class GenericExport implements FromCollection, WithHeadings, WithColumnWidths
{
    protected $data;
    protected $headings;

    public function __construct($data, $headings = [])
    {
        $this->data = $data;
        $this->headings = $headings;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return $this->headings;
    }

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
