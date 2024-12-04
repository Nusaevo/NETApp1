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
    const FILENAME_PREFIX = 'Material';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = ['code', 'name', 'descr', 'type_code', 'class_code', 'category', 'remarks', 'brand', 'dimension', 'wgt', 'qty_min', 'specs', 'supplier_id', 'supplier_code', 'supplier_id1', 'supplier_id2', 'supplier_id3', 'matl_price', 'sellprc_calc_method', 'price_markup_id', 'price_markup_code', 'buying_price', 'selling_price', 'cogs', 'partner_id', 'partner_code', 'taxable', 'info', 'status_code', 'created_by', 'updated_by', 'remarks'];

    public static function validateExcelUpload($dataTable, $audit, $param)
    {
        $errors = [];
        $validHeaderCounts = ($param === "Create") ? [10, 12] : [12, 14]; // Sesuaikan jumlah kolom
        $sheetName = self::FILENAME_PREFIX . ($param === 'Create' ? '_Create_Template' : '_Update_Template');

        $filename = self::FILENAME_PREFIX . ($param === 'Create' ? 'Create' : 'Update') . '_Validation_Result_' . now()->format('Y-m-d_His') . '.xlsx';

        $masterService = new MasterService();
        $actualHeaders = $dataTable[0] ?? [];

        // Validasi jumlah header
        if (!in_array(count($actualHeaders), $validHeaderCounts)) {
            $audit->updateAuditTrail(100, 'Template salah: Header tidak sesuai dengan jumlah kolom yang diharapkan.', Status::ERROR);
            Attachment::uploadExcelAttachment($dataTable, $filename, $audit->id, 'ConfigAudit', $sheetName);

            return [
                'success' => false,
                'dataTable' => $dataTable,
            ];
        }

        // Tambahkan kolom Status dan Message jika tidak ada
        $headerHasStatusAndMessage = in_array('Status', $actualHeaders) && in_array('Message', $actualHeaders);
        if (!$headerHasStatusAndMessage) {
            $dataTable[0][] = 'Status';
            $dataTable[0][] = 'Message';
        }

        $statusIndex = array_search('Status', $dataTable[0]);
        $messageIndex = array_search('Message', $dataTable[0]);

        foreach ($dataTable as $index => $row) {
            if ($index === 0) {
                if (count($dataTable) === 1 || empty(array_filter($dataTable[1] ?? []))) {
                    $audit->updateAuditTrail(100, 'Error: Data tidak ditemukan.', Status::ERROR);
                    return [
                        'success' => false,
                        'dataTable' => $dataTable,
                    ];
                }
                continue;
            }

            $status = '';
            $message = '';

            // Validasi umum untuk kolom `No*`
            $no = $row[0] ?? null;
            if (empty($no)) {
                $status = 'Error';
                $message .= 'Kolom No* tidak boleh kosong. ';
            }

            if ($param === 'Create') {
                // Validasi Create
                $category = $row[1] ?? null;
                $brand = $row[2] ?? null;
                $type = $row[3] ?? null;
                $uom = $row[6] ?? null;
                $sellingPrice = $row[7] ?? null;

                if (empty($category)) {
                    $status = 'Error';
                    $message .= 'Kategori tidak boleh kosong. ';
                }

                if (empty($brand)) {
                    $status = 'Error';
                    $message .= 'Merk tidak boleh kosong. ';
                }

                if (empty($type)) {
                    $status = 'Error';
                    $message .= 'Jenis tidak boleh kosong. ';
                }

                if (empty($uom)) {
                    $status = 'Error';
                    $message .= 'UOM tidak boleh kosong. ';
                }

                if (!isValidNumeric($sellingPrice)) {
                    $status = 'Error';
                    $message .= 'Harga jual harus berupa angka positif. ';
                }
            } elseif ($param === 'Update') {
                // Validasi Update
                $materialCode = $row[6] ?? null;
                $version = $row[11] ?? null;

                if (empty($materialCode)) {
                    $status = 'Error';
                    $message .= 'Kode Barang tidak boleh kosong. ';
                }

                if (empty($version)) {
                    $status = 'Error';
                    $message .= 'Version tidak boleh kosong. ';
                }
            }

            // Tambahkan status dan pesan ke baris
            $dataTable[$index][$statusIndex] = $status;
            $dataTable[$index][$messageIndex] = $message;

            if ($status === 'Error') {
                $errors[] = "Row $index: $message";
            }
        }

        Attachment::uploadExcelAttachment($dataTable, $filename, $audit->id, 'ConfigAudit', $sheetName);

        return [
            'success' => empty($errors),
            'dataTable' => $dataTable,
        ];
    }


    public static function generateMaterialCode($category)
    {
        if (isNullOrEmptyString($category)) {
            throw new \InvalidArgumentException("Mohon pilih kategori untuk mendapatkan material code.");
        }

        $configSnum = ConfigSnum::where('code', '=', 'MMATL_' . $category . '_LASTID')->first();

        if ($configSnum) {
            $stepCnt = $configSnum->step_cnt;
            $proposedTrId = $configSnum->last_cnt + $stepCnt;

            if ($proposedTrId > $configSnum->wrap_high) {
                $proposedTrId = $configSnum->wrap_low;
            }

            $proposedTrId = max($proposedTrId, $configSnum->wrap_low);
            $materialCode = $category . $proposedTrId;

            $configSnum->last_cnt = $proposedTrId;
            $configSnum->save();

            return $materialCode;
        }

        throw new \RuntimeException("Tidak ada kode ditemukan untuk kategori produk ini.");
    }

    public static function processExcelUpload($dataTable, $audit, $param)
    {
        $masterService = new MasterService();
        $statusIndex = array_search('Status', $dataTable[0]);
        $messageIndex = array_search('Message', $dataTable[0]);
        DB::beginTransaction();

        try {
            foreach ($dataTable as $rowIndex => $row) {
                // Skip header dan baris dengan status 'Error'
                if ($rowIndex === 0 || ($row[$statusIndex] ?? '') === 'Error') {
                    continue;
                }

                $status = 'Success';
                $message = '';

                if ($param === 'Create') {
                    // Proses untuk template Create
                    $category = $row[0] ?? ''; // Kategori*
                    $brand = $row[1] ?? '';    // Merk*
                    $type = $row[2] ?? '';     // Jenis*
                    $no = $row[3] ?? '';       // No*
                    $colorCode = $row[4] ?? ''; // Kode Warna (Optional)
                    $colorName = $row[5] ?? ''; // Nama Warna (Optional)
                    $uom = $row[6] ?? '';       // UOM*
                    $sellingPrice = convertFormattedNumber($row[7]); // Harga Jual*
                    $remarks = $row[8] ?? '';   // Keterangan (Optional)
                    $barcode = $row[9] ?? '';   // Kode Barcode (Optional)


                    // Generate kode material
                    $materialCode =  Material::generateMaterialCode($category);

                    // Buat material baru
                    $material = Material::create([
                        'code' => $materialCode,
                        'category' => $category,
                        'brand' => $brand,
                        'type_code' => $type,
                        'specs' => json_encode(['color_code' => $colorCode, 'color_name' => $colorName]),
                        'selling_price' => $sellingPrice,
                        'remarks' => $remarks,
                    ]);

                    // Buat UOM dan barcode
                    if ($material) {
                        $material->MatlUom()->create([
                            'matl_uom' => $uom,
                            'barcode' => $barcode,
                        ]);
                    }
                } elseif ($param === 'Update') {
                    // Proses untuk template Update
                    $no = $row[0] ?? "";
                    $colorCode = $row[1] ?? "";
                    $colorName = $row[2] ?? "";
                    $uom = $row[3] ?? "";
                    $sellingPrice = convertFormattedNumber($row[4]);
                    $stock = convertFormattedNumber($row[5] ?? null);
                    $materialCode = $row[6] ?? "";
                    $barcode = $row[7] ?? "";
                    $materialName = $row[8] ?? "";
                    $nonActive = ($row[9] === 'Yes') ? now() : null;
                    $remarks = $row[10] ?? "";
                    $version = $row[11] ?? "";
                    // Cari material berdasarkan kode
                    $material = Material::where('code', $materialCode)->first();

                    if ($material) {
                        // Perbarui data material
                        $material->update([
                            'specs' => json_encode(['color_code' => $colorCode, 'color_name' => $colorName]),
                            'selling_price' => $sellingPrice,
                            'name' => $materialName,
                            'deleted_at' => $nonActive,
                            'remarks' => $remarks,
                            'version_number' => $version++,
                        ]);

                        // Perbarui stok
                        if ($stock !== null) {
                            $ivtBal = $material->IvtBal()->first();
                            if ($ivtBal) {
                                $ivtBal->update(['qty_oh' => $stock]);
                            } else {
                                $material->IvtBal()->create(['qty_oh' => $stock]);
                            }
                        }

                        // Perbarui UOM dan barcode
                        $uomData = $material->MatlUom()->first();
                        if ($uomData) {
                            $uomData->update(['matl_uom' => $uom, 'barcode' => $barcode]);
                        } else {
                            $material->MatlUom()->create(['matl_uom' => $uom, 'barcode' => $barcode]);
                        }
                    } else {
                        $status = 'Error';
                        $message = 'Material dengan kode ' . $materialCode . ' tidak ditemukan.';
                    }
                }

                // Tambahkan status dan pesan ke baris
                $dataTable[$rowIndex][$statusIndex] = $status;
                $dataTable[$rowIndex][$messageIndex] = $message;

                // Perbarui progress audit
                $audit->updateAuditTrail(
                    intval(50 + ($rowIndex / count($dataTable)) * 50),
                    "Processed row $rowIndex.",
                    Status::IN_PROGRESS
                );
            }

            DB::commit();
            $audit->updateAuditTrail(100, 'Upload and processing completed successfully.', Status::SUCCESS);
        } catch (\Exception $e) {
            DB::rollback();
            $audit->updateAuditTrail(100, 'Processing failed: ' . $e->getMessage(), Status::ERROR);
            throw $e;
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
            'qty_oh' => '0',
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
        return trim(
            implode(
                ' ',
                array_filter([
                    $this->code, // Kode Barang
                    $this->MatlUom[0]->barcode, // Kode Barcode
                    $this->brand, // Merk
                    $this->type_code, // Tipe
                    $colorCode, // Color Code
                    $colorName, // Color Name
                ]),
            ),
        );
    }
}
