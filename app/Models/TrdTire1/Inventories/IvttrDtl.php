<?php

namespace App\Models\TrdTire1\Inventories;

use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class IvttrDtl extends BaseModel
{
    protected $table = 'ivttr_dtls';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'matl_id',
        'matl_code',
        'wh_id',
        'matl_uom',
        'wh_code',
        'batch_code',
        'qty',
        'tr_descr',
        'ivt_id'
    ];
    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function delivDtl()
    {
        return $this->belongsTo(DelivDtl::class, 'trdtl_id');
    }

    public function ivttrHdr()
    {
        return $this->belongsTo(IvttrHdr::class, 'trhdr_id');
    }
    // Pada model IvttrDtl
    public function ivtBal()
    {
        return $this->hasOne(IvtBal::class, 'wh_code', 'wh_code');
    }

    public function isOrderCompleted()
    {
        // Contoh logika untuk mengecek apakah order telah selesai
        return $this->status == 'completed';
    }

    public static function saveIvttrDtl($details, $trhdrId, $whCode, $whCode2 = null)
    {
        foreach ($details as $key => $detail) {
            $trSeq = $key + 1;

            // Pastikan matl_code sudah ada
            if (!isset($detail['matl_code'])) {
                throw new \Exception("Undefined array key 'matl_code'");
            }

            // Simpan transaksi positif untuk wh_code asal
            self::updateOrCreate(
                [
                    'trhdr_id' => $trhdrId,
                    'tr_seq'   => $trSeq,
                ],
                [
                    'wh_code'    => $whCode,
                    'matl_id'    => $detail['matl_id'],
                    'tr_id'      => $trhdrId,
                    'matl_code'  => $detail['matl_code'],
                    'matl_uom'   => $detail['matl_uom'] ?? '',
                    'batch_code' => $detail['batch_code'],
                    'qty'        => $detail['qty'],
                ]
            );

            // Update qty_oh di IvtBal untuk wh_code asal
            $ivtBal = IvtBal::where('wh_code', $whCode)
                ->where('matl_id', $detail['matl_id'])
                ->where('batch_code', $detail['batch_code'])
                ->first();
            if ($ivtBal) {
                $ivtBal->qty_oh -= $detail['qty'];
                $ivtBal->save();
            } else {
                // Opsional: jika record tidak ditemukan, Anda dapat membuat record baru untuk gudang asal
                IvtBal::create([
                    'wh_code'   => $whCode,
                    'matl_id'   => $detail['matl_id'],
                    'matl_code' => $detail['matl_code'],
                    'matl_uom'  => $detail['matl_uom'] ?? '',
                    'batch_code' => $detail['batch_code'],
                    'qty_oh'    => -$detail['qty'],
                ]);
            }

            // Jika ada wh_code2, lakukan transaksi transfer (transaksi negatif) untuk gudang tujuan
            if ($whCode2) {
                $trSeq2 = -$trSeq;
                self::updateOrCreate(
                    [
                        'trhdr_id' => $trhdrId,
                        'tr_seq'   => $trSeq2,
                    ],
                    [
                        'wh_code'    => $whCode2,
                        'matl_id'    => $detail['matl_id'],
                        'tr_id'      => $trhdrId,
                        'matl_code'  => $detail['matl_code'],
                        'matl_uom'   => $detail['matl_uom'] ?? '',
                        'batch_code' => $detail['batch_code'],
                        'qty'        => -$detail['qty'],
                    ]
                );

                // Update qty_oh di IvtBal untuk wh_code2
                $ivtBal2 = IvtBal::where('wh_code', $whCode2)
                    ->where('matl_id', $detail['matl_id'])
                    ->where('batch_code', $detail['batch_code'])
                    ->first();
                if ($ivtBal2) {
                    $ivtBal2->qty_oh += $detail['qty'];
                    $ivtBal2->save();
                } else {
                    // Jika tidak ditemukan, buat record IvtBal baru untuk wh_code2
                    IvtBal::create([
                        'wh_code'   => $whCode2,
                        'matl_id'   => $detail['matl_id'],
                        'matl_code' => $detail['matl_code'],
                        'matl_uom'  => $detail['matl_uom'] ?? '',
                        'batch_code' => $detail['batch_code'],
                        'qty_oh'    => $detail['qty'],
                    ]);
                }
            }
        }
    }
}
