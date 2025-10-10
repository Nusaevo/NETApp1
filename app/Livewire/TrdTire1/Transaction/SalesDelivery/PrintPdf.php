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
    public $notaCounter = [];

    protected function onPreRender()
    {
        $this->bypassPermissions = true;
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = OrderHdr::findOrFail($this->objectIdValue);

            // Inisialisasi counter dari array nota
            $this->notaCounter = $this->object->getPrintCounterArray();

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
            // Update counter di property
            $this->notaCounter = $this->object->fresh()->getPrintCounterArray();

            $this->dispatch('success', 'Print counter surat jalan berhasil diupdate: ' . $newVersion);
            $this->dispatch('refreshData');

            // Refresh halaman setelah berhasil update counter
            // $this->redirect(route('TrdTire1.Transaction.SalesOrder.Detail', [
            //     'action'   => encryptWithSessionKey('Edit'),
            //     'objectId' => encryptWithSessionKey($this->object->id),
            // ]), navigate: true);
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

    /**
     * Check if current ship_to is the first option
     */
    public function isFirstShipTo()
    {
        if (!$this->object || !$this->object->Partner) {
            return true; // Default to show header if no object or partner
        }

        $partner = $this->object->Partner;

        // Get shipping options from partner detail
        if (!$partner->PartnerDetail || empty($partner->PartnerDetail->shipping_address)) {
            return true; // Default to show header if no shipping options
        }

        $shipDetail = $partner->PartnerDetail->shipping_address;
        if (is_string($shipDetail)) {
            $shipDetail = json_decode($shipDetail, true);
        }

        if (!is_array($shipDetail) || empty($shipDetail)) {
            return true; // Default to show header if no valid shipping options
        }

        // Get first option
        $firstOption = reset($shipDetail);
        $firstShipToName = $firstOption['name'] ?? '';

        // Compare with current ship_to_name
        return $this->object->ship_to_name === $firstShipToName;
    }
}
