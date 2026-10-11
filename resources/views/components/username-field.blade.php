@props([
    // staff | tenant | landlord | buyer
    'type',
    'value' => null,
    // id of the record being edited (so its own username isn't "taken")
    'ignore' => null,
    // selector of the name input to suggest a username from
    'nameField' => '#name',
    'col' => 'col-md-6',
])

{{-- Username with a live availability check. Blank = generated from the
     name on save. Server-side rule: App\Core\Auth\UsernameRules. --}}
<div class="{{ $col }}">
    <label class="form-label" for="usernameField">{{ __('Username') }} <span class="text-secondary fw-normal">({{ __('used to sign in') }})</span></label>
    <div class="input-group has-validation">
        <span class="input-group-text"><i class="bi bi-at"></i></span>
        <input type="text" id="usernameField" name="username" value="{{ old('username', $value) }}"
               class="form-control @error('username') is-invalid @enderror"
               autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="60"
               placeholder="e.g. amina.mushi"
               data-username-type="{{ $type }}" data-username-ignore="{{ $ignore }}" data-name-field="{{ $nameField }}">
        @error('username')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="invalid-feedback" id="usernameFeedback"></div>
        @enderror
    </div>
    <div class="form-text" id="usernameHint">{{ __('Leave blank to create one from the name. People can also sign in with their phone number or email.') }}</div>
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('usernameField');
            if (! input) return;

            const hint = document.getElementById('usernameHint');
            const feedback = document.getElementById('usernameFeedback');
            const nameInput = document.querySelector(input.dataset.nameField);
            const endpoint = @json(route('username.check'));
            let timer;
            let userTyped = input.value !== '';

            function check() {
                const params = new URLSearchParams({ type: input.dataset.usernameType, username: input.value.trim() });
                if (input.dataset.usernameIgnore) params.set('ignore', input.dataset.usernameIgnore);
                if (nameInput) params.set('name', nameInput.value);

                fetch(endpoint + '?' + params, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        if (! data) return;
                        input.classList.remove('is-invalid', 'is-valid');

                        if (data.available === null) {
                            // Nothing typed - show what would be generated.
                            input.placeholder = data.suggestion ? data.suggestion : 'e.g. amina.mushi';
                            hint.textContent = data.suggestion
                                ? 'Leave blank to use "' + data.suggestion + '", or type your own.'
                                : 'Leave blank to create one from the name.';
                            return;
                        }

                        if (data.available) {
                            input.classList.add('is-valid');
                            hint.textContent = data.message;
                        } else {
                            input.classList.add('is-invalid');
                            if (feedback) {
                                feedback.textContent = data.message + (data.suggestion ? ' Try "' + data.suggestion + '".' : '');
                            }
                        }
                    })
                    .catch(() => {});
            }

            function schedule() {
                clearTimeout(timer);
                timer = setTimeout(check, 350);
            }

            input.addEventListener('input', () => {
                userTyped = input.value !== '';
                input.value = input.value.replace(/\s+/g, '').toLowerCase();
                schedule();
            });
            if (nameInput) {
                nameInput.addEventListener('input', () => { if (! userTyped) schedule(); });
            }
            schedule();
        });
    </script>
    @endpush
@endonce
