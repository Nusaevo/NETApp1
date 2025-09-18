<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Enums\TrdTire1\Status;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\Auth;

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

            // Guard izin cetak ulang untuk Surat Jalan saja (cetakan pertama diizinkan untuk semua)
            $revData = $this->object->getPrintCounterArray();
            $hasPrinted = (($revData['surat_jalan'] ?? 0) > 0);
            if ($hasPrinted) {
                $userId = Auth::id();
                $allowed = (int) (ConfigConst::where('const_group', 'SEC_LEVEL')
                    ->where('str2', 'UPDATE_AFTER_PRINT')
                    ->where('user_id', $userId)
                    ->value('num1') ?? 0) === 1;
                if (!$allowed) {
                    $this->dispatch('error', 'Anda tidak memiliki izin untuk mencetak ulang surat jalan.');
                    return redirect()->route(
                        'TrdTire1.Transaction.SalesOrder.Detail',
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
     * Update print counter untuk surat jalan
     */
    public function updateDeliveryPrintCounter()
    {
        if ($this->object) {
            $newVersion = OrderHdr::updateDeliveryPrintCounterStatic($this->object->id);
            $this->dispatch('success', 'Print counter surat jalan berhasil diupdate: ' . $newVersion);
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
}
