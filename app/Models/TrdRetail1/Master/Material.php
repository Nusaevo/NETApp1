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
    public static function validateExcelUpload($dataTable, $audit, $param)
    {
        $errors = [];
        // Ambil config template (Create / Update)
        $templateConfig = $param === 'Create'
            ? self::getCreateTemplateConfig()
            : self::getUpdateTemplateConfig();

        $expectedHeaders = $templateConfig['headers'];
        $actualHeaders   = $dataTable[0] ?? [];

        // 1. VALIDASI HEADER
        $missing = array_diff($expectedHeaders, $actualHeaders);
        if (!empty($missing)) {
            $audit->updateAuditTrail(
                100,
                'Template salah: kolom header berikut tidak ditemukan — ' . implode(', ', $missing),
                Status::ERROR
            );
            $templateConfig['headers'] = $actualHeaders;
            $templateConfig['data']    = array_slice($dataTable, 1);
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');
            return [
                'success'   => false,
                'dataTable' => $dataTable,
            ];
        }

        // Pastikan kolom Status & Message ada
        $statusIndex  = array_search('Status',  $actualHeaders);
        $messageIndex = array_search('Message', $actualHeaders);

        // 2. CEK DATA KOSONG TOTAL (hanya header atau baris pertama blank)
        if (count($dataTable) === 1 || empty(array_filter($dataTable[1] ?? []))) {
            $audit->updateAuditTrail(100, 'Error: Data tidak ditemukan.', Status::ERROR);
            $templateConfig['headers'] = $actualHeaders;
            $templateConfig['data']    = array_slice($dataTable, 1);
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');
            return [
                'success'   => false,
                'dataTable' => $dataTable,
            ];
        }

        // Siapkan index kolom untuk akses cepat
        $headerIndex = array_flip($actualHeaders);

        // 3. BATCH GENERATE NAMA & DETEKSI DUPLIKAT (dengan pelacakan baris error)
        $masterService  = new MasterService();
        $generatedNames = [];

        foreach (array_slice($dataTable, 1) as $idx => $row) {
            // Hitung nomor baris Excel (header = 1, data pertama = 2)
            $rowNum = $idx + 2;
            $cat       = $row[$headerIndex['Kategori*']]  ?? null;
            $brand     = $row[$headerIndex['Merk']]       ?? '';
            $type      = $row[$headerIndex['Jenis']]      ?? '';
            $colorCode = $row[$headerIndex['Kode Warna']] ?? '';
            $colorName = $row[$headerIndex['Nama Warna']] ?? '';

            try {
                $catDetail = $masterService->getMatlCategoryDetail($cat);
                if (empty($catDetail) || is_string($catDetail)) {
                    // Jika error atau tidak ditemukan, lempar exception dengan pesan yang jelas
                    $errorMsg = is_string($catDetail)
                        ? $catDetail
                        : 'Kategori tidak ditemukan';
                    throw new \Exception($errorMsg);
                }
            } catch (\Throwable $e) {
                // Catat error baris ke $errors dan set nama ter-generate null
                $errors[]       = "Row {$rowNum}: {$e->getMessage()}.";
                $generatedNames[] = null;
                continue;
            }

            // Jika valid, generate nama atau gunakan Nama Barang
            $gen = Material::generateName($cat, $brand, $type, $colorCode, $colorName);
            if ($gen !== '') {
                $generatedNames[] = $gen;
            } else {
                $generatedNames[] = strtoupper($row[$headerIndex['Nama Barang']] ?? '');
            }
        }

        // Hitung duplikat
        $validNames = array_filter($generatedNames, fn($n) => $n !== null);
        $counts     = array_count_values($validNames);
        $dupNames   = array_keys(array_filter($counts, fn($c) => $c > 1));

        // Ambil daftar UOM valid satu kali
        $validUOMs = ConfigConst::where('const_group', 'MMATL_UOM')->pluck('str1')->toArray();

        // 4. LOOP VALIDASI TIAP BARIS
        foreach ($dataTable as $i => $row) {
            // Skip header atau baris kosong
            if ($i === 0 || empty(array_filter($row, fn($c) => trim((string)$c) !== ''))) {
                continue;
            }

            $message  = '';
            $status   = '';
            $cat      = $row[$headerIndex['Kategori*']] ?? '';
            $name     = $generatedNames[$i - 1] ?? null;
            $catDetail = $masterService->getMatlCategoryDetail($cat);

            // Tangani error kategori jika string
            if (is_string($catDetail)) {
                $message .= "Error kategori: {$catDetail}. ";
                $catDetail = null;
            }

            // a) Kategori ada?
            if (empty($catDetail)) {
                $message .= 'Kategori tidak ditemukan. ';
            }

            // b) Duplikat nama?
            if ($name !== null && in_array($name, $dupNames)) {
                $message .= "Duplikasi nama barang: {$name}. ";
            }

            if ($param === 'Create') {
                // Validasi fields Create
                $no           = $row[$headerIndex['No']]           ?? null;
                $uom          = $row[$headerIndex['UOM*']]         ?? null;
                $sellingPrice = $row[$headerIndex['Harga Jual*']]  ?? null;
                $stock        = $row[$headerIndex['Stock']]        ?? null;

                if (empty($no)) {
                    $message .= 'Kolom No* tidak boleh kosong. ';
                }
                if (empty($cat)) {
                    $message .= 'Kategori* tidak boleh kosong. ';
                }
                if (empty($uom)) {
                    $message .= 'UOM tidak boleh kosong. ';
                } elseif (!in_array($uom, $validUOMs)) {
                    $message .= 'UOM tidak ditemukan. ';
                }
                if (!is_numeric($sellingPrice) || $sellingPrice <= 0) {
                    $message .= 'Harga jual harus angka positif. ';
                }
                if (empty($sellingPrice)) {
                    $message .= 'Harga jual* tidak boleh kosong. ';
                }
                if (!empty($stock) && (!is_numeric($stock) || $stock < 0)) {
                    $message .= 'Stock harus berupa angka non-negatif. ';
                }

                // Cek duplikasi di DB
                if ($name !== null) {
                    $exists = Material::where('category', $cat)
                                      ->where('name', $name)
                                      ->first();
                    if ($exists) {
                        $message .= 'Material dengan Kategori dan Nama barang sama sudah ada di database. ';
                    }
                }
            } else {
                // Validasi fields Update
                $no           = $row[$headerIndex['No']]           ?? null;
                $materialCode = $row[$headerIndex['Kode Barang']]  ?? null;
                $version      = $row[$headerIndex['Version']]      ?? null;
                $uom          = $row[$headerIndex['UOM*']]         ?? null;

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
                $status   = 'Error';
                $errors[] = "Row {$i}: {$message}";
            }

            $dataTable[$i][$statusIndex]  = $status;
            $dataTable[$i][$messageIndex] = trim($message);
        }

        // 5. UPLOAD ATTACHMENT & UPDATE AUDIT TRAIL
        if (!empty($errors)) {
            $templateConfig['headers'] = $actualHeaders;
            $templateConfig['data']    = array_slice($dataTable, 1);
            Attachment::uploadExcelAttachment($templateConfig, $audit->id, 'ConfigAudit');
        }

        $audit->updateAuditTrail(
            100,
            empty($errors)
                ? 'Validasi selesai tanpa kesalahan.'
                : 'Validasi selesai dengan kesalahan.',
            empty($errors) ? Status::SUCCESS : Status::ERROR
        );

        return [
            'success'   => empty($errors),
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
