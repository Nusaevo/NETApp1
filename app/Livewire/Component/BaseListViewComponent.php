<?php

namespace App\Livewire\Component;

use App\Livewire\Component\BaseComponent;
use Exception;

abstract class BaseListViewComponent extends BaseComponent
{
    public $items = [];

    protected $listeners = [
        'addItem' => 'addItem',
        'deleteItem' => 'deleteItem',
    ];

    protected function onPreRender()
    {

    }

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        $this->bypassPermissions = true;
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    // public function mount($items = [])
    // {
    //     $this->items = $items;
    // }

    public function addItem()
    {
        try {
            $this->items[] = [];
            $this->dispatch('success', 'Item kosong berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menambahkan item kosong: ' . $e->getMessage());
        }
    }

    public function deleteItem($index)
    {
        try {
            unset($this->items[$index]);
            $this->items = array_values($this->items); // Re-index array
            $this->dispatch('success', 'Item berhasil dihapus.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    abstract public function render();
}
