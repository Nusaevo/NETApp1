<!--begin::Header-->
@php
    $appUrl = config('app.url');
    $subdomain = getSubdomain($appUrl);
    $appcode = Session::get('app_code', '');
    $imagePath = 'customs/logos/' . $appcode . '.png';
@endphp

<div class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom" id="app-header">
    <div class="container-fluid">
            <!-- Mobile hamburger + Logo & Subdomain -->
        <div class="d-flex align-items-center">
            <!-- Hamburger for mobile -->
            <button class="btn btn-outline-primary me-3 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open menu">
                <i class="bi bi-list" style="font-size: 1.25rem;"></i>
            </button>
        </div>

        <!-- Right side controls -->
        <div class="d-flex align-items-center ms-auto">
            <!-- Cart Component (only if user has access) -->
            @if (auth()->check())
                @php
                $usercode = Auth::check() ? Auth::user()->code : '';
                $access = \App\Models\SysConfig1\ConfigRight::getPermissionsByMenu("TrdJewel1/Transaction/CartOrder");
                @endphp

                @if($access['create'])
                <div class="me-3">
                    @livewire('component.cart-component')
                </div>
                @endif
            @endif

            <!-- Search Menu Component -->
            <div class="me-3">
                @livewire('component.search-menu-component')
            </div>

            <!-- Mobile profile icon (right side) -->
            <button class="btn btn-outline-secondary me-3 d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileProfile" aria-controls="mobileProfile" title="Profile">
                <i class="bi bi-person-circle" style="font-size: 1.2rem;"></i>
            </button>

            <!-- Desktop User Dropdown -->
            <div class="profile-dropdown-container dropdown d-none d-lg-block">
                <button class="profile-dropdown-trigger btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i>
                    {{ Auth::user()->name ?? 'User' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ url('/SysConfig1/AccountSetting/Detail/' . encryptWithSessionKey('Edit') . '/' . encryptWithSessionKey(Auth::id())) }}"><i class="bi bi-person me-2"></i>My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!--end::Header-->

@push('styles')
<style>
    /* Profile Dropdown - Simplified CSS */
    .profile-dropdown-container {
        position: relative;
    }

    .profile-dropdown-container .dropdown-menu {
        display: none;
        z-index: 1050;
        background-color: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }

    .profile-dropdown-container .dropdown-menu.show {
        display: block !important;
    }

    .profile-dropdown-container .dropdown-item {
        transition: background-color 0.15s ease-in-out;
    }

    .profile-dropdown-container .dropdown-item:hover {
        background-color: var(--bs-light);
    }

    /* Dark theme */
    [data-bs-theme="dark"] .profile-dropdown-container .dropdown-menu {
        background-color: var(--bs-dark);
        border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .profile-dropdown-container .dropdown-item:hover {
        background-color: var(--bs-secondary-bg);
    }
</style>
@endpush

@push('scripts')
<script>
// Profile Dropdown - Minimal
document.addEventListener('DOMContentLoaded', function() {
    // Let the global initialization handle everything
    // Just add component-specific logging
    const profileTrigger = document.querySelector('.profile-dropdown-trigger');
    if (profileTrigger) {
        // Component-specific click logging
        profileTrigger.addEventListener('click', function(e) {
            // Silent operation
        });
    }
});
</script>
@endpush
