@props(['src', 'alt', 'width' => '300px', 'height' => '300px', 'lazy' => true])

@php
$imageSrc = !empty($src) ? $src : 'https://via.placeholder.com/300';
$placeholderSrc = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22300%22%20height%3D%22300%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23f0f0f0%22/%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20fill%3D%22%23ccc%22%3ELoading...%3C/text%3E%3C/svg%3E';
@endphp

<div style="position: relative; display: inline-block; cursor: pointer; width: {{ $width }}; height: {{ $height }}; overflow: hidden; display: flex; align-items: center; justify-content: center;" onclick="showImagePreview('{{ $src }}')">
    <img
        @if($lazy && !empty($src))
            src="{{ $placeholderSrc }}"
            data-src="{{ $imageSrc }}"
            class="lazy-image"
        @else
            src="{{ $imageSrc }}"
        @endif
        alt="{{ $alt }}"
        style="max-width: 100%; max-height: 100%; object-fit: contain; transition: filter 0.3s;"
        onmouseover="this.style.filter='brightness(0.5)';"
        onmouseout="this.style.filter='brightness(1)';"
    >
    @if($src)
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none;">
            <i class="fas fa-eye" style="font-size: 16px; color: white; cursor: pointer;" onclick="showImagePreview('{{ $src }}')"></i>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lazy loading functionality
        const lazyImages = document.querySelectorAll('.lazy-image');

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.getAttribute('data-src');
                        if (src) {
                            img.src = src;
                            img.classList.remove('lazy-image');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px 0px', // Load images 50px before they come into view
                threshold: 0.01
            });

            lazyImages.forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            lazyImages.forEach(img => {
                const src = img.getAttribute('data-src');
                if (src) {
                    img.src = src;
                }
            });
        }

        // Image preview functionality
        const imageContainers = document.querySelectorAll('div[style*="position: relative; display: inline-block;"]');
        imageContainers.forEach(container => {
            container.addEventListener('mouseenter', function() {
                const icon = container.querySelector('div[style*="position: absolute;"]');
                if (icon) {
                    icon.style.display = 'block';
                }
            });
            container.addEventListener('mouseleave', function() {
                const icon = container.querySelector('div[style*="position: absolute;"]');
                if (icon) {
                    icon.style.display = 'none';
                }
            });
        });
    });
</script>
