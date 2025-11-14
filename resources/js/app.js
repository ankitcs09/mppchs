import { initThemeManager } from './modules/theme.js';
import { initAsyncFeedback } from './modules/feedback.js';
import { initFormValidation } from './modules/formValidation.js';
import initHospitalsPage from './pages/hospitals/index.js';
import initHospitalRequest from './pages/hospitals/request.js';
import initHospitalRequestLegacy from './pages/hospitals/requestLegacy.js';
import initAdminHospitalRequests from './pages/admin/hospitalRequests.js';
import initAdminChangeRequest from './pages/admin/changeRequests.js';
import { initOtpVerify } from './pages/auth/otpVerify.js';
import { initLogoutRedirect } from './modules/logoutRedirect.js';
import { initIdleTimeout } from './modules/idleTimeout.js';
import { initDatePickers } from './modules/datePicker.js';

function initSidebar() {
    const sidebar = document.querySelector('[data-role="sidebar"]');
    const toggle = document.querySelector('[data-role="sidebar-toggle"]');
    const backdrop = document.querySelector('[data-role="sidebar-backdrop"]');

    if (!sidebar || !toggle) {
        return;
    }

    const openClass = 'is-open';
    const bodyOpenClass = 'sidebar-open';
    const backdropActiveClass = 'is-active';

    const closeSidebar = ({ focusToggle = false } = {}) => {
        sidebar.classList.remove(openClass);
        document.body.classList.remove(bodyOpenClass);
        toggle.setAttribute('aria-expanded', 'false');
        if (focusToggle) {
            toggle.focus();
        }
        if (backdrop) {
            backdrop.classList.remove(backdropActiveClass);
            backdrop.hidden = true;
        }
    };

    const openSidebar = () => {
        sidebar.classList.add(openClass);
        document.body.classList.add(bodyOpenClass);
        toggle.setAttribute('aria-expanded', 'true');
        if (backdrop) {
            backdrop.classList.add(backdropActiveClass);
            backdrop.hidden = false;
        }
    };

    const toggleSidebar = () => {
        if (sidebar.classList.contains(openClass)) {
            closeSidebar();
        } else {
            openSidebar();
        }
    };

    toggle.addEventListener('click', () => {
        toggleSidebar();
    });

    if (backdrop) {
        backdrop.addEventListener('click', () => {
            closeSidebar();
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains(openClass)) {
            closeSidebar({ focusToggle: true });
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992 && sidebar.classList.contains(openClass)) {
            closeSidebar();
        }
    });

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });

    // ensure initial state is collapsed on load for small screens
    closeSidebar();
}

function initGlobalSearchShortcut() {
    const searchField = document.querySelector('[data-role="global-search"]');

    if (!searchField) {
        return;
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey) {
            event.preventDefault();
            searchField.focus();
        }

        if (event.key === 'Escape' && document.activeElement === searchField) {
            searchField.value = '';
            searchField.blur();
        }
    });
}

function initPageModules() {
    document.querySelectorAll('[data-module="hospitals-index"]').forEach((element) => {
        try {
            initHospitalsPage(element);
        } catch (error) {
            console.error('Failed to initialise hospitals module', error);
        }
    });

    document.querySelectorAll('[data-module="hospital-request"]').forEach((element) => {
        try {
            initHospitalRequest(element);
        } catch (error) {
            console.error('Failed to initialise hospital request module', error);
        }
    });

    document.querySelectorAll('[data-module="hospital-request-legacy"]').forEach((element) => {
        try {
            initHospitalRequestLegacy(element);
        } catch (error) {
            console.error('Failed to initialise legacy hospital request module', error);
        }
    });

    document.querySelectorAll('[data-module="admin-hospital-requests"]').forEach((element) => {
        try {
            initAdminHospitalRequests(element);
        } catch (error) {
            console.error('Failed to initialise admin hospital requests module', error);
        }
    });

    try {
        initOtpVerify();
    } catch (error) {
        console.error('Failed to initialise OTP verification module', error);
    }
}

function initScrollAnimations() {
    if (window.AOS && typeof window.AOS.init === 'function') {
        window.AOS.init({ once: true });
    }
}

function initClaimDetailToggles() {
    const toggles = Array.from(document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target^="#claimDetails"]'));

    if (!toggles.length) {
        return;
    }

    toggles.forEach((toggle) => {
        const selector = toggle.getAttribute('data-bs-target');
        const target = selector ? document.querySelector(selector) : null;
        if (!target) {
            return;
        }
        const originalLabel = toggle.textContent.trim() || 'Details';
        toggle.dataset.originalLabel = originalLabel;

        target.addEventListener('shown.bs.collapse', () => {
            toggle.textContent = 'Hide details';
            toggle.setAttribute('aria-expanded', 'true');
        });

        target.addEventListener('hidden.bs.collapse', () => {
            toggle.textContent = toggle.dataset.originalLabel || 'Details';
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initThemeManager();
    initAsyncFeedback();
    initSidebar();
    initGlobalSearchShortcut();
    initFormValidation();
    initPageModules();
    initScrollAnimations();
    initClaimDetailToggles();
    initFilterSheet();
    initLogoutRedirect();
    initIdleTimeout();
    initDatePickers();
});

document.addEventListener('click', (event) => {
    const clearButton = event.target.closest('[data-action="clear-search"]');
    if (!clearButton) {
        return;
    }
    const input = clearButton.closest('.filter-search')?.querySelector('input');
    if (!input) {
        return;
    }
    input.value = '';
    clearButton.hidden = true;
    input.focus();
});

document.addEventListener('input', (event) => {
    const searchInput = event.target.closest('.filter-search__input');
    if (!searchInput) {
        return;
    }
    const clearButton = searchInput.parentElement.querySelector('[data-action="clear-search"]');
    if (clearButton) {
        clearButton.hidden = searchInput.value === '';
    }
});

function initFilterSheet() {
    const sheet = document.querySelector('[data-filter-sheet]');
    const openButton = document.querySelector('[data-filter-sheet-open]');
    const closeButton = sheet?.querySelector('[data-filter-sheet-close]');

    if (!sheet || !openButton || !closeButton) {
        return;
    }

    const trapFocus = (event) => {
        if (!sheet.classList.contains('is-open')) {
            return;
        }
        const focusable = sheet.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) {
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.key === 'Tab') {
            if (event.shiftKey && document.activeElement === first) {
                last.focus();
                event.preventDefault();
            } else if (!event.shiftKey && document.activeElement === last) {
                first.focus();
                event.preventDefault();
            }
        }
        if (event.key === 'Escape') {
            closeSheet();
        }
    };

    const openSheet = () => {
        sheet.classList.add('is-open');
        document.body.classList.add('filters-open');
        const firstInput = sheet.querySelector('input, select, button');
        firstInput?.focus();
        document.addEventListener('keydown', trapFocus);
    };

    const closeSheet = () => {
        sheet.classList.remove('is-open');
        document.body.classList.remove('filters-open');
        openButton.focus();
        document.removeEventListener('keydown', trapFocus);
    };

    openButton.addEventListener('click', () => {
        openSheet();
    });

    closeButton.addEventListener('click', () => {
        closeSheet();
    });

    sheet.addEventListener('click', (event) => {
        if (event.target === sheet) {
            closeSheet();
        }
    });
}
    document.querySelectorAll('[data-module="admin-change-request"]').forEach((element) => {
        try {
            initAdminChangeRequest(element);
        } catch (error) {
            console.error('Failed to initialise change request module', error);
        }
    });
