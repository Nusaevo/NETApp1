<?php

namespace App\Models\TrdTire1\Master;

use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Inventories\IvtBalUnit;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Models\TrdTire1\Transaction\DelivDtl;

class MatlUom extends BaseModel
{
    protected $table = 'matl_uoms';
    use SoftDeletes;

    protected $fillable = [
        'matl_uom',
        'matl_id',
        'matl_code',
        'reff_uom',
        'reff_factor',
        'base_factor',
        'price_grp',
        'barcode',
        'qty_oh',
        'qty_fgr',
        'qty_fgi',
        'selling_price',
        'last_buying_price',
        'last_buying_date'
        // 'initial_qty_fgr' // Pastikan field ini ada jika diperlukan
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Recalculate qty_oh berdasarkan jumlah semua record IvtBal untuk kombinasi matl_id dan matl_uom
     */
    public static function recalcMatlUomQtyOh($matlId, $matlUom)
    {
        $matlUomRec = self::where([
            'matl_id'  => $matlId,
            'matl_uom' => $matlUom,
        ])->first();

        if ($matlUomRec) {
            $sumOh = IvtBal::where('matl_id', $matlId)
                ->where('matl_uom', $matlUom)
                ->get()
                ->pluck('qty_oh')
                ->reduce(function ($carry, $item) {
                    return $carry + $item;
                }, 0);
            $matlUomRec->qty_oh = $sumOh;
            $matlUomRec->save();
        }
    }

    /**
     * Recalculate qty_fgr berdasarkan stok awal (initial_qty_fgr) dikurangi total transaksi PD aktif.
     * Asumsi: Nilai awal stok disimpan pada field 'initial_qty_fgr' atau dapat disesuaikan (di sini default 40).
     * Hanya menghitung transaksi PD yang belum dihapus (soft delete).
     */
    public static function recalcMatlUomQtyFgr($matlId, $matlUom)
    {
        $matlUomRec = self::where([
            'matl_id'  => $matlId,
            'matl_uom' => $matlUom,
        ])->first();

        if ($matlUomRec) {
            // Use the initial_qty_fgr if available, otherwise default to the current qty_fgr
            $initialQtyFgr = isset($matlUomRec->initial_qty_fgr) ? $matlUomRec->initial_qty_fgr : $matlUomRec->qty_fgr;

            // Calculate the total quantity of active PD transactions
            $totalPD = DelivDtl::where('matl_id', $matlId)
                ->where('matl_uom', $matlUom)
                ->where('tr_type', 'PD')
                ->whereNull('deleted_at')
                ->sum('qty');

            // Ensure qty_fgr is recalculated correctly
            $newQtyFgr = $initialQtyFgr - $totalPD;

            // Prevent negative values for qty_fgr
            $matlUomRec->qty_fgr = max($newQtyFgr, 0);
            $matlUomRec->save();
        }
    }

    #region Relations
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function ivtBal()
    {
        return $this->hasMany(IvtBal::class, 'matl_id');
    }

    public function ivtBalUnit()
    {
        return $this->hasMany(IvtBalUnit::class, 'matl_id');
    }
    #endregion

    public function scopeFindMaterialId($query, $matl_id)
    {
        return $query->where('matl_id', $matl_id);
    }
}
