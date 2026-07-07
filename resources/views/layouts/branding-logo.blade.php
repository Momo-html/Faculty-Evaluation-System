@php
    $imageKey = $imageKey ?? 'header_logo_path';
    $class = $class ?? '';
    $logoUrl = ($portalImage)($imageKey);
    $label = $portalSettings['school_name'] ?? 'School';
    $brandText = $text ?? ($portalSettings['portal_name'] ?? 'Portal');
@endphp

<div class="{{ trim('brand-lockup '.$class) }}">
    @if($logoUrl)
        <img class="brand-lockup-image" src="{{ $logoUrl }}" alt="{{ $label }} logo">
    @else
        <div class="brand-lockup-fallback">FEU</div>
    @endif
    @if($brandText)
        <span class="brand-lockup-text">{{ strtoupper($brandText) }}</span>
    @endif
</div>
