@php
    $toastTypes = [
        'success' => ['color' => 'success', 'icon' => 'bi-check-circle-fill',        'title' => 'Success'],
        'error'   => ['color' => 'danger',  'icon' => 'bi-exclamation-octagon-fill', 'title' => 'Error'],
    ];
@endphp

<style>
    /* Auto-dismiss: change 4s here (both places) to adjust the duration */
    .toast-auto { overflow: hidden; animation: toast-vanish .5s ease-in 4s forwards; }
    .toast-auto .toast-progress { height: 3px; animation: toast-progress 4s linear forwards; }
    .toast-auto:hover,
    .toast-auto:hover .toast-progress { animation-play-state: paused; }

    @keyframes toast-vanish {
        0%   { opacity: 1; max-height: 600px; }
        99%  { opacity: 0; max-height: 600px; }
        100% { opacity: 0; max-height: 0; margin: 0; visibility: hidden; }
    }
    @keyframes toast-progress {
        from { width: 100%; }
        to   { width: 0; }
    }
</style>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">

    {{-- Success / Error flash messages --}}
    @foreach ($toastTypes as $key => $type)
        @if (session($key))
            <div class="toast toast-auto show border-0 border-start border-4 border-{{ $type['color'] }} shadow"
                 role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex align-items-start p-3">
                    <i class="bi {{ $type['icon'] }} text-{{ $type['color'] }} fs-5 me-3"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $type['title'] }}</div>
                        <div class="small text-secondary">{{ session($key) }}</div>
                    </div>

                    <div class="modal fade" id="foodoConfirmModal" tabindex="-1" aria-labelledby="foodoConfirmTitle" aria-hidden="true">
                        <div class="modal-dialog modal-sm modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header border-0 pb-1">
                                    <h6 class="modal-title fw-bold" id="foodoConfirmTitle">Please confirm</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body pt-1">
                                    <p class="small text-secondary mb-0" id="foodoConfirmMessage"></p>
                                </div>
                                <div class="modal-footer border-0 pt-1">
                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal" id="foodoConfirmCancel">Cancel</button>
                                    <button type="button" class="btn btn-sm btn-danger" id="foodoConfirmAccept">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                    window.foodoConfirm = function (message, options = {}) {
                        return new Promise((resolve) => {
                            const modalElement = document.getElementById('foodoConfirmModal');
                            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                            const title = document.getElementById('foodoConfirmTitle');
                            const text = document.getElementById('foodoConfirmMessage');
                            const accept = document.getElementById('foodoConfirmAccept');
                            const cancel = document.getElementById('foodoConfirmCancel');

                            title.textContent = options.title || 'Please confirm';
                            text.textContent = message;
                            accept.textContent = options.acceptText || 'Confirm';
                            accept.className = 'btn btn-sm ' + (options.acceptClass || 'btn-danger');

                            const finish = (confirmed) => {
                                accept.removeEventListener('click', onAccept);
                                modalElement.removeEventListener('hidden.bs.modal', onCancel);
                                resolve(confirmed);
                            };
                            const onAccept = () => {
                                modal.hide();
                                finish(true);
                            };
                            const onCancel = () => finish(false);

                            accept.addEventListener('click', onAccept);
                            modalElement.addEventListener('hidden.bs.modal', onCancel, { once: true });
                            modal.show();
                        });
                    };

                    document.addEventListener('submit', function (event) {
                        const form = event.target;
                        if (!form.matches('[data-confirm]') || form.dataset.confirmed === 'true') {
                            return;
                        }

                        event.preventDefault();
                        window.foodoConfirm(form.dataset.confirm, {
                            title: form.dataset.confirmTitle || 'Please confirm',
                            acceptText: form.dataset.confirmAccept || 'Confirm',
                            acceptClass: form.dataset.confirmClass || 'btn-danger',
                        }).then((confirmed) => {
                            if (!confirmed) return;
                            form.dataset.confirmed = 'true';
                            form.submit();
                        });
                    });
                    </script>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-progress bg-{{ $type['color'] }}"></div>
            </div>
        @endif
    @endforeach

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="toast toast-auto show border-0 border-start border-4 border-danger shadow"
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex align-items-start p-3">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-3"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold">Unable to save</div>
                    <div class="small text-secondary mb-1">Please correct the following:</div>
                    <ul class="small mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-progress bg-danger"></div>
        </div>
    @endif

</div>