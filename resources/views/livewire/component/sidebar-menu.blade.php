<div class="sidebar-menu">
    <!-- Bootstrap Sidebar Menu Component -->
    <style>
    .sidebar-menu .nav-link {
        color: var(--bs-body-color);
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .sidebar-menu .nav-link:hover {
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        transform: translateX(4px);
    }

    .sidebar-menu .nav-link.active {
        background: linear-gradient(135deg, var(--bs-primary), #764ba2);
        color: white;
        box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.3);
    }

    .sidebar-menu .nav-link.active .bi-chevron-down {
        transform: rotate(180deg);
    }

    .sidebar-menu .submenu-link {
        font-size: 0.9rem;
        color: var(--bs-secondary-color);
        margin-left: 0.5rem;
    }

    .sidebar-menu .submenu-link:hover {
        background: rgba(var(--bs-primary-rgb), 0.08);
        color: var(--bs-primary);
        transform: translateX(2px);
    }

    .sidebar-menu .submenu-link.active {
        background: rgba(var(--bs-primary-rgb), 0.15);
        color: var(--bs-primary);
        font-weight: 500;
    }

    .transition-all {
        transition: all 0.3s ease;
    }

    [data-bs-theme="dark"] .sidebar-menu .nav-link:hover {
        background: rgba(var(--bs-primary-rgb), 0.15);
    }

    [data-bs-theme="dark"] .sidebar-menu .submenu-link:hover {
        background: rgba(var(--bs-primary-rgb), 0.12);
    }
    </style>

    @foreach ($menus as $menu)
        @if (isset($menu['sub']))
            @php
                $isSubmenuActive = false;
                foreach ($menu['sub']['items'] as $submenu) {
                    if (request()->routeIs($submenu['path'] ?? '') || request()->routeIs($submenu['path'] . '.*')) {
                        $isSubmenuActive = true;
                        break;
                    }
                }
            @endphp

            <!-- Menu item with submenu -->
            <div class="nav-item">
                <a class="nav-link d-flex align-items-center py-3 px-3 mb-1 rounded-3 collapsed {{ $isSubmenuActive ? 'active' : '' }}"
                   data-bs-toggle="collapse"
                   href="#submenu-{{ Str::slug($menu['title']) }}"
                   role="button"
                   aria-expanded="{{ $isSubmenuActive ? 'true' : 'false' }}">
                    @if (isset($menu['icon']))
                        <span class="me-3 fs-5">{!! $menu['icon'] !!}</span>
                    @endif
                    <span class="fw-500">{{ $menu['title'] }}</span>
                    <i class="bi bi-chevron-down ms-auto transition-all" style="font-size: 0.75rem;"></i>
                </a>

                <div class="collapse {{ $isSubmenuActive ? 'show' : '' }}" id="submenu-{{ Str::slug($menu['title']) }}">
                    <ul class="list-unstyled ps-4 mb-2">
                        @foreach ($menu['sub']['items'] as $submenu)
                            <li class="mb-1">
                                <a class="nav-link d-flex align-items-center py-2 px-3 rounded-3 submenu-link {{ request()->routeIs($submenu['path'] ?? '') || request()->routeIs($submenu['path'] . '.*') ? 'active' : '' }}"
                                   href="{{ route($submenu['path'], $submenu['params'] ?? []) }}">
                                    <span class="me-3 opacity-75" style="font-size: 0.5rem;">{!! $submenu['bullet'] ?? '●' !!}</span>
                                    <span>{{ $submenu['title'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <!-- Single menu item -->
            <div class="nav-item">
                <a class="nav-link d-flex align-items-center py-3 px-3 mb-1 rounded-3 {{ request()->routeIs($menu['path'] ?? '') || request()->routeIs($menu['path'] . '.*') ? 'active' : '' }}"
                   href="{{ route($menu['path'], $menu['params'] ?? []) }}">
                    @if (isset($menu['icon']))
                        <span class="me-3 fs-5">{!! $menu['icon'] !!}</span>
                    @endif
                    <span class="fw-500">{{ $menu['title'] }}</span>
                </a>
            </div>
        @endif
    @endforeach
</div>

<script>
    // Close mobile offcanvas when a menu link is clicked
    document.addEventListener('click', function(e) {
        const target = e.target.closest('a.nav-link, a.submenu-link');
        if (target) {
            const mobileSidebarEl = document.getElementById('mobileSidebar');
            if (mobileSidebarEl && window.innerWidth <= 991.98) {
                try {
                    const bs = bootstrap.Offcanvas.getInstance(mobileSidebarEl) || new bootstrap.Offcanvas(mobileSidebarEl);
                    bs.hide();
                } catch (err) {
                    // ignore
                }
            }
        }
    });
</script>
