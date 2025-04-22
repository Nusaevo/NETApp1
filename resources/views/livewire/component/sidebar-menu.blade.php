<div>
    <!-- resources/views/livewire/component/sidebar-menu.blade.php -->
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
            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ $isSubmenuActive ? 'here show' : '' }}">
                <span class="menu-link">
                    @if (isset($menu['icon']))
                        <span class="menu-icon">{!! $menu['icon'] !!}</span>
                    @endif
                    <span class="menu-title">{{ $menu['title'] }}</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion {{ $isSubmenuActive ? 'show' : '' }}">
                    @foreach ($menu['sub']['items'] as $submenu)
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs($submenu['path'] ?? '') || request()->routeIs($submenu['path'] . '.*') ? 'active' : '' }}" href="{{ route($submenu['path'], $submenu['params'] ?? []) }}">
                                <span class="menu-bullet">{!! $submenu['bullet'] !!}</span>
                                <span class="menu-title">{{ $submenu['title'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs($menu['path'] ?? '') || request()->routeIs($menu['path'] . '.*') ? 'active' : '' }}" href="{{ route($menu['path'], $menu['params'] ?? []) }}">
                    @if (isset($menu['icon']))
                        <span class="menu-icon">{!! $menu['icon'] !!}</span>
                    @endif
                    <span class="menu-title">{{ $menu['title'] }}</span>
                </a>
            </div>
        @endif
    @endforeach
</div>
