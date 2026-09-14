@props(['label', 'value', 'icon' => null])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm kpi-card']) }}>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <p class="text-secondary small mb-1">{{ $label }}</p>
                <h3 class="mb-0">{{ $value }}</h3>
            </div>
            @if ($icon)
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="bi {{ $icon }} fs-5"></i>
                </div>
            @endif
        </div>
        @isset($footer)
            <div class="small text-secondary mt-2">{{ $footer }}</div>
        @endisset
    </div>
</div>
