<script>
  (function () {
    const endpoint = "<?= site_url('stats') ?>";
    const selectState = document.getElementById('state');
    const selectCity = document.getElementById('city');
    const hospitalPanel = document.getElementById('hospital-panel');
    const hospitalCollapseEl = document.getElementById('hospital-collapse');

    const hospitalTableWrapper = document.getElementById('hospital-table-wrapper');
    const hospitalToggleButton = hospitalPanel ? hospitalPanel.querySelector('button[data-bs-target="#hospital-collapse"]') : null;
    const hospitalList = document.getElementById('hospital-list');
    const hospitalHint = document.getElementById('hospital-hint');
    const hospitalCount = document.getElementById('hospital-count');
    const selectedRegion = document.getElementById('selected-region');
    const targets = {
      states: document.querySelector('[data-stat="states"]'),
      cities: document.querySelector('[data-stat="cities"]'),
      hospitals: document.querySelector('[data-stat="hospitals"]'),
      beneficiaries: document.querySelector('[data-stat="beneficiaries"]'),
    };
    const timestamp = document.querySelector('[data-stat="timestamp"]');
    const paginationWrapper = document.getElementById('hospital-pagination');
    const paginationRange = document.getElementById('hospital-range');
    const paginationPrev = document.getElementById('hospital-prev');
    const paginationNext = document.getElementById('hospital-next');
    let currentPage = 1;
    const DEFAULT_STATE_ID = '15';
    const formatNumber = (value) => Number(value ?? 0).toLocaleString('en-IN');

    async function refreshStats() {
      try {
        const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
          return;
        }
        const payload = await response.json();
        Object.entries(targets).forEach(([key, el]) => {
          if (el && Object.prototype.hasOwnProperty.call(payload, key)) {
            el.textContent = formatNumber(payload[key]);
          }
        });
        if (timestamp && payload.generatedAt) {
          timestamp.textContent = new Date(payload.generatedAt).toLocaleString('en-IN');
        }
      } catch (error) {
        console.error('Unable to refresh stats', error);
      }
    }

    async function fetchStates() {
      if (!selectState) {
        return;
      }
      try {
        const response = await fetch("<?= site_url('hospitals/states') ?>");
        if (!response.ok) {
          throw new Error('Unable to load states');
        }
        const data = await response.json();
        selectState.innerHTML = '<option value="">-- Select State --</option>';
        data.forEach((state) => {
          const option = document.createElement('option');
          option.value = state.state_id;
          option.textContent = state.state_name;
          selectState.appendChild(option);
        });
        const hasDefaultState = data.some((state) => String(state.state_id) === DEFAULT_STATE_ID);
        if (hasDefaultState) {
          selectState.value = DEFAULT_STATE_ID;
          fetchCities(DEFAULT_STATE_ID);
          fetchHospitals({ state_id: DEFAULT_STATE_ID, city_id: '' });
        }
      } catch (error) {
        hospitalHint.textContent = 'Unable to load states right now. Please try again later.';
      }
    }

    async function fetchCities(stateId) {
      if (!selectCity) {
        return;
      }

      if (!stateId) {
        selectCity.innerHTML = '<option value="">-- Select City --</option>';
        selectCity.disabled = true;
        return;
      }
      try {
        const response = await fetch(`<?= site_url('hospitals/cities') ?>/${stateId}`);
        if (!response.ok) {
          throw new Error('Unable to load cities');
        }
        const data = await response.json();
        selectCity.innerHTML = '<option value="">-- Select City --</option>';
        data.forEach((city) => {
          const option = document.createElement('option');
          option.value = city.city_id;
          option.textContent = city.city_name;
          selectCity.appendChild(option);
        });
        selectCity.disabled = data.length === 0;
        hospitalHint.textContent = data.length === 0
          ? 'No cities found for the selected state.'
          : 'Select a city to view empanelled hospitals.';
      } catch (error) {
        hospitalHint.textContent = 'Unable to load cities right now. Please try again later.';
      }
    }

    function resolveRegionLabel() {
      const stateOption = selectState.options[selectState.selectedIndex];
      const cityOption = selectCity.options[selectCity.selectedIndex];
      const stateLabel = stateOption ? stateOption.text : 'Selected State';
      if (!selectCity.disabled && selectCity.value && cityOption) {
        const cityLabel = cityOption.text;
        return `${cityLabel}, ${stateLabel}`;
      }
      return stateLabel;
    }

    async function fetchHospitals(params, page = 1) {
      currentPage = Math.max(1, page);
      if (!hospitalPanel || !hospitalCollapseEl) {
        return;
      }

      const collapseInstance = bootstrap.Collapse.getOrCreateInstance(hospitalCollapseEl, { toggle: false });

      if (!params.state_id) {
        hospitalPanel.classList.add('d-none');
        hospitalTableWrapper.classList.add('d-none');
        paginationWrapper?.classList.add('d-none');
        hospitalList.innerHTML = '';
        hospitalHint.textContent = 'Select a state and city to view empanelled hospitals.';
        hospitalCount.textContent = '0';
        collapseInstance.hide();
        hospitalToggleButton?.classList.add('collapsed');
        hospitalToggleButton?.setAttribute('aria-expanded', 'false');
        return;
      }

      hospitalPanel.classList.remove('d-none');
      hospitalTableWrapper.classList.add('d-none');
      paginationWrapper?.classList.add('d-none');
      hospitalList.innerHTML = '';
      hospitalHint.textContent = 'Loading hospitals...';

      try {
        const PAGE_SIZE = 50;
        const start = (currentPage - 1) * PAGE_SIZE;
        const query = new URLSearchParams({
          state_id: params.state_id,
          city_id: params.city_id || '',
          start: start.toString(),
          length: PAGE_SIZE.toString(),
        });
        const response = await fetch(`<?= site_url('hospitals/list') ?>?${query.toString()}`, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
          throw new Error('Unable to load hospitals');
        }
        const data = await response.json();

        hospitalList.innerHTML = '';
        const rows = Array.isArray(data.data) ? data.data : [];
        const totalCount = Number(data.recordsFiltered ?? data.recordsTotal ?? rows.length ?? 0);
        hospitalCount.textContent = formatNumber(totalCount);
        selectedRegion.textContent = resolveRegionLabel();

        if (rows.length === 0) {
          hospitalHint.textContent = 'No hospitals found for the selected filters.';
          collapseInstance.hide();
          hospitalToggleButton?.classList.add('collapsed');
          hospitalToggleButton?.setAttribute('aria-expanded', 'false');
          return;
        }

        rows.forEach((row, index) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${start + index + 1}</td>
            <td>${row.CARENAME ?? ''}</td>
            <td>${row.city ?? ''}</td>
            <td>${row.state ?? ''}</td>
            <td>
              <div>${row.CAREPHONE ?? ''}</div>
              ${row.CAREEMAIL ? `<a href="mailto:${row.CAREEMAIL}">${row.CAREEMAIL}</a>` : ''}
            </td>`;
          hospitalList.appendChild(tr);
        });

        hospitalHint.textContent = `Showing ${formatNumber(rows.length)} of ${formatNumber(totalCount)} hospitals for the selected filters.`;
        hospitalTableWrapper.classList.remove('d-none');
        if (totalCount > rows.length) {
          const startDisplay = start + 1;
          const endDisplay = Math.min(start + rows.length, totalCount);
          paginationRange.textContent = `Showing ${formatNumber(startDisplay)}–${formatNumber(endDisplay)} of ${formatNumber(totalCount)}`;
          paginationPrev.disabled = currentPage === 1;
          paginationNext.disabled = endDisplay >= totalCount;
          paginationWrapper.classList.remove('d-none');
        } else {
          paginationWrapper?.classList.add('d-none');
        }
        collapseInstance.hide();
        hospitalToggleButton?.classList.add('collapsed');
        hospitalToggleButton?.setAttribute('aria-expanded', 'false');
      } catch (error) {
        hospitalTableWrapper.classList.add('d-none');
        hospitalList.innerHTML = '';
        hospitalCount.textContent = '0';
        hospitalHint.textContent = 'Unable to load hospitals right now. Please try again later.';
        paginationWrapper?.classList.add('d-none');
        collapseInstance.hide();
        hospitalToggleButton?.classList.add('collapsed');
        hospitalToggleButton?.setAttribute('aria-expanded', 'false');
      }
    }

    hospitalCount?.addEventListener('click', (event) => {
      event.stopPropagation();
      hospitalToggleButton?.click();
    });

    selectState?.addEventListener('change', (event) => {
      const stateId = event.target.value;
      fetchCities(stateId);
      fetchHospitals({ state_id: stateId, city_id: '' }, 1);
    });

    selectCity?.addEventListener('change', (event) => {
      const cityId = event.target.value;
      fetchHospitals({ state_id: selectState.value, city_id: cityId }, 1);
    });

    paginationPrev?.addEventListener('click', () => {
      if (currentPage > 1) {
        fetchHospitals({ state_id: selectState.value, city_id: selectCity.value || '' }, currentPage - 1);
      }
    });

    paginationNext?.addEventListener('click', () => {
      fetchHospitals({ state_id: selectState.value, city_id: selectCity.value || '' }, currentPage + 1);
    });

    refreshStats();
    fetchStates();
  })();
</script>
