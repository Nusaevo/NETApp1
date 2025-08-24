@props([
  'permissions' => [],
  'route'       => '',
  'isComponent' => false,
])

@if(! empty($permissions['create']) && $permissions['create'] && ! $isComponent)
  @php
    // 1) build the base URL (only the route‐parameters)
    $baseUrl = route($route, [
      'action' => encryptWithSessionKey('Create'),
    ]);

    // 2) grab the raw query string (e.g. "TYPE=C&foo=bar")
    $qs = request()->getQueryString();

    // 3) append it if non‑empty
    $url = $qs ? $baseUrl . '?' . $qs : $baseUrl;
  @endphp

  <div class="create-button-wrapper mb-3">
    <a href="{{ $url }}" class="btn-create">
      <i class="bi bi-plus-circle me-2"></i>
      Create New Record
    </a>
  </div>
@endif
