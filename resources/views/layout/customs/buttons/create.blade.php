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

  <div style="padding:5px">
    <x-ui-button
      :visible="true"
      :enabled="true"
      clickEvent="{{ $url }}"
      cssClass="btn btn-primary mb-5"
      type="Route"
      :loading="false"
      iconPath=""
      button-name="Create"
    />
  </div>
@endif
