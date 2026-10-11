@props(['dark' => false])

{{-- English / Kiswahili switch - App\Http\Controllers\LocaleController. --}}
<div {{ $attributes->merge(['class' => 'locale-switch']) }} role="group" aria-label="{{ __('Language') }}">
    @foreach (['en' => 'EN', 'sw' => 'SW'] as $code => $label)
        <form method="POST" action="{{ route('locale.switch', $code) }}">
            @csrf
            <button type="submit" class="{{ app()->getLocale() === $code ? 'active' : '' }}"
                    title="{{ $code === 'en' ? 'English' : 'Kiswahili' }}" @if (app()->getLocale() === $code) aria-pressed="true" @endif>
                {{ $label }}
            </button>
        </form>
    @endforeach
</div>
