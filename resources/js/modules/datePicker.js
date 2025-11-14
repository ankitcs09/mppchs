import flatpickr from 'flatpickr';

const DATE_SELECTOR = '[data-date-picker]';

const normalizeBound = (value) => {
  if (!value) {
    return undefined;
  }

  if (value.toLowerCase() === 'today') {
    return 'today';
  }

  return value;
};

const enhanceInput = (input) => {
  if (!input || input.dataset.datePickerApplied === '1') {
    return;
  }

  const sizeClass = input.classList.contains('form-control-sm') ? 'form-control-sm' : '';
  const invalidClass = input.classList.contains('is-invalid') ? 'is-invalid' : '';
  const placeholder = input.dataset.datePlaceholder || input.getAttribute('placeholder') || 'DD/MM/YYYY';

  const fpInstance = flatpickr(input, {
    altInput: true,
    altInputClass: ['form-control', sizeClass, invalidClass].filter(Boolean).join(' ') || 'form-control',
    altFormat: input.dataset.altFormat || 'd/m/Y',
    allowInput: false,
    clickOpens: true,
    disableMobile: true,
    dateFormat: 'Y-m-d',
    defaultDate: input.value || undefined,
    maxDate: normalizeBound(input.dataset.maxDate),
    minDate: normalizeBound(input.dataset.minDate),
  });

  input.dataset.datePickerApplied = '1';

  const syncClasses = () => {
    if (!fpInstance.altInput) {
      return;
    }

    if (placeholder) {
      fpInstance.altInput.placeholder = placeholder;
    }

    fpInstance.altInput.classList.toggle('is-invalid', input.classList.contains('is-invalid'));
  };

  syncClasses();

  const classObserver = new MutationObserver(syncClasses);
  classObserver.observe(input, { attributes: true, attributeFilter: ['class'] });
};

const observeNewInputs = () => {
  const scan = () => {
    document.querySelectorAll(DATE_SELECTOR).forEach(enhanceInput);
  };

  scan();

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) {
          return;
        }
        if (node.matches(DATE_SELECTOR)) {
          enhanceInput(node);
        }
        node.querySelectorAll?.(DATE_SELECTOR).forEach(enhanceInput);
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
};

export function initDatePickers() {
  if (!document.body) {
    return;
  }

  observeNewInputs();
}

export default initDatePickers;
