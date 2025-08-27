<div class="cart-component-container position-relative">
    <a href="{{ route('TrdJewel1.Transaction.CartOrder') }}"
       class="btn btn-outline-secondary position-relative d-flex align-items-center justify-content-center"
       style="width: 40px; height: 40px;"
       title="Shopping Cart">
        <!-- Cart icon using Bootstrap Icons -->
        <i class="bi bi-cart3" style="font-size: 1.2rem;"></i>

        <!-- Cart count badge -->
        @if($cartCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="font-size: 0.75rem; min-width: 20px; height: 20px; line-height: 18px;">
            {{ $cartCount > 99 ? '99+' : $cartCount }}
        </span>
        @endif
    </a>
</div>
