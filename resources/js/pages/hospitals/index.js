const DEFAULT_STATE_FALLBACK = 'Madhya Pradesh';

function escapeHtml(value) {
    return (value || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[char] || char);
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/\r?\n/g, ' ');
}

function findDefaultStateId(states, defaultStateName) {
    const target = String(defaultStateName).trim().toLowerCase();
    if (!target) {
        return '';
    }
    const match = states.find((state) => String(state.state_name || '').toLowerCase() === target);
    return match ? String(match.state_id) : '';
}

export default function initHospitalsPage(root) {
    const $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.DataTable) {
        console.warn('Hospitals page: DataTables is not available.');
        return;
    }

    const $root = $(root);
    const stateFilter = $root.find('[data-role="state-filter"]');
    const cityFilter = $root.find('[data-role="city-filter"]');
    const tableElement = $root.find('[data-role="hospital-table"]');
    const searchInput = $root.find('[data-role="table-search"]');
    const clearSearchButton = $root.find('[data-action="clear-search"]');
    const filterPills = root.querySelector('[data-role="filter-pills"]');
    const filterCountBadge = root.querySelector('[data-role="filter-count"]');
    const resultCountLabel = root.querySelector('[data-role="result-count"]');
    const emptyState = root.querySelector('[data-role="empty-state"]');
    const refreshButtons = $root.find('[data-action="refresh-table"]');
    const resetButtons = $root.find('[data-action="reset-filters"]');
    const pageLengthControl = $root.find('[data-role="page-length"]');
    const loadingOverlay = root.querySelector('[data-role="table-loading"]');

    if (!stateFilter.length || !cityFilter.length || !tableElement.length) {
        console.warn('Hospitals page: Required elements missing.');
        return;
    }

    const statesEndpoint = root.dataset.statesEndpoint;
    const citiesEndpoint = root.dataset.citiesEndpoint;
    const listEndpoint = root.dataset.listEndpoint;
    const defaultStateName = root.dataset.defaultState || DEFAULT_STATE_FALLBACK;

    if (!statesEndpoint || !citiesEndpoint || !listEndpoint) {
        console.warn('Hospitals page: Endpoint configuration missing.');
        return;
    }

    let tableInstance;
    let lastKnownStateOptions = [];

    function fetchStates(applyDefault) {
        return $.getJSON(statesEndpoint)
            .done((states = []) => {
                stateFilter.empty().append('<option value="">All States</option>');
                states.forEach((state) => {
                    stateFilter.append($('<option/>', { value: state.state_id, text: state.state_name }));
                });
                lastKnownStateOptions = states;

                if (applyDefault) {
                    const defaultStateId = findDefaultStateId(states, defaultStateName);
                    if (defaultStateId) {
                        stateFilter.val(defaultStateId);
                        fetchCities(defaultStateId).done(() => {
                            if (tableInstance) {
                                tableInstance.ajax.reload();
                            }
                        });
                        return;
                    }
                }

                cityFilter.prop('disabled', true).empty().append('<option value="">All Cities</option>');

                if (applyDefault && tableInstance) {
                    tableInstance.ajax.reload();
                }
            });
    }

    function fetchCities(stateId) {
        if (!stateId) {
            cityFilter.prop('disabled', true).empty().append('<option value="">All Cities</option>');
            return $.Deferred().resolve().promise();
        }

        cityFilter.prop('disabled', false).empty().append('<option value="">Loading...</option>');

        return $.getJSON(`${citiesEndpoint}/${stateId}`)
            .done((cities = []) => {
                if (!cities.length) {
                    cityFilter.prop('disabled', true).empty().append('<option value="">No cities found</option>');
                    return;
                }

                cityFilter.prop('disabled', false).empty().append('<option value="">All Cities</option>');
                cities.forEach((city) => {
                    cityFilter.append($('<option/>', { value: city.city_id, text: city.city_name }));
                });
            });
    }

    function buildPopoverContent(button) {
        const parts = [];
        const { categoryLabel, categoryDesc, categoryRates, categoryCopay, categoryNote } = button.dataset;

        if (categoryDesc) {
            parts.push(`<p class="mb-2">${escapeHtml(categoryDesc)}</p>`);
        }
        if (categoryRates) {
            parts.push(`<p class="mb-1"><strong>Rates:</strong> ${escapeHtml(categoryRates)}</p>`);
        }
        if (categoryCopay) {
            parts.push(`<p class="mb-0"><strong>Copay:</strong> ${escapeHtml(categoryCopay)}</p>`);
        }
        if (categoryNote) {
            parts.push(`<hr class="my-2"><p class="mb-0 small text-muted">${escapeHtml(categoryNote)}</p>`);
        }

        return parts.join('') || '<p class="mb-0">Category definition is being updated.</p>';
    }

    function initialiseCategoryPopovers() {
        const { Popover } = window.bootstrap || {};
        if (!Popover) {
            return;
        }

        root.querySelectorAll('.hospital-category').forEach((button) => {
            const content = buildPopoverContent(button);
            const existing = Popover.getInstance(button);
            if (existing) {
                existing.dispose();
            }
            Popover.getOrCreateInstance(button, {
                title: button.dataset.categoryLabel || 'Hospital Category',
                content,
                html: true,
                trigger: 'focus hover',
                placement: 'auto',
            });
        });
    }

    function describeActiveFilters() {
        const filters = [];
        const selectedState = stateFilter.val();
        const selectedCity = cityFilter.val();
        const searchTerm = searchInput.val()?.trim();

        if (selectedState) {
            const label = stateFilter.find('option:selected').text();
            filters.push({ key: 'state', label: 'State', value: label });
        }

        if (selectedCity) {
            const label = cityFilter.find('option:selected').text();
            filters.push({ key: 'city', label: 'City', value: label });
        }

        if (searchTerm) {
            filters.push({ key: 'search', label: 'Search', value: searchTerm });
        }

        return filters;
    }

    function updateFilterMeta() {
        if (!filterCountBadge) {
            return;
        }
        const filters = describeActiveFilters();
        filterCountBadge.textContent = String(filters.length);

        if (!filterPills) {
            return;
        }

        if (!filters.length) {
            filterPills.hidden = true;
            filterPills.innerHTML = '';
            return;
        }

        filterPills.hidden = false;
        filterPills.innerHTML = filters
            .map(
                (filter) =>
                    `<span class="filter-pill" data-filter-key="${filter.key}">
                        <span class="filter-pill__label">${escapeHtml(filter.label)}:</span>
                        <span class="filter-pill__value">${escapeHtml(filter.value)}</span>
                        <button type="button" class="filter-pill__close" aria-label="Remove ${escapeHtml(filter.label)} filter">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </span>`
            )
            .join('');
    }

    function updateResultCount(info) {
        if (!resultCountLabel) {
            return;
        }
        if (!info) {
            resultCountLabel.textContent = 'Loading...';
            return;
        }
        const { recordsDisplay, recordsTotal } = info;
        const totalText = recordsTotal === recordsDisplay ? '' : ` of ${recordsTotal}`;
        resultCountLabel.textContent = `${recordsDisplay}${totalText} hospitals`;
    }

    function toggleEmptyState(show) {
        if (!emptyState) {
            return;
        }
        emptyState.hidden = !show;
        tableElement.closest('.table-responsive').attr('aria-hidden', show ? 'true' : 'false');
    }

    const columnLabels = ['Hospital', 'Category', 'State', 'City', 'Phone', 'Email'];

    tableInstance = tableElement.DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 400,
        dom: 'rt<"datatable-footer d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3"ip>',
        order: [[0, 'asc']],
        lengthChange: false,
        createdRow(row) {
            columnLabels.forEach((label, index) => {
                const cell = row.cells[index];
                if (cell) {
                    cell.setAttribute('data-label', label);
                }
            });
        },
        ajax(data, callback) {
            const payload = {
                ...data,
                state_id: stateFilter.val(),
                city_id: cityFilter.val(),
                search: searchInput.val(),
            };

            $.getJSON(listEndpoint, payload).done((json) => {
                callback(json);
            });
        },
        columns: [
            { data: 'CARENAME' },
            {
                data: 'category_label',
                orderable: false,
                searchable: false,
                render(data, type, row) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    const label = escapeHtml(row.category_label || 'To be assigned');
                    const desc = escapeAttribute(row.category_desc || '');
                    const rates = escapeAttribute(row.category_rates || '');
                    const copay = escapeAttribute(row.category_copay || '');
                    const note = escapeAttribute(row.category_note || '');
                    const badgeClass = row.category_label ? 'text-bg-primary' : 'text-bg-secondary';

                    return `<button type="button" class="badge ${badgeClass} hospital-category" data-category-label="${label}" data-category-desc="${desc}" data-category-rates="${rates}" data-category-copay="${copay}" data-category-note="${note}" aria-label="Hospital category ${label}" tabindex="0">${label}</button>`;
                },
            },
            { data: 'state' },
            { data: 'city' },
            { data: 'CAREPHONE' },
            {
                data: 'CAREEMAIL',
                render(value) {
                    if (!value) {
                        return '';
                    }
                    const safeValue = escapeHtml(value);
                    return `<a href="mailto:${safeValue}">${safeValue}</a>`;
                },
            },
        ],
    });

    tableInstance.on('draw', () => {
        initialiseCategoryPopovers();
        updateFilterMeta();
        const info = tableInstance.page.info();
        updateResultCount(info);
        toggleEmptyState((info?.recordsDisplay ?? 0) === 0);
    });

    stateFilter.on('change', () => {
        fetchCities(stateFilter.val()).done(() => {
            tableInstance.ajax.reload();
        });
    });

    cityFilter.on('change', () => {
        tableInstance.ajax.reload();
    });

    searchInput.on('input', () => {
        const term = searchInput.val();
        const hasValue = Boolean(term && term.trim().length);
        if (clearSearchButton.length) {
            clearSearchButton[0].hidden = !hasValue;
        }
        tableInstance.search(term).draw();
    });

    if (clearSearchButton.length) {
        clearSearchButton.on('click', () => {
            searchInput.val('');
            clearSearchButton[0].hidden = true;
            tableInstance.search('').draw();
        });
    }

    refreshButtons.on('click', () => {
        tableInstance.ajax.reload(null, false);
    });

    resetButtons.on('click', () => {
        stateFilter.val('');
        cityFilter.prop('disabled', true).empty().append('<option value="">All Cities</option>');
        searchInput.val('');
        if (clearSearchButton.length) {
            clearSearchButton[0].hidden = true;
        }
        tableInstance.search('');
        tableInstance.ajax.reload();
        updateFilterMeta();
    });

    if (filterPills) {
        filterPills.addEventListener('click', (event) => {
            const pill = event.target.closest('.filter-pill');
            if (!pill) {
                return;
            }
            const key = pill.dataset.filterKey;
            if (key === 'state') {
                stateFilter.val('');
                cityFilter.prop('disabled', true).empty().append('<option value="">All Cities</option>');
            } else if (key === 'city') {
                cityFilter.val('');
            } else if (key === 'search') {
                searchInput.val('');
                if (clearSearchButton.length) {
                    clearSearchButton[0].hidden = true;
                }
                tableInstance.search('');
            }
            tableInstance.ajax.reload();
            updateFilterMeta();
        });
    }

    if (pageLengthControl.length) {
        pageLengthControl.on('change', () => {
            const value = parseInt(pageLengthControl.val(), 10) || 10;
            tableInstance.page.len(value).draw();
        });
    }

    if (loadingOverlay) {
        tableElement.on('processing.dt', (_event, _settings, processing) => {
            loadingOverlay.hidden = !processing;
        });
    }

    fetchStates(true).then(() => {
        initialiseCategoryPopovers();
        updateFilterMeta();
    });
}
