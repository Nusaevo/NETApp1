<?php

namespace App\Core\Bootstrap;

class BootstrapDefault
{
    public function init()
    {
        // 1) Light sidebar layout (default.html)
        // $this->initLightSidebarLayout();

        // 2) Dark sidebar layout (default.html)
        $this->initDarkSidebarLayout();

        // 3) Dark header layout (default_header_layout.html)
        // $this->initDarkHeaderLayout();

        // 4) Light header layout (default_header_layout.html)
        // $this->initLightHeaderLayout();

        # Init global assets for default layout
        $this->initAssets();
    }

    public function initAssets()
    {
        # Include global vendors
        addVendors(['datatables']);

        # Include global javascript files
        // addJavascriptFile('assets/js/custom/widgets.js');
        // addJavascriptFile('assets/js/custom/apps/chat/chat.js');
        // addJavascriptFile('assets/js/custom/utilities/modals/upgrade-plan.js');
        // addJavascriptFile('assets/js/custom/utilities/modals/create-app.js');
        // addJavascriptFile('assets/js/custom/utilities/modals/users-search.js');
        // addJavascriptFile('assets/js/custom/utilities/modals/new-target.js');
        // addCssFile(asset('customs/css/pagebase.css'));
        // addJavascriptFile('https://code.jquery.com/jquery-3.7.0.min.js');
        // addJavascriptFile('https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js');
        // addJavascriptFile('https://cdn.datatables.net/1.13.7/js/dataTables.jqueryui.min.js');
        // addJavascriptFile('https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js');
        // addJavascriptFile('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js');
        // addJavascriptFile('https://cdn.datatables.net/buttons/2.0.0/js/dataTables.buttons.min.js');
        // addJavascriptFile('https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js');
        // addJavascriptFile('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js');
        // addJavascriptFile('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js');
    }

    public function initDarkSidebarLayout()
    {
        addHtmlAttribute('body', 'data-kt-app-layout', 'dark-sidebar');
        addHtmlAttribute('body', 'data-kt-app-header-fixed', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-enabled', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-fixed', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-hoverable', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-push-header', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-push-toolbar', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-push-footer', 'true');
        addHtmlAttribute('body', 'data-kt-app-toolbar-enabled', 'true');

        addHtmlClass('body', 'app-default');
    }

    public function initLightSidebarLayout()
    {
        addHtmlAttribute('body', 'data-kt-app-layout', 'light-sidebar');
        addHtmlAttribute('body', 'data-kt-app-header-fixed', 'false');
        addHtmlAttribute('body', 'data-kt-app-sidebar-enabled', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-fixed', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-hoverable', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-push-header', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-push-toolbar', 'true');
        addHtmlAttribute('body', 'data-kt-app-sidebar-push-footer', 'true');
        addHtmlAttribute('body', 'data-kt-app-toolbar-enabled', 'true');

        addHtmlClass('body', 'app-default');
    }

    public function initDarkHeaderLayout()
    {
        addHtmlAttribute('body', 'data-kt-app-layout', 'dark-header');
        addHtmlAttribute('body', 'data-kt-app-header-fixed', 'true');
        addHtmlAttribute('body', 'data-kt-app-toolbar-enabled', 'true');

        addHtmlClass('body', 'app-default');
    }

    public function initLightHeaderLayout()
    {
        addHtmlAttribute('body', 'data-kt-app-layout', 'light-header');
        addHtmlAttribute('body', 'data-kt-app-header-fixed', 'true');
        addHtmlAttribute('body', 'data-kt-app-toolbar-enabled', 'true');

        addHtmlClass('body', 'app-default');
    }

}
