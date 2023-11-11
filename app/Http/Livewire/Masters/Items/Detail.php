<?php

namespace App\Http\Livewire\Masters\Items;

use Livewire\Component;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\CategoryItem;
use Illuminate\Validation\Rule;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\ItemWarehouse;
use App\Models\PriceCategory;
use App\Models\ItemPrice;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $object_detail;
    public $VersioNumber;
    public $action = 'Create';
    public $objectId;
    public $inputs = [];
    public $input_details = [];
    public $status = '';

    public $item_categories;
    public $units;

    public $unit_row = 0;
    public $deletedItems = [];
    public $newItems = [];
    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        $this->refreshItemCategory();

        $unitsData = Unit::orderByName()->get();
        $this->units = $unitsData->map(function ($data) {
            return [
                'label' => $data->name,
                'value' => $data->id,
            ];
        })->toArray();

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = Item::withTrashed()->find($this->objectId);
            $this->object_detail = ItemUnit::ItemId($this->object->id)->get();
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            foreach ($this->object_detail as $index => $detail) {
                $this->input_details[$index] = populateArrayFromModel($detail);
                $this->input_details[$index]['id'] = $detail->id;
            }
        } else {
            $this->object = new Item();
        }
    }


    public function render()
    {
        return view('livewire.masters.items.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.name' => 'required|string|min:1|max:50',
            'inputs.category_item_id' => 'required',
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.name'      => 'Name',
        'inputs.category_item_id'      => 'Kategori Harga'
    ];

    public function refreshItemCategory()
    {
        $categoriesData = CategoryItem::orderByName()->get();
        if (!$categoriesData->isEmpty()) {
            $this->item_categories = $categoriesData->map(function ($data) {
                return [
                    'label' => $data->name,
                    'value' => $data->id,
                ];
            })->toArray();
            $this->inputs['category_item_id'] = $this->item_categories[0]['value'];
        } else {
            $this->item_categories = [];
            $this->inputs['category_item_id'] = null;
        }
    }

    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        return $objectData;
    }

    public function addDetails()
    {
        if (!empty($this->units)) {
            $unitDetail = [
                'unit_id' => $this->units[0]['value'],
                'multiplier' => 1,
                'to_unit_id' => $this->units[0]['value'],
            ];

            array_push($this->input_details, $unitDetail);
        }
        $newDetail = end($this->input_details);
        $this->newItems[] = $newDetail;
        $this->unit_row++;
    }

    public function deleteDetails($index)
    {
        if (isset($this->input_details[$index]['id'])) {
            $this->deletedItems[] = $this->input_details[$index]['id'];
        }
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);

    }


    public function validateForms()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }
    public function validateUnits()
    {
        if (!empty($this->input_details)) {
            foreach ($this->input_details as $index => $detail) {
                if ($detail['unit_id'] == $detail['to_unit_id']) {
                    throw new Exception("Unit Dari dan Unit Ke tidak boleh sama untuk Satuan Barang baris " . ($index + 1));
                }
            }
            $unitIds = array_column($this->input_details, 'unit_id');
            $toUnitIds = array_column($this->input_details, 'to_unit_id');

            if (count($unitIds) !== count(array_flip($unitIds))) {
                throw new Exception("Ditemukan duplikasi 'Unit Dari' dalam  Satuan Barang.");
            }
            if (count($toUnitIds) !== count(array_flip($toUnitIds))) {
                throw new Exception("Ditemukan duplikasi 'Unit Ke' dalam  Satuan Barang.");
            }
        }
    }


    public function Create()
    {
        $this->validateForms();
        DB::beginTransaction();
        try {
            $this->validateUnits();
            $objectData = $this->populateObjectArray();
            $this->object = Item::create($objectData);

            foreach ($this->input_details as $inputDetail) {
                $inputDetail['item_id'] = $this->object->id;
                $newItemUnit = ItemUnit::create($inputDetail);

                // Create related records in Warehouse and ItemPrice
                $warehouse = Warehouse::all();
                foreach ($warehouse as $warehouse) {
                    ItemWarehouse::firstOrCreate(
                        [
                            'item_unit_id' => $newItemUnit->id,
                            'warehouse_id' => $warehouse->id,
                            'qty' => 0,
                            'qty_defect' => 0,
                        ]
                    );
                }

                $priceCategory = PriceCategory::all();
                foreach ($priceCategory as $priceCategory) {
                    ItemPrice::firstOrCreate(
                        [
                            'price' => 0,
                            'item_unit_id' => $newItemUnit->id,
                            'price_category_id' => $priceCategory->id
                        ]
                    );
                }
            }
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['name']])
            ]);
            $this->reset('inputs');
            $this->reset('input_details');
            $this->refreshItemCategory();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "User", 'message' => $e->getMessage()])
            ]);
        }
    }




    public function Edit()
    {
        $this->validateForms();
        DB::beginTransaction();
        try {
            $this->validateUnits();
            if ($this->object) {
                $objectData = $this->populateObjectArray();
                $this->object->update($objectData);
                foreach ($this->deletedItems as $deletedId) {
                    ItemWarehouse::where('item_unit_id', $deletedId)->delete();
                    ItemPrice::where('item_unit_id', $deletedId)->delete();
                    ItemUnit::where('id', $deletedId)->delete();
                }
                $this->deletedItems = [];

                foreach ($this->input_details as $inputDetail) {
                    if (isset($inputDetail['id']) && !in_array($inputDetail['id'], $this->deletedItems)) {
                        $detail = ItemUnit::find($inputDetail['id']);
                        $detail->update($inputDetail);
                    } elseif (!isset($inputDetail['id'])) {
                        $inputDetail['item_id'] = $this->object->id;
                        $newItemUnit = ItemUnit::create($inputDetail);

                        $warehouse = Warehouse::all();
                        foreach ($warehouse as $warehouse) {
                            ItemWarehouse::firstOrCreate(
                                [
                                    'item_unit_id' => $newItemUnit->id,
                                    'warehouse_id' => $warehouse->id,
                                    'qty' => 0,
                                    'qty_defect' => 0,
                                ]
                            );
                        }

                        $priceCategory = PriceCategory::all();
                        foreach ($priceCategory as $priceCategory) {
                            ItemPrice::firstOrCreate(
                                [
                                    'price' => 0,
                                    'item_unit_id' => $newItemUnit->id,
                                    'price_category_id' => $priceCategory->id
                                ]
                            );
                        }
                    }
                }
                $this->newItems = [];

                DB::commit();
                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'success',
                    'message' => Lang::get('generic.success.update', ['object' => $this->object->name])
                ]);
                $this->VersioNumber = $this->object->version_number;
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.update', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
    }
}
