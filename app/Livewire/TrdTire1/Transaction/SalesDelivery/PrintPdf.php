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

            // Ambil counter dari session (preview) atau dari database
            $sessionKey = 'print_counter_preview_' . $this->object->id . '_surat_jalan';
            if (session()->has($sessionKey)) {
                // Gunakan counter dari session (sudah di-increment untuk preview)
                $this->notaCounter = session($sessionKey);
            } else {
                // Jika tidak ada di session, ambil dari database dan increment untuk preview
                $currentCounter = $this->object->getPrintCounterArray();
                $this->notaCounter = $currentCounter;
                $this->notaCounter['surat_jalan'] = ($this->notaCounter['surat_jalan'] ?? 0) + 1;
                // Simpan di session untuk konsistensi
                session([$sessionKey => $this->notaCounter]);
            }

            // Guard izin cetak ulang untuk Surat Jalan saja (cetakan pertama diizinkan untuk semua)
            // Gunakan counter dari database (bukan preview) untuk pengecekan izin
            $dbCounter = $this->object->getPrintCounterArray();
            $hasPrinted = (($dbCounter['surat_jalan'] ?? 0) > 0);
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
            // Update status_code to PRINT (belum save counter, hanya status)
            // Hanya update status_code tanpa menyentuh print_remarks
            $this->object->status_code = Status::PRINT;
            // Gunakan update() untuk memastikan hanya status_code yang di-update
            OrderHdr::where('id', $this->object->id)->update(['status_code' => Status::PRINT]);
            // Refresh object setelah update
            $this->object->refresh();
        }
    }

    /**
     * Update print counter untuk surat jalan (save ke database)
     * Dipanggil saat tombol Print diklik di browser
     */
    public function updateDeliveryPrintCounter()
    {
        if ($this->object) {
            // Ambil counter preview dari session
            $sessionKey = 'print_counter_preview_' . $this->object->id . '_surat_jalan';
            $previewCounter = session($sessionKey);

            if ($previewCounter) {
                // Save counter preview ke database
                $this->object->print_remarks = $previewCounter;
                $this->object->save();

                // Hapus session setelah berhasil save
                session()->forget($sessionKey);

                // Update counter di property dengan data dari database
                $this->notaCounter = $this->object->fresh()->getPrintCounterArray();

                $newVersion = $this->object->getDisplayFormat();
                $this->dispatch('success', 'Print counter surat jalan berhasil diupdate: ' . $newVersion);
            } else {
                // Fallback: jika tidak ada di session, increment seperti biasa
                $newVersion = OrderHdr::updateDeliveryPrintCounterStatic($this->object->id);
                $this->notaCounter = $this->object->fresh()->getPrintCounterArray();
                $this->dispatch('success', 'Print counter surat jalan berhasil diupdate: ' . $newVersion);
            }
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
