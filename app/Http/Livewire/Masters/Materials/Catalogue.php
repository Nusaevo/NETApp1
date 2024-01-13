<?php
namespace App\Http\Livewire\Masters\Materials;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Material;
use Illuminate\Support\Facades\Crypt;

class Catalogue extends Component
{
    use WithPagination;

    public $searchDescr = '';
    public $searchPrice = '';
    public $searchCode = '';
    
    public function render()
    {
        $query = Material::query();

        if ($this->searchDescr) {
            $query->where('descr', 'like', '%' . $this->searchDescr . '%');
        }
        if ($this->searchPrice) {
            $query->where('price', $this->searchPrice); // Adjust as needed
        }
        if ($this->searchCode) {
            $query->where('code', 'like', '%' . $this->searchCode . '%');
        }

        $materials = $query->paginate(9);

        return view('livewire.masters.materials.catalogue', ['materials' => $materials]);
    }

    public function View($id)
    {
        dd("sd");
        return redirect()->route('materials.detail', ['action' => Crypt::encryptString('View'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('materials.detail', ['action' => Crypt::encryptString('Edit'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function updatingSearchDescr()
    {
        $this->resetPage();
    }

    public function updatingSearchPrice()
    {
        $this->resetPage();
    }

    public function updatingSearchCode()
    {
        $this->resetPage();
    }
}
