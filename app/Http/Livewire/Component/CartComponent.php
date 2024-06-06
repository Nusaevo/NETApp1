<?php

namespace App\Http\Livewire\Component;

use Livewire\Component;
use App\Models\TrdJewel1\Transaction\CartHdr;
use Illuminate\Support\Facades\Auth;

class CartComponent extends Component
{
    public $cartCount;

    protected $listeners = ['updateCartCount' => 'refreshCartCount'];

    public function mount()
    {
        $this->refreshCartCount();
    }

    public function refreshCartCount()
    {
        $usercode = Auth::check() ? Auth::user()->code : '';
        $this->cartCount = CartHdr::getCartDetailCount($usercode);
    }

    public function render()
    {
        return view('livewire.trd-jewel1.component.cart-component');
    }
}
