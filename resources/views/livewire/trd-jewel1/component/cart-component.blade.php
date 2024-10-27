<div class="d-flex align-items-center " id="kt_header_user_menu_toggle" style="padding-top: 1rem; padding-right: 1rem; padding-left: 1rem;">
    <!--begin::Menu wrapper-->
    <div class="cursor-pointer position-relative symbol" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" style="width: 40px; height: 40px; line-height: 40px;">
        <!-- Cart icon with adjusted font size and position -->
        <a href="{{ route($appCode.'.Transaction.CartOrder') }}" class="menu-link px-5">
            <i class="fas fa-shopping-cart" style="font-size: 24px;"></i>
        </a>

        <!-- Number badge -->
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            {{ $cartCount }}
        </span>
    </div>
    <!--end::Menu wrapper-->
</div>
