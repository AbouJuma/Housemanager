<section>
    <h2 class="h6 text-danger">Delete Account</h2>
    <p class="text-secondary small">
        Once your account is deleted, all of its resources and data will be permanently deleted. Please
        download any data you wish to retain before continuing.
    </p>

    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
        Delete Account
    </button>

    <div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="{{ route('profile.destroy') }}" class="modal-content">
                @csrf
                @method('delete')

                <div class="modal-header">
                    <h2 class="modal-title h6">Are you sure you want to delete your account?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary small">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                        Enter your password to confirm.
                    </p>

                    <label for="delete_account_password" class="visually-hidden">Password</label>
                    <input id="delete_account_password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Password">
                    @error('password', 'userDeletion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</section>

@if ($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            new bootstrap.Modal(document.getElementById('confirmUserDeletionModal')).show();
        </script>
    @endpush
@endif
