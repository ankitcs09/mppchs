const STATUS_BADGES = {
    approved: 'bg-success',
    rejected: 'bg-danger',
    in_review: 'bg-warning text-dark',
    pending: 'bg-info text-dark',
};

function normaliseStatus(status) {
    const raw = (status || 'pending').toString().trim();
    const label = raw.charAt(0).toUpperCase() + raw.slice(1).toLowerCase();
    const key = raw.toLowerCase();
    return { key, label, badge: STATUS_BADGES[key] || 'bg-secondary' };
}

export default function initHospitalRequest(root) {
    const $ = window.jQuery;
    if (!$ || !$.fn) {
        console.warn('Hospital request module requires jQuery.');
        return;
    }

    const $root = $(root);
    const $form = $root.find('#hospitalRequestForm');

    if (!$form.length) {
        return;
    }

    const statesEndpoint = root.dataset.statesEndpoint;
    const citiesEndpoint = root.dataset.citiesEndpoint;
    const duplicateEndpoint = root.dataset.duplicateEndpoint;
    const submitEndpoint = root.dataset.submitEndpoint;
    const defaultStateName = (root.dataset.defaultState || 'Madhya Pradesh').toLowerCase();

    if (!statesEndpoint || !citiesEndpoint || !duplicateEndpoint || !submitEndpoint) {
        console.warn('Hospital request module missing endpoint configuration.');
        return;
    }

    const $state = $form.find('#requestState');
    const $city = $form.find('#requestCity');
    const $hospitalName = $form.find('#hospitalName');
    const $duplicateHint = $root.find('#duplicateHint');
    const $alert = $root.find('#requestAlert');
    const $submitButton = $form.find('button[type="submit"]');
    const csrfField = $form.find('input[type="hidden"][name]').first();

    let csrfName = csrfField.attr('name') || '';
    let csrfHash = csrfField.val() || '';
    let hasBlockingDuplicate = false;

    function updateCsrf(value) {
        if (!value) {
            return;
        }
        csrfHash = value;
        if (csrfField.length) {
            csrfField.val(csrfHash);
        }
    }

    function setAlert(type, message) {
        $alert
            .removeClass('d-none alert-success alert-danger alert-warning')
            .addClass(`alert-${type}`)
            .text(message);
    }

    function clearAlert() {
        $alert.addClass('d-none').text('');
    }

    function setDuplicateHint(tone, message) {
        $duplicateHint
            .removeClass('d-none text-muted text-success text-warning text-danger')
            .addClass(`text-${tone}`)
            .text(message);
    }

    function resetDuplicateState() {
        $duplicateHint.addClass('d-none').text('');
        hasBlockingDuplicate = false;
        $submitButton.prop('disabled', false);
    }

    function fetchStates() {
        $state.prop('disabled', true);

        return $.getJSON(statesEndpoint)
            .done((response) => {
                $state.prop('disabled', false);
                $state.html('<option value="">Select state</option>');

                const states = Array.isArray(response) ? response : (response.states || []);

                states.forEach((state) => {
                    $('<option/>', { value: state.state_id, text: state.state_name }).appendTo($state);
                });

                if (!$state.val()) {
                    const defaultMatch = states.find((state) => (state.state_name || '').toLowerCase() === defaultStateName);
                    if (defaultMatch) {
                        $state.val(String(defaultMatch.state_id));
                    }
                }
            })
            .fail(() => {
                $state.prop('disabled', false);
                setAlert('danger', 'Unable to load states right now. Please try again later.');
            });
    }

    function fetchCities(stateId) {
        if (!stateId) {
            $city.prop('disabled', true).html('<option value="">Select city</option>');
            return $.Deferred().resolve().promise();
        }

        $city.prop('disabled', true);
        const url = `${citiesEndpoint.replace(/\/+$/u, '')}/${encodeURIComponent(stateId)}`;

        return $.getJSON(url)
            .done((response) => {
                $city.prop('disabled', false);
                $city.html('<option value="">Select city</option>');
                (response.cities || []).forEach((city) => {
                    $('<option/>', { value: city.city_id, text: city.city_name }).appendTo($city);
                });
            })
            .fail(() => {
                $city.prop('disabled', false);
                setAlert('danger', 'Unable to load cities for the selected state.');
            });
    }

    function appendRequestRow(referenceNumber, payload, createdAtDisplay, status) {
        const $table = $('#userRequestsTable');
        if (!$table.length) {
            return;
        }

        const $tbody = $table.find('tbody');
        $('#noRequestsRow').remove();

        const { label, badge } = normaliseStatus(status);
        const createdDisplay = createdAtDisplay || new Date().toLocaleString();

        const $row = $('<tr/>')
            .append($('<td/>').text(referenceNumber || '-'))
            .append($('<td/>').text(payload.hospital_name || '-'))
            .append($('<td/>').text($state.find('option:selected').text() || '-'))
            .append($('<td/>').text($city.find('option:selected').text() || '-'))
            .append($('<td/>').html(`<span class="badge ${badge}">${label}</span>`))
            .append($('<td/>').text(createdDisplay));

        $tbody.prepend($row);
    }

    function checkDuplicate() {
        const hospitalValue = ($hospitalName.val() || '').trim();

        if (hospitalValue === '' || $state.val() === '' || $city.val() === '') {
            resetDuplicateState();
            return;
        }

        $submitButton.prop('disabled', true);

        const payload = {
            state_id: $state.val(),
            city_id: $city.val(),
            hospital_name: hospitalValue,
        };

        if (csrfName) {
            payload[csrfName] = csrfHash;
        }

        $.ajax({
            method: 'POST',
            url: duplicateEndpoint,
            dataType: 'json',
            data: payload,
        })
            .done((response) => {
                updateCsrf(response.csrfToken);

                if (response.message) {
                    setDuplicateHint('warning', response.message);
                    hasBlockingDuplicate = true;
                    $submitButton.prop('disabled', true);
                } else {
                    setDuplicateHint('success', 'No duplicate found. You can proceed with your request.');
                    hasBlockingDuplicate = false;
                    $submitButton.prop('disabled', false);
                }
            })
            .fail((xhr) => {
                const json = xhr.responseJSON || {};
                updateCsrf(json.csrfToken || csrfHash);
                const message = json.message || 'Unable to verify duplicates at the moment.';

                setDuplicateHint('warning', message);

                if (xhr.status === 401) {
                    setAlert('danger', message);
                    $submitButton.prop('disabled', true);
                    hasBlockingDuplicate = true;
                    return;
                }

                hasBlockingDuplicate = false;
                $submitButton.prop('disabled', false);
            });
    }

    $state.on('change', () => {
        fetchCities($state.val()).then(() => {
            resetDuplicateState();
        });
    });

    $city.on('change', () => {
        resetDuplicateState();
    });

    $hospitalName.on('blur', checkDuplicate);

    $form.on('submit', (event) => {
        event.preventDefault();
        clearAlert();

        if (hasBlockingDuplicate) {
            setAlert('danger', 'Please resolve the duplicate hospital warning before submitting.');
            return;
        }

        const payload = {
            state_id: $state.val(),
            city_id: $city.val(),
            hospital_name: $form.find('[name="hospital_name"]').val(),
            contact_person: $form.find('[name="contact_person"]').val(),
            contact_phone: $form.find('[name="contact_phone"]').val(),
            contact_email: $form.find('[name="contact_email"]').val(),
            address: $form.find('[name="address"]').val(),
            notes: $form.find('[name="notes"]').val(),
        };

        $.ajax({
            method: 'POST',
            url: submitEndpoint,
            contentType: 'application/json',
            data: JSON.stringify(payload),
            headers: {
                'X-CSRF-TOKEN': csrfHash,
                Accept: 'application/json',
            },
        })
            .done((response) => {
                const referenceMessage = response.referenceNumber
                    ? ` Reference number: ${response.referenceNumber}.`
                    : '';

                setAlert('success', `${response.message || 'Request submitted successfully.'}${referenceMessage}`);
                updateCsrf(response.csrfToken);
                $form[0].reset();
                $city.prop('disabled', true).html('<option value="">Select city</option>');
                resetDuplicateState();
                appendRequestRow(response.referenceNumber, payload, response.createdAtDisplay, response.status);
            })
            .fail((xhr) => {
                const json = xhr.responseJSON || {};
                updateCsrf(json.csrfToken || csrfHash);

                if (json.errors) {
                    const firstError = Object.values(json.errors)[0];
                    setAlert('danger', firstError);
                } else if (json.message) {
                    setAlert('danger', json.message);
                } else {
                    setAlert('danger', 'Unable to submit request right now. Please try again.');
                }
            });
    });

    fetchStates().then(() => {
        if ($state.val()) {
            fetchCities($state.val());
        }
    });
}
