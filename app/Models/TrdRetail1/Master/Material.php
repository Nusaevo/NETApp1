<?php

namespace App\Models\TrdRetail1\Master;

use App\Helpers\SequenceUtility;
use App\Models\TrdRetail1\Base\TrdRetail1BaseModel;
use App\Models\TrdRetail1\Base\Attachment;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Transaction\OrderDtl;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdRetail1\Config\ConfigAudit;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use App\Models\SysConfig1\ConfigSnum;


class Material extends TrdRetail1BaseModel
{
    protected $table = 'materials';
    use SoftDeletes;
    const FILENAME_PREFIX = 'Material_Template_';
    const SHEET_NAME = 'Material Template';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'code',
        'name',
        'descr',
        'type_code',
        'class_code',
        'category',
        'remarks',
        'brand',
        'dimension',
        'wgt',
        'qty_min',
        'specs',
        'supplier_id',
        'supplier_code',
        'supplier_id1',
        'supplier_id2',
        'supplier_id3',
        'matl_price',
        'sellprc_calc_method',
        'price_markup_id',
        'price_markup_code',
        'buying_price',
        'selling_price',
        'cogs',
        'partner_id',
        'partner_code',
        'taxable',
        'info',
        'status_code',
        'created_by',
        'updated_by',
        'remarks'
    ];
    public static function validateExcelUpload($dataTable, $audit)
    {
        $errors = [];
        $validHeaderCounts = [12, 14]; // Valid header counts
        $masterService = new MasterService();
        $actualHeaders = $dataTable[0] ?? [];

        if (!in_array(count($actualHeaders), $validHeaderCounts)) {
            $audit->updateAuditTrail(100, 'Template salah: Header tidak sesuai dengan jumlah kolom yang diharapkan.', Status::ERROR);

            $filename = Material::FILENAME_PREFIX . now()->format('Y-m-d_His') . '.xlsx';
            Attachment::uploadExcelAttachment($dataTable, $filename, $audit->id, 'ConfigAudit', Material::SHEET_NAME);

            return [
                'success' => false,
                'dataTable' => $dataTable
            ];
        }

        // Append Status and Message columns if not present
        $headerHasStatusAndMessage = in_array("Status", $actualHeaders) && in_array("Message", $actualHeaders);
        if (!$headerHasStatusAndMessage) {
            $dataTable[0][] = "Status";
            $dataTable[0][] = "Message";
        }

        $statusIndex = array_search('Status', $dataTable[0]);
        $messageIndex = array_search('Message', $dataTable[0]);

        foreach ($dataTable as $index => $row) {
            if ($index === 0) {
                continue; // Skip header row
            }

            $status = "";
            $message = "";

            // Extract fields
            $category = $row[0] ?? null;
            $brand = $row[1] ?? null;
            $type = $row[2] ?? null;
            $colorCode = $row[4] ?? null;
            $colorName = $row[5] ?? null;
            $uom = $row[6] ?? null;
            $sellingPrice = $row[7] ?? null;
            $stock = $row[8] ?? null;
            $barcode = $row[10] ?? null;
            // Validate required fields
            if (empty($category)) {
                $status = "Error";
                $message .= "Kategori tidak boleh kosong. ";
            } elseif (!$masterService->isValidMatlCategory($category)) {
                $status = "Error";
                $message .= "Kategori tidak valid. ";
            }

            if (empty($brand)) {
                $status = "Error";
                $message .= "Merk tidak boleh kosong. ";
            }

            if (empty($type)) {
                $status = "Error";
                $message .= "Jenis tidak boleh kosong. ";
            }

            if (empty($uom)) {
                $status = "Error";
                $message .= "UOM tidak boleh kosong. ";
            }

            if (!isValidNumeric($sellingPrice ?? null)) {
                $status = "Error";
                $message .= "Harga jual harus berupa angka positif. ";
            }

            if (!isValidNumeric($stock) || $stock < 0) {
                $status = "Error";
                $message .= "Stok harus berupa angka dan minimal 0. ";
            }

            // Append status and message to the row
            $dataTable[$index][$statusIndex] = $status;
            $dataTable[$index][$messageIndex] = $message;

            // Log errors
            if ($status === "Error") {
                $errors[] = "Row $index: $message";
            }

            // Update audit progress
            $audit->updateAuditTrail(intval(($index / count($dataTable)) * 50), "Validated row $index.", Status::IN_PROGRESS);
        }

        // Upload validation result
        $filename = Material::FILENAME_PREFIX . now()->format('Y-m-d_His') . '.xlsx';
        Attachment::uploadExcelAttachment($dataTable, $filename, $audit->id, 'ConfigAudit', Material::SHEET_NAME);

        if (!empty($errors)) {
            $audit->updateAuditTrail(100, 'Validation failed. Mohon download hasil untuk cek error.', Status::ERROR);
        }

        return [
            'success' => empty($errors),
            'dataTable' => $dataTable
        ];
    }

    public static function processExcelUpload($dataTable, $audit)
    {
        $masterService = new MasterService();
        $statusIndex = array_search('Status', $dataTable[0]);
        $messageIndex = array_search('Message', $dataTable[0]);
        DB::beginTransaction();

        try {
            foreach ($dataTable as $rowIndex => $row) {
                // Skip header and rows with errors
                if ($rowIndex === 0 || ($row[$statusIndex] ?? '') === "Error") {
                    continue;
                }

                $status = "Success";
                $message = "";

                $categoryStr2 = $row[0] ?? '';
                $brand = $row[1] ?? '';
                $type = $row[2] ?? '';
                $colorCode = $row[4] ?? '';
                $colorName = $row[5] ?? '';
                $uom = $row[6] ?? '';
                $sellingPrice = convertFormattedNumber($row[7]);
                $stock = convertFormattedNumber($row[8]);
                $barcode = $row[10] ?? '';
                $remarks = $row[11] ?? '';

                // Validate and get category str1
                $categoryStr1 = $masterService->isValidMatlCategory($categoryStr2);
                if (!$categoryStr1) {
                    $status = "Error";
                    $message .= "Kategori tidak valid. ";
                    $dataTable[$rowIndex][$statusIndex] = $status;
                    $dataTable[$rowIndex][$messageIndex] = $message;
                    continue;
                }

                // Generate material code if new material
                $configSnum = ConfigSnum::where('code', '=', 'MMATL_' . $categoryStr1 . '_LASTID')->first();
                $materialCode = $row[9] ?? '';

                if (!$materialCode && $configSnum) {
                    $stepCnt = $configSnum->step_cnt;
                    $proposedTrId = $configSnum->last_cnt + $stepCnt;

                    if ($proposedTrId > $configSnum->wrap_high) {
                        $proposedTrId = $configSnum->wrap_low;
                    }

                    $proposedTrId = str_pad($proposedTrId, 6, '0', STR_PAD_LEFT);
                    $materialCode = $categoryStr1 . $proposedTrId;
                    $configSnum->last_cnt = $proposedTrId;
                    $configSnum->save();
                }

                // Update or create material
                $material = Material::where('code', $row[9])->first();
                if ($material) {
                    $material->update([
                        'category' => $categoryStr1,
                        'brand' => $brand,
                        'type_code' => $type,
                        'specs' => json_encode(['color_code' => $colorCode, 'color_name' => $colorName]),
                        'selling_price' => $sellingPrice,
                        'remarks' => $remarks,
                    ]);
                } else {
                    $material = Material::create([
                        'code' => $materialCode,
                        'category' => $categoryStr1,
                        'brand' => $brand,
                        'type_code' => $type,
                        'specs' => json_encode(['color_code' => $colorCode, 'color_name' => $colorName]),
                        'selling_price' => $sellingPrice,
                        'remarks' => $remarks,
                    ]);
                }

                // Handle stock update
                $ivtBal = $material->IvtBal()->first();
                if ($ivtBal) {
                    $ivtBal->update(['qty_oh' => $stock]);
                } else {
                    $material->IvtBal()->create(['qty_oh' => $stock]);
                }

                // Handle UOM and Barcode
                $uomData = $material->MatlUom()->first();
                if ($uomData) {
                    $uomData->update(['matl_uom' => $uom, 'barcode' => $barcode]);
                } else {
                    $material->MatlUom()->create(['matl_uom' => $uom, 'barcode' => $barcode]);
                }

                // Update status and message
                $dataTable[$rowIndex][$statusIndex] = $status;
                $dataTable[$rowIndex][$messageIndex] = $message;

                // Update audit progress
                $audit->updateAuditTrail(intval(50 + (($rowIndex / count($dataTable)) * 50)), "Processed row $rowIndex.", Status::IN_PROGRESS);
            }

            // Upload processed result
            $filename = Material::FILENAME_PREFIX . now()->format('Y-m-d_His') . '.xlsx';
            Attachment::uploadExcelAttachment($dataTable, $filename, $audit->id, 'ConfigAudit', Material::SHEET_NAME);

            DB::commit();
            $audit->updateAuditTrail(100, 'Upload and processing completed successfully.', Status::SUCCESS);

            return ['success' => true, 'message' => 'Data successfully processed and uploaded.'];
        } catch (\Exception $e) {
            DB::rollback();
            $audit->updateAuditTrail(100, "Processing failed: " . $e->getMessage(), Status::ERROR);

            // Upload error result
            $filename = Material::FILENAME_PREFIX . now()->format('Y-m-d_His') . '.xlsx';
            Attachment::uploadExcelAttachment($dataTable, $filename, $audit->id, 'ConfigAudit', Material::SHEET_NAME);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }




    #region Relations
    public function MatlUom()
    {
        return $this->hasMany(MatlUom::class, 'matl_id');
    }


    public function ivtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id')->withDefault([
            'qty_oh' => '0'
        ]);
    }
    #endregion

    #region Attributes
    public function getSellingPriceTextAttribute()
    {
        return rupiah($this->selling_price);
    }
    #endregion

    public static function getAvailableMaterials()
    {
        return self::whereHas('ivtBal', function ($query) {
                    $query->where('qty_oh', '>', 0);
                })
                ->whereNull('materials.deleted_at')
                ->distinct();
    }

    public function hasQuantity()
    {
        return IvtBal::where('matl_id', $this->id)
            ->where('qty_oh', '>', 0)
            ->exists();
    }

    public function getStockAttribute()
    {
        return $this->ivtBal ? $this->ivtBal->qty_oh : 0;
    }

     /**
     * Get the concatenated tag attribute.
     */
    public function getTagAttribute()
    {
        // Extract color_code and color_name from specs
        $specs = json_decode($this->specs, true) ?? [];
        $colorCode = $specs['color_code'] ?? '';
        $colorName = $specs['color_name'] ?? '';

        // Concatenate fields into the tag
        return trim(implode(' ', array_filter([
            $this->code,          // Kode Barang
            $this->MatlUom[0]->barcode,       // Kode Barcode
            $this->brand,         // Merk
            $this->type_code,     // Tipe
            $colorCode,           // Color Code
            $colorName,           // Color Name
        ])));
    }

}
