<section class="d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h3>Delete account</h3>
        <p>Deleting your account permanently removes its inventory, reports, and account data.</p>
    </div>
    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        Delete account
    </button>
</section>

<div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true"
     @if ($errors->userDeletion->isNotEmpty()) data-auto-show="true" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('profile.destroy') }}" class="modal-content border-0 shadow">
            @csrf
            @method('delete')
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="confirmUserDeletionLabel">Delete your account?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">This permanently deletes your account and its data. Enter your password to confirm.</p>
                <label for="delete_account_password" class="form-label">Password</label>
                <input id="delete_account_password" name="password" type="password"
                       class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                       autocomplete="current-password" required>
                @error('password', 'userDeletion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm">Delete account</button>
            </div>
        </form>
    </div>
</div>
