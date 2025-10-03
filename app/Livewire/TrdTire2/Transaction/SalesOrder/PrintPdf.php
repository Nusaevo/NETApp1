<?php

namespace App\Livewire\TrdTire2\Transaction\SalesOrder;

use App\Enums\TrdTire2\Status;
use App\Models\TrdTire2\Transaction\OrderHdr;
use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\Auth;
use App\Models\Util\GenericExcelExport;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PrintPdf extends BaseComponent
{
    public $object;
    public $objectIdValue;

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = OrderHdr::findOrFail($this->objectIdValue);

            // Guard izin cetak ulang untuk Nota Jual saja (cetakan pertama diizinkan untuk semua)
            $revData = $this->object->getPrintCounterArray();
            $hasPrinted = (($revData['nota'] ?? 0) > 0);
            if ($hasPrinted) {
                $userId = Auth::id();
                $allowed = (int) (ConfigConst::where('const_group', 'SEC_LEVEL')
                    ->where('str2', 'UPDATE_AFTER_PRINT')
                    ->where('user_id', $userId)
                    ->value('num1') ?? 0) === 1;
                if (!$allowed) {
                    $this->dispatch('error', 'Anda tidak memiliki izin untuk mencetak ulang.');
                    // Redirect balik ke detail
                    return redirect()->route(
                        'TrdTire2.Transaction.SalesOrder.Detail',
                        [
                            'action'   => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($this->object->id),
                        ]
                    );
                }
            }
            // Update status_code to PRINT
            $this->object->status_code = Status::PRINT;
            $this->object->save();
        }
    }

    /**
     * Update print counter untuk nota jual
     */
    public function updatePrintCounter()
    {
        if ($this->object) {
            $newVersion = OrderHdr::updatePrintCounterStatic($this->object->id);
            $this->dispatch('success', 'Print counter berhasil diupdate: ' . $newVersion);
            $this->dispatch('refreshData');
        }
    }

    protected function onLoadForEdit()
    {
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    protected function onPopulateDropdowns()
    {

    }

    protected function onReset()
    {
    }

    public function onValidateAndSave()
    {
    }

    public function downloadExcel()
    {
        // Validasi: pastikan ada object untuk di-export
        if (!$this->object) {
            $this->dispatch('error', 'Data tidak ditemukan untuk di-export.');
            return;
        }

        try {
            // Siapkan data untuk Excel
            $excelData = [];
            $rowStyles = [];
            $currentRowIndex = 0;

            // Header perusahaan (kiri atas)
            $excelData[] = [
                'CAHAYA TERANG',
                '',
                'NOTA PENJUALAN',
                '',
                'Surabaya, ' . Carbon::parse($this->object->tr_date)->format('d-M-Y'),
                ''
            ];

            // Styling untuk header perusahaan
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'bold' => true,
                'specificCells' => ['A', 'C', 'E']
            ];
            $currentRowIndex++;

            // Baris kedua header
            $excelData[] = [
                'SURABAYA',
                '',
                'No. ' . $this->object->tr_code,
                '',
                'Kepada Yth :',
                ''
            ];

            // Styling untuk baris kedua
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'bold' => true,
                'specificCells' => ['A', 'C', 'E']
            ];
            $currentRowIndex++;

            // Informasi customer
            $excelData[] = [
                '',
                '',
                '',
                '',
                $this->object->Partner->name ?? '',
                ''
            ];
            $currentRowIndex++;

            $excelData[] = [
                '',
                '',
                '',
                '',
                $this->object->Partner->address ?? '',
                ''
            ];
            $currentRowIndex++;

            $excelData[] = [
                '',
                '',
                '',
                '',
                $this->object->Partner->city ?? '',
                ''
            ];
            $currentRowIndex++;

            // Baris kosong
            $excelData[] = ['', '', '', '', '', ''];
            $currentRowIndex++;

            // Header tabel dengan border
            if ($this->object->sales_type != 'O') {
                $excelData[] = [
                    'KODE BARANG',
                    'NAMA BARANG',
                    'QTY',
                    'HARGA SATUAN',
                    'DISC',
                    'JUMLAH HARGA'
                ];
            } else {
                $excelData[] = [
                    'KODE BARANG',
                    'NAMA BARANG',
                    'QTY',
                    'HARGA SATUAN',
                    '',
                    'JUMLAH HARGA'
                ];
            }

            // Styling untuk header tabel
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'bold' => true,
                'borderAll' => true,
                'backgroundColor' => 'F0F0F0'
            ];
            $currentRowIndex++;

            // Data items
            $grandTotal = 0;
            $itemCount = $this->object->OrderDtl->count();
            $itemIndex = 0;

            foreach ($this->object->OrderDtl as $item) {
                $itemIndex++;
                $discount = $item->disc_pct / 100;
                $priceAfterDisc = round($item->price * (1 - $discount));
                $subTotalAfterDisc = $priceAfterDisc * $item->qty;
                $grandTotal += $subTotalAfterDisc;

                if ($this->object->sales_type != 'O') {
                    $excelData[] = [
                        $item->matl_code,
                        $item->matl_descr,
                        ceil($item->qty),
                        $priceAfterDisc,
                        $item->disc_pct . '%',
                        $subTotalAfterDisc
                    ];
                } else {
                    $excelData[] = [
                        $item->matl_code,
                        $item->matl_descr,
                        ceil($item->qty),
                        $priceAfterDisc,
                        '',
                        $subTotalAfterDisc
                    ];
                }

                // Styling untuk setiap baris data dengan border
                if ($itemIndex == $itemCount) {
                    // Baris terakhir dengan border bottom
                    $rowStyles[] = [
                        'rowIndex' => $currentRowIndex,
                        'borderLeft' => true,
                        'borderRight' => true,
                        'borderBottom' => true
                    ];
                } else {
                    // Baris biasa dengan border kiri dan kanan
                    $rowStyles[] = [
                        'rowIndex' => $currentRowIndex,
                        'borderLeft' => true,
                        'borderRight' => true
                    ];
                }

                // Format number untuk kolom angka
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'specificCells' => ['D', 'F'], // Harga Satuan dan Jumlah Harga
                    'numberFormat' => '#,##0'
                ];
                $currentRowIndex++;
            }

            // Baris total di dalam tabel (tanpa baris kosong)
            $excelData[] = [
                '',
                '',
                '',
                '',
                'Total',
                $grandTotal
            ];

            // Styling untuk total - melanjutkan border tabel
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'bold' => true,
                'borderLeft' => true,
                'borderRight' => true,
                'borderBottom' => true,
                'backgroundColor' => 'FFFFCC',
                'rangeColumns' => ['E', 'F']
            ];

            // Format number untuk total
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'specificCells' => ['F'],
                'numberFormat' => '#,##0'
            ];
            $currentRowIndex++;

            // Biaya EX (shipping cost) jika ada - masih dalam tabel
            if ($this->object->amt_shipcost > 0) {
                // Hapus border bottom dari total sebelumnya karena akan ada biaya EX
                $rowStyles[count($rowStyles) - 2]['borderBottom'] = false;

                $excelData[] = [
                    '',
                    '',
                    '',
                    '',
                    'Biaya EX',
                    $this->object->amt_shipcost
                ];

                // Styling untuk biaya EX - melanjutkan tabel
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'borderLeft' => true,
                    'borderRight' => true,
                    'rangeColumns' => ['E', 'F']
                ];

                // Format number untuk biaya EX
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'specificCells' => ['F'],
                    'numberFormat' => '#,##0'
                ];
                $currentRowIndex++;

                // Grand Total dengan biaya EX - penutup tabel
                $finalTotal = $grandTotal + $this->object->amt_shipcost;
                $excelData[] = [
                    '',
                    '',
                    '',
                    '',
                    'Grand Total',
                    $finalTotal
                ];

                // Styling untuk grand total - penutup tabel
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'bold' => true,
                    'borderLeft' => true,
                    'borderRight' => true,
                    'borderBottom' => true,
                    'backgroundColor' => 'FFCCCC',
                    'rangeColumns' => ['E', 'F']
                ];

                // Format number untuk grand total
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'specificCells' => ['F'],
                    'numberFormat' => '#,##0'
                ];
                $currentRowIndex++;
            }

            // Baris kosong
            $excelData[] = ['', '', '', '', '', ''];
            $currentRowIndex++;

            // Footer dengan penerima dan pembayaran
            $excelData[] = [
                'Penerima: ________________',
                '',
                '',
                '',
                'Pembayaran: ' . ($this->object->payment_method ?? 'CASH'),
                ''
            ];

            // Styling untuk footer dengan border
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'borderAll' => true
            ];

            // Konfigurasi sheet Excel
            $sheets = [[
                'name' => 'Nota_Penjualan',
                'headers' => [], // Kosongkan headers karena kita buat custom header sendiri
                'data' => $excelData,
                'protectedColumns' => [],
                'allowInsert' => false,
                'title' => '', // Kosongkan title karena sudah ada di data
                'subtitle' => '',
                'titleAlignment' => Alignment::HORIZONTAL_LEFT,
                'subtitleAlignment' => Alignment::HORIZONTAL_LEFT,
                'rowStyles' => $rowStyles,
                'columnWidths' => [
                    'A' => 15,  // Kode Barang
                    'B' => 35,  // Nama Barang
                    'C' => 8,   // Qty
                    'D' => 15,  // Harga Satuan
                    'E' => 12,  // Disc / Label
                    'F' => 18   // Jumlah Harga
                ],
            ]];

            $filename = 'Nota_Penjualan_' . $this->object->tr_code . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();

        } catch (\Exception $e) {
            $this->dispatch('error', 'Error generating Excel: ' . $e->getMessage());
            return;
        }
    }
}
