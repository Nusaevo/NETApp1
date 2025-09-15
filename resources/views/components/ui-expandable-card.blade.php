<div class="expandable-card-wrapper mb-4">
    @isset($id)
    <div class="card border-0 shadow-lg rounded-3 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white border-0 py-4 collapsible cursor-pointer position-relative"
             data-bs-toggle="collapse"
             data-bs-target="#{{ $id }}"
             aria-expanded="{{ $isOpen == 'true' ? 'true' : 'false' }}"
             aria-controls="{{ $id }}">

            <div class="d-flex justify-content-between align-items-center position-relative">
                @isset($title)
                <div class="d-flex align-items-center">

                    <div>
                        <h4 class="card-title mb-0 fw-bold text-white">{{ $title }}</h4>
                    </div>
                </div>
                @endisset

                <div class="card-toolbar">
                    <div class="expand-indicator bg-white rounded-circle p-3 shadow-sm d-flex align-items-center justify-content-center">
                        <i class="bi bi-chevron-down text-dark fs-3 fw-bold transition-rotate"></i>
                    </div>
                </div>
            </div>
        </div>

        @isset($isOpen)
        <div id="{{ $id }}" class="collapse {{ $isOpen == 'true' ? 'show' : '' }}">
            <div class="card-body p-4 bg-light">
                <div class="content-wrapper bg-white rounded-2 p-4 shadow-sm">
                    @isset($slot)
                    {{ $slot }}
                    @endisset
                </div>
            </div>
        </div>
        @endisset
    </div>
    @endisset
</div>

<style>
.expandable-card-wrapper {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --hover-gradient: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}

.bg-gradient-primary {
    background: var(--primary-gradient);
}

.collapsible {
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
}

.collapsible:hover {
    background: var(--hover-gradient) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3) !important;
}

.collapsible:active {
    transform: translateY(0);
}

.collapsible[aria-expanded="true"] .transition-rotate {
    transform: rotate(180deg);
}

.collapsible[aria-expanded="true"] {
    background: var(--hover-gradient) !important;
}

.transition-rotate {
    transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.expand-indicator {
    width: 50px;
    height: 50px;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.icon-container {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.collapsible:hover .expand-indicator {
    background-color: rgba(255, 255, 255, 0.95) !important;
    transform: scale(1.15);
    border-color: rgba(255, 255, 255, 0.6);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.collapsible:hover .icon-container {
    background-color: rgba(255, 255, 255, 0.95) !important;
    transform: scale(1.1);
    border-color: rgba(255, 255, 255, 0.6);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.collapsible:hover .icon-container i {
    color: var(--bs-primary) !important;
    transform: scale(1.1);
}

.collapsible:hover .expand-indicator i {
    color: var(--bs-dark) !important;
    transform: scale(1.1);
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-1px);
}

.content-wrapper {
    border-left: 4px solid var(--bs-primary);
    transition: all 0.3s ease;
}

.collapse {
    transition: all 0.4s ease !important;
}

/* Ripple effect */
.collapsible::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.6s ease;
    transform: translate(-50%, -50%);
    pointer-events: none;
}

.collapsible:active::before {
    width: 300px;
    height: 300px;
}

/* Loading animation for content */
.content-wrapper {
    animation: slideInUp 0.5s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Custom text opacity */
.text-white-75 {
    color: rgba(255, 255, 255, 0.75) !important;
}

/* Icon and text enhancements */
.bi {
    display: inline-block;
    vertical-align: middle;
}

.icon-container i,
.expand-indicator i {
    transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .expandable-card-wrapper .card-header {
        padding: 1rem !important;
    }

    .expandable-card-wrapper h4 {
        font-size: 1.1rem;
    }

    .expand-indicator {
        width: 45px;
        height: 45px;
    }

    .icon-container {
        width: 45px;
        height: 45px;
    }

    .expand-indicator i {
        font-size: 1.2rem !important;
    }

    .icon-container i {
        font-size: 1.1rem !important;
    }
}
</style>

