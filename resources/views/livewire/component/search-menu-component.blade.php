<div>
    <div class="search-menu-wrapper">
        <div class="d-flex align-items-center">
            <!-- Search toggle button - sama ukuran dengan cart -->
            <button type="button"
                    id="custom_search_toggle"
                    class="btn btn-outline-secondary d-flex align-items-center justify-content-center"
                    style="width: 40px; height: 40px;"
                    title="Search Menu">
                <i class="bi bi-search" style="font-size: 1.2rem;"></i>
            </button>
        </div>

        <!-- Search results container -->
        <div id="custom_search_content" class="search-dropdown" wire:ignore.self>
            <div class="search-content">
                <input type="text"
                       class="form-control mb-3"
                       placeholder="Search Menu..."
                       wire:model.live="searchTerm"
                       wire:keydown="onSearchChanged">

                <div id="custom_search_results">
                    @if(!empty($results))
                        <div class="search-results">
                            @foreach($results as $result)
                                <a href="{{ route(str_replace('/', '.', $result->menu_link)) }}"
                                   class="search-result-item">
                                    <div class="search-result-content">
                                        @if(!isNullOrEmptyString($result->menu_header))
                                            <span class="search-breadcrumb">{{ $result->menu_header }} /</span>
                                        @endif
                                        <span class="search-title">{{ $result->menu_caption }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @elseif(!empty($searchTerm))
                        <p class="text-muted text-center py-3">No results found for "{{ $searchTerm }}"</p>
                    @else
                        <p class="text-muted text-center py-3">Start typing to search menu...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchToggle = document.getElementById('custom_search_toggle');
            const searchContent = document.getElementById('custom_search_content');
            const searchInput = searchContent.querySelector('input');

            searchToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                searchContent.classList.toggle('show');

                if (searchContent.classList.contains('show')) {
                    setTimeout(() => {
                        searchInput.focus();
                    }, 100);
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchContent.contains(e.target) && !searchToggle.contains(e.target)) {
                    searchContent.classList.remove('show');
                }
            });

            // Prevent closing when clicking inside dropdown
            searchContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>

    <style>
        .search-menu-wrapper {
            position: relative;
            display: inline-block;
        }

        .search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 350px;
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            box-shadow: 0 8px 30px var(--page-shadow);
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }

        .search-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .search-content {
            padding: 1rem;
        }

        .search-results {
            max-height: 300px;
            overflow-y: auto;
        }

        .search-results::-webkit-scrollbar {
            width: 6px;
        }

        .search-results::-webkit-scrollbar-thumb {
            background-color: var(--bs-secondary-bg);
            border-radius: 3px;
        }

        .search-results::-webkit-scrollbar-track {
            background-color: var(--bs-light-bg-subtle);
        }

        .search-result-item {
            display: block;
            padding: 0.75rem;
            margin-bottom: 0.25rem;
            border-radius: 0.375rem;
            text-decoration: none;
            color: var(--bs-body-color);
            background-color: var(--bs-body-bg);
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .search-result-item:hover {
            background-color: var(--bs-primary-bg-subtle);
            border-color: var(--bs-primary);
            color: var(--bs-primary);
            transform: translateX(4px);
        }

        .search-result-content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .search-breadcrumb {
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
            opacity: 0.8;
        }

        .search-title {
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Form control styling for search input */
        .search-dropdown .form-control {
            border-radius: 0.5rem;
            border: 1px solid var(--bs-border-color);
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
        }

        .search-dropdown .form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
        }

        /* Dark theme adjustments */
        [data-bs-theme="dark"] .search-dropdown {
            background-color: var(--bs-dark);
            border-color: var(--bs-border-color);
        }

        [data-bs-theme="dark"] .search-result-item {
            background-color: var(--bs-dark);
        }

        [data-bs-theme="dark"] .search-result-item:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.1);
        }

        /* Mobile responsiveness */
        @media (max-width: 767.98px) {
            .search-dropdown {
                width: 90vw;
                right: -50px;
            }
        }
    </style>
</div>

