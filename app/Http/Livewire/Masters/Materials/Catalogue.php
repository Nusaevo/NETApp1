<?php

namespace App\Http\Livewire\Masters\Materials;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Masters\Material;
use App\Models\Masters\Orderh;
use Illuminate\Support\Facades\Crypt;

class Catalogue extends Component
{
    use WithPagination;

    public $inputs = [
        'description' => '',
        'selling_price1' => '',
        'selling_price2' => '',
        'code' => ''
    ];

    public function render()
    {
        $query = Material::query();

        if (!empty($this->inputs['description'])) {
            $query->where('descr', 'like', '%' . $this->inputs['description'] . '%');
        }
        if (!empty($this->inputs['selling_price1']) && !empty($this->inputs['selling_price2'])) {
            $query->whereBetween('selling_price', [$this->inputs['selling_price1'], $this->inputs['selling_price2']]);
        }
        if (!empty($this->inputs['code'])) {
            $query->where('code', 'like', '%' . $this->inputs['code'] . '%');
        }

        $materials = $query->paginate(9);

        return view('livewire.masters.materials.catalogue', ['materials' => $materials]);
    }

    public function View($id)
    {
        return redirect()->route('materials.detail', ['action' => Crypt::encryptString('View'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('materials.detail', ['action' => Crypt::encryptString('Edit'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function search()
    {
        $this->resetPage();
    }

    public function addToCart($index)
    {
        
    }
}
