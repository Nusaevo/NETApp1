<div>

    <div class="custom-header-search">
        <div class="d-flex align-items-center" id="kt_header_user_menu_toggle" style="padding-top: 1rem; padding-right: 1rem; padding-left: 1rem;">
            <!--begin::Menu wrapper-->
            <div class="cursor-pointer position-relative symbol" style="width: 40px; height: 40px; line-height: 40px;">
                <!-- Search toggle button -->
                <div id='custom_search_toggle' class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px">
                    {!! getIcon('magnifier', 'fs-2') !!}
                </div>
            </div>
            <!--end::Menu wrapper-->
        </div>

        <!-- Search results container -->
        <div id="custom_search_content" class="menu menu-sub menu-sub-dropdown p-7 w-325px w-md-375px" wire:ignore.self>
            <!--begin::Wrapper-->
            <div>
                <input type="text" class="form-control" placeholder="Search Menu..." wire:model="searchTerm" wire:keydown="onSearchChanged">

                <div id="custom_search_results">
                    @if(!empty($results))
                    <div class="scroll-y mh-200px mh-lg-350px">
                        @foreach($results as $result)
                        <a href="{{ route(str_replace('/', '.', $result->menu_link)) }}" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            @if(!isNullOrEmptyString($result->menu_header))
                            {{ $result->menu_header }} /
                            @endif
                            {{ $result->menu_caption }}
                        </a>
                        @endforeach

                    </div>
                    @else
                    <p class="text-muted">No results found.</p>
                    @endif
                </div>
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Search content-->
    </div>

    <script>
        document.getElementById('custom_search_toggle').addEventListener('click', function() {
            var searchContent = document.getElementById('custom_search_content');
            searchContent.classList.toggle('active'); // Toggle the active class to slide in/out
        });

    </script>


    <style>
        /* Overall Search Container */
        .custom-header-search {
            position: relative;
            display: inline-block;
            align-items: center;
        }

        /* Search Results Container */
        #custom_search_content {
            position: absolute;
            top: 100%;
            right: -50px;
            transform: translateX(100%);
            width: 325px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 10px;
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            display: none;
            /* Ensure it's hidden initially */
        }

        /* Activate the search content on toggle */
        #custom_search_content.active {
            display: block;
            transform: translateX(0);
            opacity: 1;
        }

        /* Search Input */
        #custom_search_content .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        /* Search Results List */
        .scroll-y {
            max-height: 350px;
            overflow-y: auto;
        }

        .scroll-y::-webkit-scrollbar {
            width: 6px;
        }

        .scroll-y::-webkit-scrollbar-thumb {
            background-color: #c1c7d0;
            border-radius: 3px;
        }

        .scroll-y::-webkit-scrollbar-track {
            background-color: #f1f3f5;
        }

        /* Individual Result Items */
        #custom_search_content .d-flex {
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
            text-decoration: none;
        }

        #custom_search_content .d-flex:hover {
            background-color: #f3f6f9;
        }

        #custom_search_content .symbol {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            overflow: hidden;
        }

        #custom_search_content .symbol img {
            width: 100%;
            height: auto;
        }

        /* Text and Icon Styling */
        #custom_search_content .fs-6 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        #custom_search_content .fs-7 {
            font-size: 12px;
            color: #999;
        }

        /* No Results Text */
        .text-muted {
            font-size: 14px;
            color: #999;
            text-align: center;
            margin-top: 20px;
        }

    </style>


</div>

