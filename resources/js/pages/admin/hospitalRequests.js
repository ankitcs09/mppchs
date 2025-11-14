export default function initAdminHospitalRequests(root) {
    const table = root.querySelector('table');
    const modalElement = document.getElementById('remarksModal');

    if (!table || !modalElement) {
        return;
    }

    const bootstrapInstance = window.bootstrap;
    if (!bootstrapInstance || !bootstrapInstance.Modal) {
        console.warn('Bootstrap Modal is required for admin hospital requests.');
        return;
    }

    const remarksModal = bootstrapInstance.Modal.getOrCreateInstance(modalElement);
    const modalRequestId = modalElement.querySelector('#modalRequestId');
    const modalAction = modalElement.querySelector('#modalAction');
    const remarksInput = modalElement.querySelector('#remarksInput');
    const submitButton = modalElement.querySelector('#modalSubmitButton');
    const errorBox = modalElement.querySelector('#remarksError');
    const csrfField = modalElement.querySelector('input[type="hidden"][name]');
    const statusEndpointBase = root.dataset.statusEndpoint || '';

    let pendingRow;

    const resetModal = () => {
        pendingRow = undefined;
        if (modalRequestId) modalRequestId.value = '';
        if (modalAction) modalAction.value = '';
        if (remarksInput) remarksInput.value = '';
        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
        }
    };

    const openModal = (row, action) => {
        pendingRow = row;
        if (modalRequestId) modalRequestId.value = row.dataset.requestId || '';
        if (modalAction) modalAction.value = action;
        if (remarksInput) remarksInput.value = '';
        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
        }
        remarksModal.show();
    };

    table.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const action = target.dataset.action;
        if (!action) {
            return;
        }

        const row = target.closest('tr[data-request-id]');
        if (!row) {
            return;
        }

        openModal(row, action);
    });

    const postAction = async () => {
        if (!modalRequestId || !modalAction || !modalRequestId.value || !modalAction.value || !statusEndpointBase) {
            return;
        }

        submitButton?.setAttribute('disabled', 'disabled');
        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
        }

        const url = `${statusEndpointBase.replace(/\/+$/u, '')}/${encodeURIComponent(modalRequestId.value)}/status`;
        const formData = new FormData();
        formData.append('action', modalAction.value);
        formData.append('remarks', remarksInput?.value || '');

        if (csrfField) {
            formData.append(csrfField.name, csrfField.value ?? '');
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                let message = 'Action failed';
                try {
                    const data = await response.json();
                    if (data?.message) {
                        message = data.message;
                    }
                } catch (parseError) {
                    // Ignore JSON parse errors
                }
                throw new Error(message);
            }

            remarksModal.hide();
            if (pendingRow) {
                pendingRow.classList.add('table-success');
            }
            window.location.reload();
        } catch (error) {
            if (errorBox) {
                errorBox.textContent = error instanceof Error ? error.message : 'Action failed';
                errorBox.classList.remove('d-none');
            }
        } finally {
            submitButton?.removeAttribute('disabled');
        }
    };

    submitButton?.addEventListener('click', postAction);
    modalElement.addEventListener('hidden.bs.modal', resetModal);
}
