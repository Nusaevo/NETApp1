//
// Bootstrap + Essential Plugins JavaScript Bundle
//

module.exports = [
    // Core dependencies
    'node_modules/jquery/dist/jquery.js',
    'node_modules/@popperjs/core/dist/umd/popper.js',
    'node_modules/bootstrap/dist/js/bootstrap.min.js',

    // Essential utilities
    'node_modules/moment/min/moment-with-locales.min.js',
    'node_modules/axios/dist/axios.js',

    // Form & UI components (keep the useful ones)
    'node_modules/select2/dist/js/select2.full.js',
    'node_modules/sweetalert2/dist/sweetalert2.min.js',
    'node_modules/toastr/dist/toastr.min.js',
    'node_modules/flatpickr/dist/flatpickr.js',

    // DataTables (essential for your project)
    'node_modules/datatables.net/js/jquery.dataTables.js',
    'node_modules/datatables.net-bs5/js/dataTables.bootstrap5.js',
    'node_modules/datatables.net-buttons/js/dataTables.buttons.js',
    'node_modules/datatables.net-buttons-bs5/js/buttons.bootstrap5.js',

    // Form validation
    'node_modules/bootstrap-maxlength/src/bootstrap-maxlength.js',
    'node_modules/inputmask/dist/inputmask.js',
    'node_modules/inputmask/dist/bindings/inputmask.binding.js',

    // Charts (if needed)
    'node_modules/chart.js/dist/chart.umd.js',
    'node_modules/apexcharts/dist/apexcharts.min.js',

    // Custom app scripts
    'resources/bootstrap/custom/app-init.js'
];
