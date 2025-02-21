<?php

namespace App\Models\TrdRetail1\Master;

use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\Base\Attachment;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Transaction\OrderDtl;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdRetail1\Config\ConfigAudit;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use App\Models\SysConfig1\ConfigSnum;

class Material extends BaseModel
{
    protected $table = 'materials';
    use SoftDeletes;
    const FILENAME_PREFIX = 'Material';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = ['code', 'seq', 'name', 'descr', 'type_code', 'class_code', 'category', 'remarks', 'brand', 'dimension', 'wgt', 'qty_min', 'specs', 'supplier_id', 'supplier_code', 'supplier_id1', 'supplier_id2', 'supplier_id3', 'matl_price', 'sellprc_calc_method', 'price_markup_id', 'price_markup_code', 'buying_price', 'selling_price', 'cogs', 'partner_id', 'partner_code', 'taxable', 'info', 'uom','remarks'];

    /**
     * Get configuration for Create Template.
     */
    public static function getCreateTemplateConfig(array $data = []): array
    {
        return [
            'name' => 'Material_Create_Template',
            'headers' => ['Kategori*', 'Merk*', 'Jenis*', 'No', 'Kode Warna', 'Nama Warna', 'UOM*', 'Harga Jual*', 'Keterangan', 'Kode Barcode', 'Status', 'Message'],
            'data' => $data,
            'protectedColumns' => [],
            'allowInsert' => true,
        ];
    }

    /**
     * Get configuration for Update Template.
     * Data is optional and can be passed dynamically.
     */
    public static function getUpdateTemplateConfig(array $data = []): array
    {
        return [
            'name' => 'Material_Update_Template',
            'headers' => ['No', 'Kode Warna', 'Nama Warna', 'UOM*', 'Harga Jual*', 'STOK', 'Kode Barang', 'Kode Barcode', 'Nama Barang', 'Non Aktif', 'Keterangan', 'Version', 'Status', 'Message'],
            'data' => $data,
            'protectedColumns' => ['A', 'G'],
            'allowInsert' => false,
        ];
    }

