<nav class="nav-menu">
    @foreach ($menus as $menu)
        @if (isset($menu['type']) && $menu['type'] === 'accordion')
            @php
                $isSubmenuActive = false;
                foreach ($menu['items'] as $submenu) {
                    if (request()->routeIs($submenu['path'] ?? '') || request()->routeIs($submenu['path'] . '.*')) {
                        $isSubmenuActive = true;
                        break;
                    }
                }
            @endphp
            <div class="menu-item menu-accordion {{ $isSubmenuActive ? 'show' : '' }}">
                <a href="#" class="menu-link" data-bs-toggle="collapse" data-bs-target="#submenu-{{ Str::slug($menu['title']) }}">
                    <div class="menu-icon">
                        <i class="{{ $menu['icon'] ?? 'bi-folder' }}"></i>
                    </div>
                    <span class="menu-title">{{ $menu['title'] }}</span>
                </a>
                <div class="menu-sub collapse {{ $isSubmenuActive ? 'show' : '' }}" id="submenu-{{ Str::slug($menu['title']) }}">
                    @foreach ($menu['items'] as $submenu)
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs($submenu['path'] ?? '') || request()->routeIs($submenu['path'] . '.*') ? 'active' : '' }}"
                               href="{{ route($submenu['path'], $submenu['params'] ?? []) }}">
                                <span class="menu-bullet"></span>
                                <span class="menu-title">{{ $submenu['title'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs($menu['path'] ?? '') || request()->routeIs($menu['path'] . '.*') ? 'active' : '' }}"
                   href="{{ route($menu['path'], $menu['params'] ?? []) }}">
                    <div class="menu-icon">
                        <i class="{{ $menu['icon'] ?? 'bi-circle' }}"></i>
                    </div>
                    <span class="menu-title">{{ $menu['title'] }}</span>
                </a>
            </div>
        @endif
    @endforeach
</nav>
