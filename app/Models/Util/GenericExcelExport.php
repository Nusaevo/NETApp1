<?php

namespace App\Models\Util;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Protection as StyleProtection;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Response;

class GenericExcelExport
{
    protected array $sheets; // Multi-sheet data
    protected string $filename;

    public function __construct(array $sheets, string $filename = 'export.xlsx')
    {
        /**
         * $sheets = [
         *    [
         *        'name' => 'Sheet1',
         *        'headers' => [...],
         *        'data' => [...],
         *        'protectedColumns' => ['A', 'B'],
         *        'allowInsert' => true, // Allow inserting new rows
         *    ],
         *    [
         *        'name' => 'Sheet2',
         *        'headers' => [...],
         *        'data' => [...],
         *        'protectedColumns' => ['C'],
         *        'allowInsert' => false, // Disallow inserting new rows
         *    ]
         * ];
         */
        $this->sheets = $sheets;
        $this->filename = $filename;
    }

    /**
     * Download Excel file.
     */
    public function download()
    {
        $spreadsheet = new Spreadsheet();

        foreach ($this->sheets as $index => $sheetData) {
            $sheet = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();

            $sheet->setTitle($sheetData['name'] ?? 'Sheet' . ($index + 1));

            // Add headers
            $this->addHeaders($sheet, $sheetData['headers'], $sheetData['protectedColumns'] ?? []);

            // Add data if available
            if (!empty($sheetData['data'])) {
                $sheet->fromArray($sheetData['data'], null, 'A2');
            } else {
                // Add a placeholder empty row for template use
                $sheet->fromArray([array_fill(0, count($sheetData['headers']), '')], null, 'A2');
            }

            // Adjust column widths
            $this->adjustColumnWidths($sheet, $sheetData['headers']);

            // Protect specific columns
            $this->protectColumns($sheet, $sheetData['protectedColumns'] ?? [], $sheetData['allowInsert'] ?? false);
        }

        // Save and download the file
        $temp_file = tempnam(sys_get_temp_dir(), $this->filename);
        (new Xlsx($spreadsheet))->save($temp_file);

        return Response::download($temp_file, $this->filename)->deleteFileAfterSend(true);
    }

    /**
     * Add headers with custom styling.
     */
    private function addHeaders($sheet, array $headers, array $protectedColumns)
    {
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index); // Convert index to column letter (A, B, C, etc.)
            $cell = "{$column}1";

            // Set header value (uppercase)
            $sheet->setCellValue($cell, strtoupper($header));

            // Apply center alignment
            $sheet
                ->getStyle($cell)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Apply font styles
            $fontStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => str_contains($header, '*') ? 'FF0000' : '000000'], // Red if '*'
                ],
            ];
            $sheet->getStyle($cell)->applyFromArray($fontStyle);

            // Apply grey background ONLY to protected headers
            if (in_array($column, $protectedColumns)) {
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D3D3D3'], // Light Grey background for protected headers
                    ],
                ]);
            }
        }
    }
    /**
     * Protect headers and specific columns, allowing row insertion if specified.
     */
    private function protectColumns($sheet, array $protectedColumns, bool $allowInsert)
    {
        // 1. Unlock the entire sheet by default
        $sheet
            ->getStyle($sheet->calculateWorksheetDimension())
            ->getProtection()
            ->setLocked(StyleProtection::PROTECTION_UNPROTECTED);

        // 2. Protect Headers (Row 1)
        $headerColumns = range('A', chr(64 + count($sheet->getColumnDimensions())));
        foreach ($headerColumns as $column) {
            $headerCell = "{$column}1";

            // Lock header cells
            $sheet
                ->getStyle($headerCell)
                ->getProtection()
                ->setLocked(StyleProtection::PROTECTION_PROTECTED);

            // Apply grey background only to protected headers
            if (in_array($column, $protectedColumns)) {
                $sheet->getStyle($headerCell)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D3D3D3'], // Light Grey
                    ],
                ]);
            }
        }

        // 3. Protect Specific Columns Below Headers (Up to the last data row if data exists)
        $highestRow = $sheet->getHighestRow(); // Get the last row with data
        $lastColumn = chr(64 + count($sheet->getColumnDimensions())); // Determine the last column dynamically

        if ($highestRow > 1) {
            // Protect columns up to the last data row
            foreach ($protectedColumns as $column) {
                $range = "{$column}2:{$column}{$highestRow}";

                $sheet
                    ->getStyle($range)
                    ->getProtection()
                    ->setLocked(StyleProtection::PROTECTION_PROTECTED);

                $sheet->getStyle($range)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D3D3D3'],
                    ],
                ]);
            }
        }

        if ($allowInsert) {
            $nextRow = $highestRow + 1;
            $endUnlockedRow = $nextRow + 1000;

            $range = "A{$nextRow}:{$lastColumn}{$endUnlockedRow}";
            $sheet
                ->getStyle($range)
                ->getProtection()
                ->setLocked(StyleProtection::PROTECTION_UNPROTECTED);
        }

        // 5. Enable Sheet Protection
        $protection = $sheet->getProtection();
        $protection->setSheet(true);
        $protection->setPassword('securepassword');

        // Explicitly allow actions based on $allowInsert
        $protection->setInsertRows($allowInsert); // ✅ Allow inserting rows based on parameter
        $protection->setDeleteRows(false); // ❌ Prevent deleting rows
        $protection->setInsertColumns(false); // ❌ Prevent inserting columns
        $protection->setDeleteColumns(false); // ❌ Prevent deleting columns
        $protection->setSort(false); // ❌ Prevent sorting
        $protection->setFormatCells(false); // ❌ Prevent formatting cells
    }

    /**
     * Adjust column widths dynamically based on header length only.
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
