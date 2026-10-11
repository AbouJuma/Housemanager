<button type="button" {{ $attributes->merge(['class' => 'theme-switch']) }} onclick="toggleTheme()" aria-label="{{ __('Switch between light and dark mode') }}" title="{{ __('Light / dark mode') }}">
    <i class="bi bi-sun-fill"></i>
    <i class="bi bi-moon-stars-fill"></i>
</button>
