<?php

namespace App\Models\TrdRetail1\Master;

use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\Base\Attachment;
use App\Models\TrdRetail1\Inventories\{IvtBal, IvttrHdr, IvttrDtl, IvtBalUnit, IvtLog};
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
        $saving = function (Material $m) {
            //— handle specs & uppercase attrs seperti sebelumnya —
            $specs = $m->specs ?: [];
            if (is_string($specs)) {
                $specs = json_decode($specs, true);
            }
            if (isset($specs['color_code'])) {
                $specs['color_code'] = strtoupper(str_replace(' ', '', $specs['color_code']));
            }
            if (isset($specs['color_name'])) {
                $specs['color_name'] = strtoupper($specs['color_name']);
            }
            if (isset($specs['size'])) {
                $specs['size'] = strtoupper($specs['size']);
            }
            foreach (['brand', 'class_code', 'name'] as $attr) {
                if ($m->{$attr}) {
                    $m->{$attr} = strtoupper($m->{$attr});
                }
            }
            $m->specs = json_encode($specs);

            //— baru: restore UOM kalau material di-activate ulang —
            $original = $m->getOriginal('status_code');
            $current  = $m->status_code;
            if ($original === Status::NONACTIVE && $current === Status::ACTIVE) {
                $m->MatlUom()->withTrashed()->restore();
                $m->MatlUom()->update(['status_code' => Status::ACTIVE]);
            }
        };

        static::creating($saving);
        static::updating($saving);
        static::saving($saving);

        static::deleting(function(Material $m) {
            $m->MatlUom()->update(['status_code' => Status::NONACTIVE]);
            $m->MatlUom()->delete();
        });
    }

    protected $fillable = ['code', 'seq', 'name', 'descr', 'type_code', 'class_code', 'category', 'remarks', 'brand', 'dimension', 'wgt', 'qty_min', 'specs', 'taxable', 'uom', 'remarks', 'tag'];

    /**
     * Get configuration for Create Template.
     */
    public static function getCreateTemplateConfig(array $data = []): array
    {
        return [
            'name' => 'Material_Create_Template',
            'headers' => ['Kategori*', 'Merk', 'Jenis', 'UOM*', 'No', 'Kode Warna', 'Nama Warna', 'Ukuran', 'Nama Barang', 'Harga Beli', 'Harga Jual*', 'Stock', 'Keterangan', 'Kode Barcode', 'Status', 'Message'],
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

    public static function getExcelTemplateConfig(array $data = []): array
    {
        return [
            'name' => 'Material_Update_Template',
            'headers' => ['Kode Warna', 'Nama Warna', 'Harga Jual', 'STOK', 'Kode Barang', 'Kode Barcode', 'Nama Barang'],
            'data' => $data,
            'protectedColumns' => [],
            'allowInsert' => true,
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
        $missing = array_diff($expectedHeaders, $actualHeaders);

        if (! empty($missing)) {
            // Audit and upload the entire file for review
            $audit->updateAuditTrail(
                100,
                'Template salah: kolom header berikut tidak ditemukan — ' . implode(', ', $missing),
                Status::ERROR
            );
            $templateConfig['headers'] = $dataTable[0] ?? [];
            $templateConfig['data']    = array_slice($dataTable, 1);

            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

            return [
                'success'   => false,
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
                    $templateConfig['headers'] = $dataTable[0] ?? [];
                    $templateConfig['data']    = array_slice($dataTable, 1);
                    Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

                    return [
                        'success' => false,
                        'dataTable' => $dataTable,
                    ];
                }
                continue;
            }

            $allBlank = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    $allBlank = false;
                    break;
                }
            }
            if ($allBlank) {
                continue;  // skip this row entirely
            }

            $status = '';
            $message = '';

            // Common validation for `No*`
            $no = $row[0] ?? null;
            if (empty($no)) {
                $status = 'Error';
                $message .= 'Kolom No* tidak boleh kosong. ';
            }

            $headerIndex = array_flip($actualHeaders);
            $validUOMs = ConfigConst::where('const_group', 'MMATL_UOM')->pluck('str1')->toArray();
            if ($param === 'Create') {
                // Validation for Create template
                $category    = $row[$headerIndex['Kategori*']]   ?? null; // Kategori*
                $brand       = $row[$headerIndex['Merk']]       ?? null; // Merk*
                $type        = $row[$headerIndex['Jenis']]      ?? null; // Jenis*
                $no          = $row[$headerIndex['No']]          ?? null; // No
                $colorCode   = $row[$headerIndex['Kode Warna']]  ?? null; // Kode Warna
                $colorName   = $row[$headerIndex['Nama Warna']]  ?? null; // Nama Warna
                $uom         = $row[$headerIndex['UOM*']]        ?? null; // UOM*
                $buyingPrice = $row[$headerIndex['Harga Beli']] ?? null; // Harga Beli
                $sellingPrice= $row[$headerIndex['Harga Jual*']] ?? null; // Harga Jual*
                $stock       = $row[$headerIndex['Stock']]       ?? null; // Stock
                $materialName     = $row[$headerIndex['Nama Barang']]       ?? '';
                $generated = Material::generateName($category, $brand, $type, $colorCode, $colorName);
                $name='';
                if ($generated !== '') {
                    $name = $generated;
                }else{
                    $name = strtoupper($materialName);
                }
                // if (empty($no)) {
                //     $message .= 'Kolom No* tidak boleh kosong. ';
                // }
                if (empty($category)) {
                    $message .= 'Kategori* tidak boleh kosong. ';
                }
                // if (empty($brand)) {
                //     $message .= 'Merk* tidak boleh kosong. ';
                // }
                // if (empty($type)) {
                //     $message .= 'Jenis* tidak boleh kosong. ';
                // }

                if (empty($uom)) {
                    $message .= 'UOM tidak boleh kosong. ';
                } elseif (!in_array($uom, $validUOMs)) {
                    $message .= 'UOM tidak ditemukan. ';
                }
                if (!isValidNumeric($sellingPrice)) {
                    $message .= 'Harga jual harus berupa angka positif. ';
                }
                if (empty($sellingPrice)) {
                    $message .= 'Harga jual* tidak boleh kosong. ';
                }
                if (!empty($stock) && (!is_numeric($stock) || $stock < 0)) {
                    $message .= 'Stock harus berupa angka non-negatif. ';
                }
                // Cek duplikasi dalam database berdasarkan Kategori, Merk, Jenis, dan Color Code di JSONB (specs->color_code)
                $existingMaterial = Material::where('category', $category)->where('name', $name)->first();

                if ($existingMaterial) {
                    $message .= 'Material dengan Kategori dan Nama barang sama sudah ada di file database. ';
                }

                static $existingNamesInExcel = [];
                $key = strtoupper(trim($category)) . '|' . strtoupper(trim($name));

                if (isset($existingNamesInExcel[$key])) {
                    $message .= 'Material dengan Kategori dan Nama barang sama sudah ada di file Excel. ';
                }
            } elseif ($param === 'Update') {
                // Validasi Template Update
                $no           = $row[$headerIndex['No']]          ?? null;
                $materialCode = $row[$headerIndex['Kode Barang']] ?? null;
                $version      = $row[$headerIndex['Version']]     ?? null;
                $uom          = $row[$headerIndex['UOM*']]        ?? null;

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

            $templateConfig['headers'] = $dataTable[0] ?? [];
            $templateConfig['data']    = array_slice($dataTable, 1);
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
        $headerIndex = array_flip($dataTable[0]);
        try {
            $ivtDetails = [];

            foreach ($dataTable as $rowIndex => $row) {
                if ($rowIndex === 0 || ($row[$statusIndex] ?? '') === 'Error') {
                    continue;
                }

                $status  = 'Success';
                $message = '';
                $allBlank = true;
                foreach ($row as $cell) {
                    if (trim((string)$cell) !== '') {
                        $allBlank = false;
                        break;
                    }
                }
                if ($allBlank) {
                    continue;  // skip this row entirely
                }

                if ($param === 'Create') {
                    $category     = $row[$headerIndex['Kategori*']]   ?? '';
                    $brand        = $row[$headerIndex['Merk']]       ?? '';
                    $type         = $row[$headerIndex['Jenis']]      ?? '';
                    $no           = $row[$headerIndex['No']]          ?? 0;
                    $colorCode    = $row[$headerIndex['Kode Warna']]  ?? '';
                    $colorName    = $row[$headerIndex['Nama Warna']]  ?? '';
                    $uom          = $row[$headerIndex['UOM*']]        ?? '';

                    $buyingPrice  = convertFormattedNumber($row[$headerIndex['Harga Beli']] ?? null);
                    $sellingPrice = convertFormattedNumber($row[$headerIndex['Harga Jual*']] ?? null);
                    $remarks      = $row[$headerIndex['Keterangan']]  ?? '';
                    $barcode      = $row[$headerIndex['Kode Barcode']]?? '';
                    $stockRaw     = $row[$headerIndex['Stock']]       ?? '';
                    $size     = $row[$headerIndex['Ukuran']]       ?? '';
                    $materialName     = $row[$headerIndex['Nama Barang']]       ?? '';
                    $stock        = !empty($stockRaw) ? convertFormattedNumber($stockRaw) : 0;

                    $materialCode = Material::generateMaterialCode($category);
                    $generated = Material::generateName($category, $brand, $type, $colorCode, $colorName);
                    if ($generated !== '') {
                        $name = $generated;
                    }else{
                        $name = strtoupper($materialName);
                    }

                    $validUOMs = ConfigConst::where('const_group', 'MMATL_UOM')->pluck('str1')->toArray();
                    if (!in_array($uom, $validUOMs)) {
                        $message .= 'UOM tidak ditemukan. ';
                    }

                    if (!empty($message)) {
                        $status = 'Error';
                    } else {
                        $material = Material::create([
                            'code'       => $materialCode,
                            'name'       => $name,
                            'category'   => $category,
                            'brand'      => $brand,
                            'seq'        => $no,
                            'class_code' => $type,
                            'type_code'  => '',
                            'specs'      => [
                                'color_code' => $colorCode,
                                'color_name' => $colorName,
                                'size' =>   $size,
                            ],
                            'remarks' => $remarks,
                            'uom'     => $uom,
                        ]);

                        $tag = Material::generateTag($material->name, $material->code, $material->MatlUom, $brand, $type, $material->specs);
                        $material->update(['tag' => $tag]);

                        $material->MatlUom()->create([
                            'matl_uom'     => $uom,
                            'matl_code'    => $materialCode,
                            'barcode'      => $barcode,
                            'reff_uom'     => $uom,
                            'reff_factor'  => 1,
                            'base_factor'  => 1,
                            'buying_price' => $buyingPrice,
                            'selling_price'=> $sellingPrice,
                            'qty_oh'       => $stock,
                        ]);

                            $configConst = ConfigConst::where('const_group', 'MWAREHOUSE_LOCL1')
                                ->where('str1', IvtBal::$defaultWhCode ?? '')
                                ->first();

                            $wh_id = $configConst ? $configConst->id : null;
                            IvtBal::create([
                                'matl_id'   => $material->id,
                                'matl_uom'  => $uom,
                                'matl_code' => $materialCode,
                                'wh_id'     => $wh_id,
                                'wh_code'   => IvtBal::$defaultWhCode,
                                'qty_oh'    => $stock,
                            ]);

                            $ivtDetails[] = [
                                'matl_id'   => $material->id,
                                'matl_code' => $materialCode,
                                'matl_uom'  => $uom,
                                'qty'       => $stock
                            ];
                    }
                } else if ($param === 'Update') {
                    $no           = $row[$headerIndex['No']]          ?? '';
                    $colorCode    = $row[$headerIndex['Kode Warna']]  ?? '';
                    $colorName    = $row[$headerIndex['Nama Warna']]  ?? '';
                    $uom          = $row[$headerIndex['UOM*']]        ?? '';
                    $sellingPrice = convertFormattedNumber($row[$headerIndex['Harga Jual*']] ?? null);
                    $stock        = convertFormattedNumber($row[$headerIndex['STOK']] ?? null);
                    $materialCode = $row[$headerIndex['Kode Barang']] ?? '';
                    $barcode      = $row[$headerIndex['Kode Barcode']]?? '';
                    $materialName = $row[$headerIndex['Nama Barang']] ?? '';
                    $nonActive    = ($row[$headerIndex['Non Aktif']] === 'Yes') ? now() : null;
                    $remarks      = $row[$headerIndex['Keterangan']]  ?? '';
                    $version      = $row[$headerIndex['Version']]     ?? '';

                    $material = Material::where('code', $materialCode)->first();

                    if ($material) {
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

                        $tag = Material::generateTag(
                            $material->name,
                            $material->code,
                            $material->MatlUom,
                            $material->brand,
                            $material->class_code,
                            $material->specs
                        );
                        $material->update(['tag' => $tag]);

                        if ($stock > 0) {
                            $ivtBal = IvtBal::updateOrCreate(
                                [
                                    'matl_id'   => $material->id,
                                    'matl_uom'  => $uom,
                                    'matl_code' => $material->code,
                                    'wh_code'   => IvtBal::$defaultWhCode,
                                ],
                                [
                                    'qty_oh'    => $stock,
                                ]
                            );

                            $material->MatlUom()->updateOrCreate(
                                ['matl_uom' => $uom],
                                [
                                    'barcode'      => $barcode,
                                    'reff_uom'     => $uom,
                                    'reff_factor'  => 1,
                                    'base_factor'  => 1,
                                    'selling_price'=> $sellingPrice,
                                    'qty_oh'       => $stock,
                                ]
                            );

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

                $audit->updateAuditTrail(
                    intval(50 + ($rowIndex / count($dataTable)) * 50),
                    "Processed row $rowIndex.",
                    Status::IN_PROGRESS
                );
            }

            if (!empty($ivtDetails)) {
                $maxTrId = IvttrHdr::max('tr_id');
                $nextTrId = $maxTrId ? $maxTrId + 1 : 1;

                $ivtHdr = IvttrHdr::create([
                    'tr_id'   => $nextTrId,
                    'tr_type' => 'IA',
                    'tr_date' => now(),
                    'remark'  => 'Initial stock adjustment from Excel upload',
                ]);

                $seq = 1;
                foreach ($ivtDetails as $detail) {
                    $trDtl = IvttrDtl::create([
                        'trhdr_id'   => $ivtHdr->id,
                        'tr_type'    => 'IA',
                        'tr_id'      => $ivtHdr->id,
                        'tr_seq'     => $seq++,
                        'matl_id'    => $detail['matl_id'],
                        'matl_code'  => $detail['matl_code'],
                        'matl_uom'   => $detail['matl_uom'],
                        'wh_code'    => IvtBal::$defaultWhCode,
                        'qty'        => $detail['qty'],
                        'tr_descr'   => "Initial stock for {$detail['matl_code']}",
                    ]);

                    $ivtBal = IvtBal::where([
                        'matl_id'  => $trDtl->matl_id,
                        'matl_uom' => $trDtl->matl_uom,
                        'wh_code'  => $trDtl->wh_code,
                    ])->first();

                    IvtLog::create([
                        'trhdr_id'   => $trDtl->trhdr_id,
                        'tr_type'    => $trDtl->tr_type,
                        'tr_seq'     => $trDtl->tr_seq,
                        'tr_id'      => $trDtl->tr_id,
                        'trdtl_id'   => $trDtl->id,
                        'ivt_id'     => $ivtBal->id ?? null,
                        'matl_id'    => $trDtl->matl_id,
                        'matl_code'  => $trDtl->matl_code,
                        'matl_uom'   => $trDtl->matl_uom,
                        'wh_id'      => $ivtBal->wh_id ?? null,
                        'wh_code'    => $trDtl->wh_code,
                        'tr_date'    => date('Y-m-d'),
                        'qty'        => $trDtl->qty,
                        'price'      => 0,
                        'amt'        => 0,
                        'tr_desc'    => $trDtl->tr_descr,
                    ]);
                }
            }

            $templateConfig['headers'] = $dataTable[0] ?? [];
            $templateConfig['data']    = array_slice($dataTable, 1);
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');

            $audit->updateAuditTrail(100, 'Upload and processing completed successfully.', Status::SUCCESS);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $audit->updateAuditTrail(100, 'Processing failed: ' . $e->getMessage(), Status::ERROR);
            $templateConfig['headers'] = $dataTable[0] ?? [];
            $templateConfig['data']    = array_slice($dataTable, 1);
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
        return $this->hasOne(IvtBal::class, 'matl_id', 'id')
            ->withDefault(['qty_oh' => '0'])
            ->where(function ($query) {
                if ($this->relationLoaded('DefaultUom') && $this->DefaultUom) {
                    $query->where('ivt_bals.matl_uom', $this->DefaultUom->matl_uom);
                }
            });
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
        return $this->DefaultUom?->qty_oh ?? 0;
    }

    public static function generateTag($name, $code, $matlUoms, $brand, $classCode, $specs)
    {
        $barcode = optional($matlUoms->first())->barcode ?? '';
        return trim(implode(' ', array_filter([$name, $code, $barcode, $brand, $classCode, $specs['color_code'] ?? '', $specs['color_name'] ?? ''])));
    }

    public static function generateName(
        $category,
        $brand,
        $type,
        $colorCode,
        $colorName
    ) {
        $masterService = new MasterService();
        $data = $masterService->getMatlCategoryDetail($category);

        // hanya generate kalau num1 == 1
        if ((int) $data->num1 === 1) {
            // Convert semua input ke UPPERCASE dulu
            $brand = strtoupper($brand);
            $type = strtoupper($type);
            $colorCode = strtoupper(str_replace(' ', '', $colorCode)); // hapus spasi juga
            $colorName = strtoupper($colorName);

            // Bangun string
            $raw = sprintf(
                'Benang %s %s %s %s',
                $brand,
                $type,
                $colorCode,
                $colorName
            );

            return $raw;
        }

        return '';
    }


}
