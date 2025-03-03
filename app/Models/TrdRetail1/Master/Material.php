<?php

namespace App\Models\TrdRetail1\Master;

use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\Base\Attachment;
use App\Models\TrdRetail1\Inventories\{IvtBal, IvttrHdr, IvttrDtl, IvtBalUnit};
use App\Models\TrdRetail1\Transaction\OrderDtl;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdRetail1\Config\ConfigAudit;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use App\Models\SysConfig1\{ConfigSnum, ConfigConst};

class Material extends BaseModel
{
    protected $table = 'materials';
    use SoftDeletes;
    const FILENAME_PREFIX = 'Material';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = ['code', 'seq', 'name', 'descr', 'type_code', 'class_code', 'category', 'remarks', 'brand', 'dimension', 'wgt', 'qty_min', 'specs', 'taxable', 'uom', 'remarks', 'tag'];

    /**
     * Get configuration for Create Template.
     */
    public static function getCreateTemplateConfig(array $data = []): array
    {
        return [
            'name' => 'Material_Create_Template',
            'headers' => ['Kategori*', 'Merk*', 'Jenis*', 'No', 'Kode Warna', 'Nama Warna', 'UOM*', 'Harga Jual*', 'Keterangan', 'Kode Barcode', 'Stock', 'Status', 'Message'],
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

        $expectedHeaders = $templateConfig['headers'];

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

            $validUOMs = ConfigConst::where('const_group', 'MMATL_UOM')->pluck('str1')->toArray();
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
                $stock = $row[10] ?? null; // Stock (index 10 berdasarkan header baru)
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
                } elseif (!in_array($uom, $validUOMs)) {
                    $message .= 'UOM tidak ditemukan. ';
                }
                if (!isValidNumeric($sellingPrice)) {
                    $message .= 'Harga jual harus berupa angka positif. ';
                }
                if (!empty($stock) && (!is_numeric($stock) || $stock < 0)) {
                    $message .= 'Stock harus berupa angka non-negatif. ';
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
                $uom = $row[3] ?? '';

                if (empty($no)) {
                    $message .= 'Kolom No* tidak boleh kosong. ';
                }
                if (empty($materialCode)) {
                    $message .= 'Kode Barang tidak boleh kosong. ';
                }
                if (empty($version)) {
                    $message .= 'Version tidak boleh kosong. ';
                }
                if (!empty($uom) && !in_array($uom, $validUOMs)) {
                    $message .= 'UOM tidak ditemukan. ';
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
        $statusIndex = array_search('Status', $dataTable[0]);
        $messageIndex = array_search('Message', $dataTable[0]);
        $templateConfig = $param === 'Create' ? self::getCreateTemplateConfig() : self::getUpdateTemplateConfig();

        DB::beginTransaction();

        try {
            // Array untuk menampung detail yang perlu diinsert ke IvttrDtl
            $ivtDetails = [];

            foreach ($dataTable as $rowIndex => $row) {
                if ($rowIndex === 0 || ($row[$statusIndex] ?? '') === 'Error') {
                    continue;
                }

                $status  = 'Success';
                $message = '';

                if ($param === 'Create') {
                    // Ambil data dari row
                    $category     = $row[0] ?? '';
                    $brand        = $row[1] ?? '';
                    $type         = $row[2] ?? '';
                    $no           = $row[3] ?? '';
                    $colorCode    = $row[4] ?? '';
                    $colorName    = $row[5] ?? '';
                    $uom          = $row[6] ?? '';
                    $sellingPrice = convertFormattedNumber($row[7]);
                    $remarks      = $row[8] ?? '';
                    $barcode      = $row[9] ?? '';
                    $stock        = !empty($row[10]) ? convertFormattedNumber($row[10]) : 0;

                    // Buat kode material & nama
                    $materialCode = Material::generateMaterialCode($category);
                    $name         = Material::generateName($category, $brand, $type, $colorCode);

                    // Cek UOM validasi dari ConfigConst
                    $validUOMs = ConfigConst::where('const_group', 'MMATL_UOM')->pluck('str1')->toArray();
                    if (!in_array($uom, $validUOMs)) {
                        $message .= 'UOM tidak ditemukan. ';
                    }

                    // Cek apakah material sudah ada di database
                    $existingMaterial = Material::where('category', $category)
                        ->where('brand', $brand)
                        ->where('class_code', $type)
                        ->whereJsonContains('specs->color_code', $colorCode)
                        ->first();

                    if ($existingMaterial) {
                        $message .= 'Material sudah ada di database. ';
                    }

                    if (!empty($message)) {
                        $status = 'Error';
                    } else {
                        // Buat Material
                        $material = Material::create([
                            'code'       => $materialCode,
                            'name'       => $name,
                            'category'   => $category,
                            'brand'      => $brand,
                            'seq'        => $no,
                            'class_code' => $type,
                            'type_code'  => 'P',
                            'specs'      => [
                                'color_code' => $colorCode,
                                'color_name' => $colorName
                            ],
                            'remarks' => $remarks,
                            'uom'     => $uom,
                        ]);

                        // Generate tag
                        $tag = Material::generateTag(
                            $material->code,
                            $material->MatlUom,
                            $brand,
                            $type,
                            $material->specs
                        );
                        $material->update(['tag' => $tag]);

                        // Buat MatlUom
                        $matlUom = $material->MatlUom()->create([
                            'matl_uom'     => $uom,
                            'barcode'      => $barcode,
                            'reff_uom'     => $uom,
                            'reff_factor'  => 1,
                            'base_factor'  => 1,
                            'selling_price'=> $sellingPrice,
                            'qty_oh'=> $stock,
                        ]);

                        // Jika stock diisi > 0, siapkan data untuk inventory
                        if ($stock > 0) {
                            // Buat atau update IvtBal
                            IvtBal::create([
                                'matl_id'   => $material->id,
                                'matl_uom'  => $uom,
                                'wh_id'     => 1,
                                'wh_code'   => IvtBal::$defaultWhCode,
                                'batch_code'=> date('y/m/d'),
                                'qty_oh'    => $stock,
                            ]);

                            // Kumpulkan data detail untuk IvttrDtl
                            $ivtDetails[] = [
                                'matl_id'   => $material->id,
                                'matl_code' => $materialCode,
                                'matl_uom'  => $uom,
                                'qty'       => $stock
                            ];
                        }
                    }
                } elseif ($param === 'Update') {
                    // Ambil data dari row
                    $no           = $row[0] ?? '';
                    $colorCode    = $row[1] ?? '';
                    $colorName    = $row[2] ?? '';
                    $uom          = $row[3] ?? '';
                    $sellingPrice = convertFormattedNumber($row[4]);
                    $stock        = convertFormattedNumber($row[5] ?? null);
                    $materialCode = $row[6] ?? '';
                    $barcode      = $row[7] ?? '';
                    $materialName = $row[8] ?? '';
                    $nonActive    = ($row[9] === 'Yes') ? now() : null;
                    $remarks      = $row[10] ?? '';
                    $version      = $row[11] ?? '';

                    // Cek apakah material ada di database
                    $material = Material::where('code', $materialCode)->first();

                    if ($material) {
                        // Update Material
                        $material->update([
                            'specs'         => [
                                'color_code' => $colorCode,
                                'color_name' => $colorName
                            ],
                            'selling_price' => $sellingPrice,
                            'seq'           => $no,
                            'name'          => $materialName,
                            'deleted_at'    => $nonActive,
                            'remarks'       => $remarks,
                            'version_number'=> $version++,
                            'uom'           => $uom,
                        ]);

                        // Update Tag
                        $tag = Material::generateTag(
                            $material->code,
                            $material->MatlUom,
                            $material->brand,
                            $material->class_code,
                            $material->specs
                        );
                        $material->update(['tag' => $tag]);

                        // Jika stock diisi > 0, siapkan data untuk inventory
                        if ($stock > 0) {
                            // Update atau buat IvtBal
                            IvtBal::updateOrCreate(
                                [
                                    'matl_id'  => $material->id,
                                    'matl_uom' => $uom,
                                    'wh_code'  => IvtBal::$defaultWhCode,
                                ],
                                [
                                    'batch_code'=> date('y/m/d'),
                                    'qty_oh'    => $stock,
                                ]
                            );

                            // Update atau buat MatlUom
                            $material->MatlUom()->updateOrCreate(
                                ['matl_uom' => $uom],
                                [
                                    'barcode'      => $barcode,
                                    'reff_uom'     => $uom,
                                    'reff_factor'  => 1,
                                    'base_factor'  => 1,
                                    'selling_price'=> $sellingPrice,
                                    'qty_oh'=> $stock,
                                ]
                            );

                            // Kumpulkan data detail untuk IvttrDtl
                            $ivtDetails[] = [
                                'matl_id'   => $material->id,
                                'matl_code' => $materialCode,
                                'matl_uom'  => $uom,
                                'qty'       => $stock
                            ];
                        }
                    } else {
                        $status  = 'Error';
                        $message = "Material dengan kode $materialCode tidak ditemukan.";
                    }
                }

                $dataTable[$rowIndex][$statusIndex]  = $status;
                $dataTable[$rowIndex][$messageIndex] = $message;

                // Update progress audit
                $audit->updateAuditTrail(
                    intval(50 + ($rowIndex / count($dataTable)) * 50),
                    "Processed row $rowIndex.",
                    Status::IN_PROGRESS
                );
            }

            /**
             * Setelah loop selesai, buat IvttrHdr hanya sekali
             * jika memang ada row yang memiliki stok > 0
             */
            if (!empty($ivtDetails)) {
                $maxTrId = IvttrHdr::max('tr_id');
                $nextTrId = $maxTrId ? $maxTrId + 1 : 1;

                // Buat Transaction Header (IvttrHdr) satu kali
                $ivtHdr = IvttrHdr::create([
                    'tr_id'   => $nextTrId,
                    'tr_type' => 'IA',
                    'tr_date' => now(),
                    'remark'  => 'Initial stock adjustment from Excel upload',
                ]);

                // Buat Transaction Detail (IvttrDtl) untuk setiap material
                $seq = 1;
                foreach ($ivtDetails as $detail) {
                    IvttrDtl::create([
                        'trhdr_id'   => $ivtHdr->id,
                        'tr_type'    => 'IA',
                        'tr_id'      => $ivtHdr->id, // atau pakai $nextTrId jika ingin konsisten
                        'tr_seq'     => $seq++,
                        'matl_id'    => $detail['matl_id'],
                        'matl_code'  => $detail['matl_code'],
                        'matl_uom'   => $detail['matl_uom'],
                        'wh_code'    => IvtBal::$defaultWhCode,
                        'batch_code' => date('y/m/d'),
                        'qty'        => $detail['qty'],
                        'tr_descr'   => "Initial stock for {$detail['matl_code']}",
                    ]);
                }
            }

            // Perbarui data pada konfigurasi template
            $templateConfig['data'] = array_slice($dataTable, 1);
            // Unggah hasil proses ke attachment
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

            // Audit selesai
            $audit->updateAuditTrail(100, 'Upload and processing completed successfully.', Status::SUCCESS);

            DB::commit();
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
    public function DefaultUom()
    {
        return $this->hasOne(MatlUom::class, 'matl_id', 'id')->where('matl_uom', $this->uom);
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

    public static function generateTag($code, $matlUoms, $brand, $classCode, $specs)
    {
        $barcode = optional($matlUoms->first())->barcode ?? '';

        return trim(implode(' ', array_filter([$code, $barcode, $brand, $classCode, $specs['color_code'] ?? ''])));
    }
    public static function generateName($category, $brand, $type, $colorCode)
    {
        return $category . ' ' . $brand . ' ' . $type . ' (' . $colorCode . ')';
    }
}
