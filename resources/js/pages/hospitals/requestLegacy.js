export default function initHospitalRequestLegacy(root) {
    const form = root.querySelector('#hospitalRequestForm');
    if (!form) {
        return;
    }

    const duplicateAlert = root.querySelector('#duplicateAlert');
    const hospitalInput = root.querySelector('#hospital_name');
    const submitButton = root.querySelector('#submitButton');
    const stateSelect = root.querySelector('#state_id');
    const requestedStateInput = root.querySelector('#requested_state_name');
    const isNewStateCheckbox = root.querySelector('#is_new_state');
    const cityInput = root.querySelector('#city_id');
    const requestedCityInput = root.querySelector('#requested_city_name');
    const isNewCityCheckbox = root.querySelector('#is_new_city');
    const duplicateEndpoint = root.dataset.duplicateEndpoint;

    if (!duplicateEndpoint) {
        console.warn('Hospital legacy module missing duplicate endpoint.');
    }

    const toggleLinkedInputs = () => {
        if (stateSelect && requestedStateInput && isNewStateCheckbox) {
            if (isNewStateCheckbox.checked) {
                requestedStateInput.removeAttribute('disabled');
                stateSelect.setAttribute('disabled', 'disabled');
            } else {
                requestedStateInput.value = '';
                requestedStateInput.setAttribute('disabled', 'disabled');
                stateSelect.removeAttribute('disabled');
            }
        }

        if (cityInput && requestedCityInput && isNewCityCheckbox) {
            if (isNewCityCheckbox.checked) {
                requestedCityInput.removeAttribute('disabled');
                cityInput.setAttribute('disabled', 'disabled');
            } else {
                requestedCityInput.value = '';
                requestedCityInput.setAttribute('disabled', 'disabled');
                cityInput.removeAttribute('disabled');
            }
        }
    };

    toggleLinkedInputs();
    stateSelect?.addEventListener('change', () => {
        if (stateSelect.value) {
            isNewStateCheckbox.checked = false;
        }
        toggleLinkedInputs();
    });
    requestedStateInput?.addEventListener('input', () => {
        if (requestedStateInput.value.trim().length) {
            isNewStateCheckbox.checked = true;
        }
        toggleLinkedInputs();
    });
    isNewStateCheckbox?.addEventListener('change', toggleLinkedInputs);
    requestedCityInput?.addEventListener('input', () => {
        if (requestedCityInput.value.trim().length) {
            isNewCityCheckbox.checked = true;
        }
        toggleLinkedInputs();
    });
    isNewCityCheckbox?.addEventListener('change', toggleLinkedInputs);

    const showDuplicateAlert = (message, tone = 'warning') => {
        if (!duplicateAlert) {
            return;
        }
        duplicateAlert.textContent = message;
        duplicateAlert.classList.remove('alert', 'alert-warning', 'alert-danger');
        duplicateAlert.classList.add('alert', tone === 'danger' ? 'alert-danger' : 'alert-warning');
        duplicateAlert.removeAttribute('hidden');
    };

    const hideDuplicateAlert = () => {
        if (!duplicateAlert) {
            return;
        }
        duplicateAlert.textContent = '';
        duplicateAlert.setAttribute('hidden', 'hidden');
        duplicateAlert.classList.remove('alert', 'alert-warning', 'alert-danger');
    };

    const checkDuplicate = async () => {
        if (!duplicateEndpoint || !hospitalInput || !hospitalInput.value.trim()) {
            return;
        }

        const formData = new FormData(form);

        try {
            submitButton?.setAttribute('disabled', 'disabled');
            hideDuplicateAlert();

            const response = await fetch(duplicateEndpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to verify duplicate hospital at this time.');
            }

            const result = await response.json();

            if (result.duplicate) {
                showDuplicateAlert(result.hint || 'Hospital appears to be already present in the system.');
            } else {
                hideDuplicateAlert();
            }
        } catch (error) {
            showDuplicateAlert(error.message, 'danger');
        } finally {
            submitButton?.removeAttribute('disabled');
        }
    };

    hospitalInput?.addEventListener('blur', checkDuplicate);
    form?.addEventListener('submit', () => {
        submitButton?.setAttribute('disabled', 'disabled');
    });
}