    /**
     * Validate uploaded Excel data based on template rules.
     *
     * @param array $dataTable Data from uploaded Excel, including headers.
     * @param ConfigAudit $audit Audit object for logging.
     * @param string $param 'Create' or 'Update' to identify the template type.
     * @return array Validation result and updated data table.
     */
    public static function validateExcelUpload($dataTable, $audit, $param)
    {
        $errors = [];
        $templateConfig = $param === 'Create' ? self::getCreateTemplateConfig() : self::getUpdateTemplateConfig();

        $sheetName = $templateConfig['name'];
        $expectedHeaders = $templateConfig['headers'];
        $filename = self::FILENAME_PREFIX . ($param === 'Create' ? 'Create' : 'Update') . '_Validation_Result_' . now()->format('Y-m-d_His') . '.xlsx';

        // Validate Headers
        $actualHeaders = $dataTable[0] ?? [];
        if ($expectedHeaders !== $actualHeaders) {
            $audit->updateAuditTrail(100, 'Template salah: Header tidak sesuai dengan template.', Status::ERROR);
            $templateConfig['data'] = array_slice($dataTable, 1);
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

            return [
                'success' => false,
                'dataTable' => $dataTable,
            ];
        }

        // Ensure Status and Message columns exist
        $statusIndex = array_search('Status', $dataTable[0]);
        $messageIndex = array_search('Message', $dataTable[0]);

        foreach ($dataTable as $index => $row) {
            if ($index === 0) {
                // Skip header row
                if (count($dataTable) === 1 || empty(array_filter($dataTable[1] ?? []))) {
                    $audit->updateAuditTrail(100, 'Error: Data tidak ditemukan.', Status::ERROR);
                    $templateConfig['data'] = array_slice($dataTable, 1);
                    Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

                    return [
                        'success' => false,
                        'dataTable' => $dataTable,
                    ];
                }
                continue;
            }

            $status = '';
            $message = '';

            // Common validation for `No*`
            $no = $row[0] ?? null;
            if (empty($no)) {
                $status = 'Error';
                $message .= 'Kolom No* tidak boleh kosong. ';
            }

            if ($param === 'Create') {
                // Validation for Create template
                $category = $row[0] ?? null; // Kategori*
                $brand = $row[1] ?? null; // Merk*
                $type = $row[2] ?? null; // Jenis*
                $no = $row[3] ?? null; // No*
                $color_code = $row[4] ?? null; // Kode Warna*
                $color_name = $row[5] ?? null; // Nama Warna*
                $uom = $row[6] ?? null; // UOM*
                $sellingPrice = $row[7] ?? null; // Harga Jual*

                if (empty($no)) {
                    $message .= 'Kolom No* tidak boleh kosong. ';
                }
                if (empty($category)) {
                    $message .= 'Kategori tidak boleh kosong. ';
                }
                if (empty($brand)) {
                    $message .= 'Merk tidak boleh kosong. ';
                }
                if (empty($type)) {
                    $message .= 'Jenis tidak boleh kosong. ';
                }
                if (empty($color_code)) {
                    $message .= 'Kolom Kode Warna* tidak boleh kosong. ';
                }
                if (empty($uom)) {
                    $message .= 'UOM tidak boleh kosong. ';
                }
                if (!isValidNumeric($sellingPrice)) {
                    $message .= 'Harga jual harus berupa angka positif. ';
                }

                // Buat key berdasarkan kombinasi Kategori, Merk, Jenis, dan Color Code
                $combinationKey = trim($category) . '_' . trim($brand) . '_' . trim($type) . '_' . trim($color_code);
                if (isset($combinationTracker[$combinationKey])) {
                    $message .= 'Duplikat dalam file: kombinasi Kategori, Merk, Jenis, dan Kode Warna sudah ada. ';
                } else {
                    $combinationTracker[$combinationKey] = $index; // Tandai kombinasi sebagai sudah ada
                }

                // Cek duplikasi dalam database berdasarkan Kategori, Merk, Jenis, dan Color Code di JSONB (specs->color_code)
                $existingMaterial = Material::where('category', $category)->where('brand', $brand)->where('class_code', $type)->whereJsonContains('specs->color_code', $color_code)->first();

                if ($existingMaterial) {
                    $message .= 'Material dengan kombinasi Kategori, Merk, Jenis, dan Kode Warna sudah ada di database. ';
                }
            } elseif ($param === 'Update') {
                // Validasi Template Update
                $no = $row[0] ?? null; // No*
                $materialCode = $row[6] ?? null; // Kode Barang
                $version = $row[11] ?? null; // Version

                if (empty($no)) {
                    $message .= 'Kolom No* tidak boleh kosong. ';
                }
                if (empty($materialCode)) {
                    $message .= 'Kode Barang tidak boleh kosong. ';
                }
                if (empty($version)) {
                    $message .= 'Version tidak boleh kosong. ';
                }
            }

            if (!empty($message)) {
                $status = 'Error';
                $errors[] = "Row $index: $message";
            }

            // Update Status and Message columns
            $dataTable[$index][$statusIndex] = $status;
            $dataTable[$index][$messageIndex] = $message;
        }

        // Jika terdapat error, unggah hasil validasi
        if (!empty($errors)) {
            $templateConfig['data'] = array_slice($dataTable, 1);
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');
        }

        // Update Audit Trail
        $audit->updateAuditTrail(100, empty($errors) ? 'Validasi selesai tanpa kesalahan.' : 'Validasi selesai dengan kesalahan.', empty($errors) ? Status::SUCCESS : Status::ERROR);

        return [
            'success' => empty($errors),
            'dataTable' => $dataTable,
        ];
    }

