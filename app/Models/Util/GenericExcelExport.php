<?php

namespace App\Models\Util;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Protection as StyleProtection;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class GenericExcelExport
{
    protected array $sheets; // Multi-sheet data
    protected string $filename;

    /**
     * Constructor for GenericExcelExport.
     *
     * @param array $sheets Array containing sheet configurations.
     *                      Example:
     *                      [
     *                          [
     *                              'name' => 'Sheet1',
     *                              'headers' => [...],
     *                              'data' => [...],
     *                              'protectedColumns' => ['A', 'B'],
     *                              'allowInsert' => true,
     *                          ],
     *                      ]
     * @param string $filename The name of the exported Excel file (default: 'export.xlsx').
     */
    public function __construct(array $sheets, string $filename = 'export.xlsx')
    {
        $this->sheets = $sheets;
        $this->filename = $filename;
    }

    /**
     * Download the generated Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        $spreadsheet = $this->generateSpreadsheet();

        // Save and prepare for download
        $temp_file = tempnam(sys_get_temp_dir(), $this->filename);
        (new Xlsx($spreadsheet))->save($temp_file);

        return Response::download($temp_file, $this->filename)->deleteFileAfterSend(true);
    }

    /**
     * Upload the generated Excel file to a specified path.
     *
     * @param string $fullPath The full path where the file will be saved.
     */
    public function upload(string $fullPath)
    {
        $spreadsheet = $this->generateSpreadsheet();

        // Ensure the directory exists
        $directory = dirname($fullPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        // Save the file
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);
    }

    /**
     * Generate a Spreadsheet object with multiple sheets, headers, and column protections.
     *
     * @return Spreadsheet
     */
    private function generateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        foreach ($this->sheets as $index => $sheetData) {
            $sheet = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();

            $sheet->setTitle($sheetData['name'] ?? 'Sheet' . ($index + 1));

            $currentRow = 1;

            // Add title if provided
            if (!empty($sheetData['title'])) {
                $sheet->setCellValue('A' . $currentRow, $sheetData['title']);
                $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $currentRow++;
            }

            // Add subtitle if provided
            if (!empty($sheetData['subtitle'])) {
                $sheet->setCellValue('A' . $currentRow, $sheetData['subtitle']);
                $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $currentRow++;
            }

            // Add empty row after title/subtitle
            if (!empty($sheetData['title']) || !empty($sheetData['subtitle'])) {
                $currentRow++;
            }

            // Add headers
            $this->addHeaders($sheet, $sheetData['headers'], $sheetData['protectedColumns'] ?? [], $currentRow);

            // Add data
            $dataStartRow = $currentRow + 1;
            if (!empty($sheetData['data'])) {
                $sheet->fromArray($sheetData['data'], null, 'A' . $dataStartRow);
            } else {
                $sheet->fromArray([array_fill(0, count($sheetData['headers']), '')], null, 'A' . $dataStartRow);
            }

            // Adjust column widths dynamically
            $this->adjustColumnWidths($sheet, $sheetData['headers']);

            // Protect specific columns
            if($sheetData['allowInsert'] == false)
            {
                $this->protectColumns($sheet, $sheetData['protectedColumns'] ?? []);
            }
        }

        return $spreadsheet;
    }

    /**
     * Add headers to the sheet with custom styling.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet The worksheet object.
     * @param array $headers Array of header names.
     * @param array $protectedColumns Array of protected columns (e.g., ['A', 'B']).
     * @param int $startRow The row number to start adding headers.
     */
    private function addHeaders($sheet, array $headers, array $protectedColumns, int $startRow = 1)
    {
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $cell = "{$column}{$startRow}";

            $sheet->setCellValue($cell, $header);
            $sheet
                ->getStyle($cell)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $fontStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => str_contains($header, '*') ? 'FF0000' : '000000'],
                ],
            ];
            $sheet->getStyle($cell)->applyFromArray($fontStyle);

            if (in_array($column, $protectedColumns)) {
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D3D3D3'],
                    ],
                ]);
            }
        }
    }
    /**
     * Protect specific columns while allowing or restricting certain actions.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet The worksheet object.
     * @param array $protectedColumns Array of columns to protect.
     * @param bool $allowInsert Whether to allow row insertion.
     */
    private function protectColumns($sheet, array $protectedColumns)
    {
        $highestRow = $sheet->getHighestRow();
        $lastColumn = chr(64 + count($sheet->getColumnDimensions()));

        // Unlock all cells by default
        $sheet
            ->getStyle($sheet->calculateWorksheetDimension())
            ->getProtection()
            ->setLocked(StyleProtection::PROTECTION_UNPROTECTED);

        // Define gray fill style for protected columns
        $protectedStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'], // Warna abu-abu
            ],
            'protection' => [
                'locked' => StyleProtection::PROTECTION_PROTECTED,
            ],
        ];

        // Protect header columns and apply gray fill
        foreach ($protectedColumns as $column) {
            $headerCell = "{$column}1";
            $sheet->getStyle($headerCell)->applyFromArray($protectedStyle);
        }

        // Protect specific columns in data rows and apply gray fill
        foreach ($protectedColumns as $column) {
            $range = "{$column}2:{$column}{$highestRow}";
            $sheet->getStyle($range)->applyFromArray($protectedStyle);
        }

        // Allow row insertion if enabled
        // if ($allowInsert) {
        //     $nextRow = $highestRow + 1;
        //     $endUnlockedRow = $nextRow + 100;
        //     $range = "A{$nextRow}:{$lastColumn}{$endUnlockedRow}";
        //     $sheet
        //         ->getStyle($range)
        //         ->getProtection()
        //         ->setLocked(StyleProtection::PROTECTION_UNPROTECTED);
        // }

        // Enable sheet protection with password
        // $protection = $sheet->getProtection();
        // $protection->setSheet(true);
        // $protection->setPassword('NusaevoTeknologi');
    }

    /**
     * Adjust column widths based on header length.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet The worksheet object.
     * @param array $headers Array of header names.
     */
    private function adjustColumnWidths($sheet, array $headers)
    {
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $headerLength = mb_strlen($header ?? '');
            $minWidth = 10;
            $calculatedWidth = $headerLength + 5;
            $adjustedWidth = max($minWidth, $calculatedWidth);

            $sheet->getColumnDimension($column)->setWidth($adjustedWidth);
        }
    }
}
