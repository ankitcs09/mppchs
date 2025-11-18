document.addEventListener('DOMContentLoaded', () => {
  const manager = document.querySelector('[data-role="role-manager"]');
  if (!manager) {
    return;
  }

  const checkboxes = Array.from(manager.querySelectorAll('[data-role="role-checkbox"]'));
  const summaryList = manager.querySelector('[data-role="role-summary-list"]');
  const summaryEmpty = manager.querySelector('[data-role="role-summary-empty"]');
  const presetSelect = manager.querySelector('[data-role="role-preset"]');
  const clearButton = manager.querySelector('[data-role="role-clear"]');
  const isReadOnly = manager.dataset.roleSelf === '1';

  const refreshCards = () => {
    checkboxes.forEach((checkbox) => {
      const card = checkbox.closest('[data-role="role-card"]');
      if (!card) {
        return;
      }

      card.classList.toggle('border-primary', checkbox.checked);
      card.classList.toggle('border-2', checkbox.checked);
      card.classList.toggle('shadow-sm', checkbox.checked);
    });
  };

  const refreshSummary = () => {
    if (!summaryList || !summaryEmpty) {
      return;
    }

    summaryList.innerHTML = '';
    const selected = checkboxes.filter((checkbox) => checkbox.checked);

    if (selected.length === 0) {
      summaryEmpty.classList.remove('d-none');
      summaryList.classList.add('d-none');
      return;
    }

    summaryEmpty.classList.add('d-none');
    summaryList.classList.remove('d-none');

    selected.forEach((checkbox) => {
      const item = document.createElement('li');
      item.className = 'mb-2';

      const title = document.createElement('div');
      title.className = 'fw-semibold';
      title.textContent = checkbox.dataset.roleName || checkbox.value;

      const description = document.createElement('div');
      description.className = 'text-muted';
      description.textContent =
        checkbox.dataset.roleDescription || 'Grants access to relevant modules.';

      item.appendChild(title);
      item.appendChild(description);
      summaryList.appendChild(item);
    });
  };

  const refreshUI = () => {
    refreshCards();
    refreshSummary();
  };

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', refreshUI);
  });

  if (!isReadOnly) {
    presetSelect?.addEventListener('change', (event) => {
      const select = event.target;
      const [option] = select.selectedOptions;
      if (!option) {
        return;
      }

      const rolesAttr = option.dataset.roles;
      if (!rolesAttr) {
        return;
      }

      let roles = [];
      try {
        roles = JSON.parse(rolesAttr);
      } catch (error) {
        console.warn('Unable to parse preset payload', error);
      }

      if (!Array.isArray(roles) || roles.length === 0) {
        return;
      }

      checkboxes.forEach((checkbox) => {
        checkbox.checked = roles.includes(checkbox.value);
      });

      refreshUI();
    });

    clearButton?.addEventListener('click', () => {
      checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
      });

      if (presetSelect) {
        presetSelect.value = '';
      }

      refreshUI();
    });
  }

  refreshUI();
});