    public static function generateMaterialCode($category)
    {
        if (isNullOrEmptyString($category)) {
            throw new \InvalidArgumentException('Mohon pilih kategori untuk mendapatkan material code.');
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

        throw new \RuntimeException('Tidak ada kode ditemukan untuk kategori produk ini.');
    }
    /**
     * Process uploaded Excel data based on template rules.
     *
     * @param array $dataTable Data from uploaded Excel, including headers.
     * @param ConfigAudit $audit Audit object for logging.
     * @param string $param 'Create' or 'Update' to identify the template type.
     */
    public static function processExcelUpload($dataTable, $audit, $param)
    {
        $masterService = new MasterService();
        $templateConfig = $param === 'Create' ? self::getCreateTemplateConfig() : self::getUpdateTemplateConfig();

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
                    $brand = $row[1] ?? ''; // Merk*
                    $type = $row[2] ?? ''; // Jenis*
                    $no = $row[3] ?? ''; // No*
                    $colorCode = $row[4] ?? ''; // Kode Warna (Optional)
                    $colorName = $row[5] ?? ''; // Nama Warna (Optional)
                    $uom = $row[6] ?? ''; // UOM*
                    $sellingPrice = convertFormattedNumber($row[7]); // Harga Jual*
                    $remarks = $row[8] ?? ''; // Keterangan (Optional)
                    $barcode = $row[9] ?? ''; // Kode Barcode (Optional)

                    // Generate kode material
                    $materialCode = Material::generateMaterialCode($category);

                    // Buat material baru
                    $material = Material::create([
                        'code' => $materialCode,
                        'category' => $category,
                        'brand' => $brand,
                        'seq' => $no,
                        'class_code' => $type,
                        'specs' => ['color_code' => $colorCode, 'color_name' => $colorName],
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
                    $no = $row[0] ?? '';
                    $colorCode = $row[1] ?? '';
                    $colorName = $row[2] ?? '';
                    $uom = $row[3] ?? '';
                    $sellingPrice = convertFormattedNumber($row[4]);
                    $stock = convertFormattedNumber($row[5] ?? null);
                    $materialCode = $row[6] ?? '';
                    $barcode = $row[7] ?? '';
                    $materialName = $row[8] ?? '';
                    $nonActive = $row[9] === 'Yes' ? now() : null;
                    $remarks = $row[10] ?? '';
                    $version = $row[11] ?? '';

                    // Cari material berdasarkan kode
                    $material = Material::where('code', $materialCode)->first();

                    if ($material) {
                        // Perbarui data material
                        $material->update([
                            'specs' => ['color_code' => $colorCode, 'color_name' => $colorName],
                            'selling_price' => $sellingPrice,
                            'seq' => $no,
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
                $audit->updateAuditTrail(intval(50 + ($rowIndex / count($dataTable)) * 50), "Processed row $rowIndex.", Status::IN_PROGRESS);
            }

            DB::commit();

            // Perbarui data pada konfigurasi template
            $templateConfig['data'] = array_slice($dataTable, 1);

            // Unggah hasil proses ke attachment
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

            // Audit selesai
            $audit->updateAuditTrail(100, 'Upload and processing completed successfully.', Status::SUCCESS);
        } catch (\Exception $e) {
            DB::rollback();
            $audit->updateAuditTrail(100, 'Processing failed: ' . $e->getMessage(), Status::ERROR);

            // Perbarui data pada konfigurasi template
            $templateConfig['data'] = array_slice($dataTable, 1);

            // Unggah hasil proses ke attachment
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

            throw $e;
        }
    }

    #region Relations
    public function MatlUom()
    {
        return $this->hasMany(MatlUom::class, 'matl_id');
    }

    public function IvtBal()
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
        return self::whereHas('IvtBal', function ($query) {
            $query->where('qty_oh', '>', 0);
        })
            ->whereNull('materials.deleted_at')
            ->distinct();
    }

    public function hasQuantity()
    {
        return IvtBal::where('matl_id', $this->id)->where('qty_oh', '>', 0)->exists();
    }

    public function getStockAttribute()
    {
        return $this->IvtBal ? $this->IvtBal->qty_oh : 0;
    }

    /**
     * Get the concatenated tag attribute.
     */
    public function getTagAttribute()
    {
        // Extract color_code and color_name from specs
        $specs = $this->specs ?? [];
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
                    $this->class_code, // Tipe
                    $colorCode, // Color Code
                    $colorName, // Color Name
                ]),
            ),
        );
    }
}
