<?php

namespace App\Models\TrdRetail1\Master;

use App\Helpers\SequenceUtility;
use App\Models\TrdRetail1\Base\TrdRetail1BaseModel;
use App\Models\Base\BaseModel\Attachment;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Transaction\OrderDtl;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdRetail1\Config\ConfigAudit;

class Material extends TrdRetail1BaseModel
{
    protected $table = 'materials';
    use SoftDeletes;

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
        'partner_id',
        'partner_code',
        'taxable',
        'info',
        'status_code',
        'created_by',
        'updated_by',
        'remarks'
    ];


    public static function validateExcelUpload($dataTable, ConfigAudit $audit)
    {
        $errors = [];
        $minRequiredColumns = 9;
        $maxAllowedColumns = 10;

        foreach ($dataTable as $index => $row) {
            // Add header for error messages
            if ($index === 0) {
                $dataTable[$index][] = "Error Message";
                continue;
            }

            // Check if the row has between 9 and 10 columns
            if (count($row) < $minRequiredColumns || count($row) > $maxAllowedColumns) {
                $errors[] = "Row $index: Invalid number of columns. Each row must have 9 or 10 columns.";
                continue;
            }

            // Validate 'Kode Barang' (Column 1)
            // if (empty($row[0])) {
            //     $errors[] = "Row $index: 'Kode Barang' (Column 1) cannot be empty.";
            // }

            // Validate 'Category' (Column 2)
            if (empty($row[2])) {
                $errors[] = "Row $index: 'Category' (Column 2) cannot be empty.";
            }

            // Validate 'Brand' (Column 3)
            if (empty($row[3])) {
                $errors[] = "Row $index: 'Brand' (Column 3) cannot be empty.";
            }
            // Validate 'Selling Price' (Column 7)
            if (empty($row[7]) || !is_numeric($row[7])) {
                $sellingPrice = str_replace(',', '', $row[7]);
                if (!is_numeric($sellingPrice)) {
                    $errors[] = "Row $index: 'Selling Price' (Column 8) must be a numeric value.";
                }
            }

            // Optionally validate 'Remarks' (Column 8)
            // Add this line if there are any conditions for remarks validation

            // Update audit progress
            $audit->updateAuditTrail(intval(($index / count($dataTable)) * 50), "Validated row $index.");
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'dataTable' => $dataTable
        ];
    }

    public static function processExcelUpload($dataTable, ConfigAudit $audit)
    {
        DB::beginTransaction();
        $errorMessages = [];

        try {
            foreach ($dataTable as $rowIndex => $row) {
                // Skip header row and rows with existing error messages
                if ($rowIndex === 0 || !empty($row[count($row) - 1])) {
                    continue;
                }

                // Map columns to variables based on specifications
                $kodeBarang = $row[0];
                $category = $row[2];
                $brand = $row[3];
                // Skipping columns 4, 5, 6 as per requirements
                $sellingPrice = $row[7];
                $remarks = $row[7] ?? null;

                // Generate 'Kode Barang' if empty
                if (empty($kodeBarang)) {
                    $lastId = self::max('id') + 1;
                    $kodeBarang = 'MAT-' . str_pad($lastId, 6, '0', STR_PAD_LEFT);
                }

                // Insert material data into the database
                self::create([
                    'code' => $kodeBarang,
                    'name' => $category,
                    'brand' => $brand,
                    'selling_price' => $sellingPrice,
                    'remarks' => $remarks,
                ]);

                // Update audit trail to track processing progress
                $audit->updateAuditTrail(intval(50 + (($rowIndex / count($dataTable)) * 50)), "Processed row $rowIndex.");
            }

            DB::commit();
            $audit->updateAuditTrail(100, 'Upload and processing completed successfully.');

            return ['success' => true, 'message' => 'Data successfully processed and uploaded.'];
        } catch (Exception $e) {
            DB::rollback();
            $audit->updateAuditTrail(100, "Processing failed: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error processing file: ' . $e->getMessage()]];
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
            'qty_oh' => '$0.00'
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

    public static function checkMaterialStockByMatlId($matlId)
    {
        return self::query()
            ->join('matl_uoms', 'materials.id', '=', 'matl_uoms.matl_id')
            ->join('ivt_bals', 'materials.id', '=', 'ivt_bals.matl_id')
            ->where('materials.id', $matlId)
            ->where('ivt_bals.qty_oh', '>', 0)
            ->select('materials.*')
            ->first();
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

    public static function getListMaterialByBarcode($barcode)
    {
        return self::query()
            ->join('matl_uoms', 'materials.id', '=', 'matl_uoms.matl_id')
            ->leftJoin('ivt_bals', 'materials.id', '=', 'ivt_bals.matl_id')
            ->where('matl_uoms.barcode', $barcode)
            ->select('materials.*', DB::raw('COALESCE(CAST(ivt_bals.qty_oh AS numeric), 0) as qty_oh'))
            ->first();
    }

    public function isItemExistonOrder(int $matl_id): bool
    {
        return OrderDtl::where('matl_id', $matl_id)->exists();
    }

    public function isItemExistonAnotherPO(int $matl_id): bool
    {
        return OrderDtl::where('matl_id', $matl_id)
            ->where('tr_type', 'PO')
            ->exists();
    }

    public static function calculateSellingPrice($buyingPrice, $markup)
    {
        if (empty($buyingPrice)) {
            return null;
        }

        $buyingPrice = toNumberFormatter($buyingPrice);

        if (empty($markup) || toNumberFormatter($markup) == 0) {
            return numberFormat($buyingPrice);
        }

        $markupAmount = $buyingPrice * (toNumberFormatter($markup) / 100);
        return numberFormat($buyingPrice + $markupAmount);
    }

    public static function calculateMarkup($buyingPrice, $sellingPrice)
    {
        if (empty($buyingPrice) || empty($sellingPrice)) {
            return null;
        }

        $buyingPrice = toNumberFormatter($buyingPrice);
        $sellingPrice = toNumberFormatter($sellingPrice);

        if ($buyingPrice <= 0) {
            return null;
        }

        if ($buyingPrice == $sellingPrice) {
            return numberFormat(0);
        }

        $newMarkupPercentage = (($sellingPrice - $buyingPrice) / $buyingPrice) * 100;
        return numberFormat($newMarkupPercentage);
    }
}
