@props(['src', 'alt', 'width', 'height'])
@php
$imageSrc = !empty($src) ? $src : 'https://via.placeholder.com/300';
@endphp

<div style="position: relative; display: inline-block; cursor: pointer;" onclick="showImagePreview('{{ $src }}')">
    <img
        src="{{ $imageSrc }}"
        alt="{{ $alt }}"
        style="width: {{ $width }}; height: {{ $height }}; object-fit: cover; transition: filter 0.3s;"
        onmouseover="this.style.filter='brightness(0.5)';"
        onmouseout="this.style.filter='brightness(1)';"
    >
    @if($src)
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none;">
            <i class="fas fa-eye" style="font-size: 24px; color: black; cursor: pointer;" onclick="showImagePreview('{{ $src }}')"></i>
        </div>
    @endif
</div>

<script>
    // Show the eye icon on hover
    document.addEventListener('DOMContentLoaded', function() {
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
