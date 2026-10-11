@props(['label', 'value', 'icon' => null, 'color' => null])

@php
    // Without an explicit color, cards take the next palette color in
    // render order, so a row of KPIs always reads as distinct tiles.
    $palette = ['brand', 'amber', 'violet', 'green', 'sky', 'rose', 'blue'];
    $kpiIndex = app()->bound('kpi-card.index') ? app('kpi-card.index') + 1 : 0;
    app()->instance('kpi-card.index', $kpiIndex);
    $color ??= $palette[$kpiIndex % count($palette)];
@endphp

<div {{ $attributes->merge(['class' => 'card kpi-card h-100']) }}>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="min-w-0">
                <p class="text-secondary small fw-medium mb-1">{{ __($label) }}</p>
                <h3 class="mb-0 text-truncate">{{ $value }}</h3>
            </div>
            @if ($icon)
                <span class="icon-tile icon-tile-{{ $color }}">
                    <i class="bi {{ $icon }}"></i>
                </span>
            @endif
        </div>
        @isset($footer)
            <div class="small text-secondary mt-2">{{ $footer }}</div>
        @endisset
    </div>
</div>
