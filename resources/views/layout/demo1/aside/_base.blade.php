

{{--begin::Aside--}}
<div
    id="kt_aside"
    class="aside {{ theme()->printHtmlClasses('aside', false) }}"
    data-kt-drawer="true"
    data-kt-drawer-name="aside"
    data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true"
    data-kt-drawer-width="{default:'200px', '300px': '250px'}"
    data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_aside_mobile_toggle"
>

    {{--begin::Brand--}}
    <div class="aside-logo flex-column-auto d-flex justify-content-center " id="kt_aside_logo">
        <a href="{{ $theme->getPageUrl('') }}" >
            @php
                $appcode = Session::get('app_code', '');
                $imagePath = 'customs/logos/' . $appcode . '.png';
            @endphp
            <img alt="Logo" src="{{ asset($imagePath) }}" class="h-45px"/>
        </a>
        @if (theme()->getOption('layout', 'aside/minimize') === true)
            {{--begin::Aside toggler--}}
            {{-- <div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle"
                data-kt-toggle="true"
                data-kt-toggle-state="active"
                data-kt-toggle-target="body"
                data-kt-toggle-name="aside-minimize"
            >
                {!! theme()->getSvgIcon("icons/duotune/arrows/arr079.svg", "svg-icon-1 rotate-180") !!}
            </div> --}}
            {{--end::Aside toggler--}}
        @endif
    </div>



    {{--end::Brand--}}

    {{--begin::Aside menu--}}
    <div class="aside-menu flex-column-fluid">
        @livewire('component.application-component')
        {{ theme()->getView('layout/aside/_menu') }}
    </div>
    {{--end::Aside menu--}}

    {{--begin::Footer--}}
    {{-- <div class="aside-footer flex-column-auto pt-5 pb-7 px-5" id="kt_aside_footer">
        <a href="{{ theme()->getPageUrl('documentation/getting-started/overview') }}" class="btn btn-custom btn-primary w-100" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-delay-show="8000" title="Check out the complete documentation with over 100 components">
        <span class="btn-label">
            {{ __('Documentation') }}
        </span>
            {!! theme()->getSvgIcon("icons/duotune/general/gen005.svg", "btn-icon svg-icon-2") !!}
        </a>
    </div> --}}
    {{--end::Footer--}}
</div>
{{--end::Aside--}}
