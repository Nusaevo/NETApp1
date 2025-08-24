//
// App Initialization Scripts
// Replace Metronic KT functionality with Bootstrap equivalents
//

// Initialize all Bootstrap components
function initBootstrapComponents() {
    // Auto-initialize components with data attributes
    document.querySelectorAll('[data-bs-toggle]').forEach(element => {
        const toggle = element.getAttribute('data-bs-toggle');

        switch(toggle) {
            case 'tooltip':
                new bootstrap.Tooltip(element);
                break;
            case 'popover':
                new bootstrap.Popover(element);
                break;
            case 'modal':
                new bootstrap.Modal(element);
                break;
            case 'dropdown':
                new bootstrap.Dropdown(element);
                break;
        }
    });
}

// Replace kt- functionality with standard Bootstrap
function migrateKTAttributes() {
    // Replace kt-scrolltop with standard scroll behavior
    document.querySelectorAll('[data-kt-scrolltop]').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // Replace kt-menu with Bootstrap dropdown
    document.querySelectorAll('[data-kt-menu]').forEach(element => {
        element.classList.add('dropdown-menu');
    });

    // Replace kt-menu-trigger with Bootstrap dropdown toggle
    document.querySelectorAll('[data-kt-menu-trigger]').forEach(element => {
        element.setAttribute('data-bs-toggle', 'dropdown');
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initBootstrapComponents();
        migrateKTAttributes();
    });
} else {
    initBootstrapComponents();
    migrateKTAttributes();
}
