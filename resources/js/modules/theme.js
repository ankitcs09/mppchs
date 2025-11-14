const STORAGE_KEY = 'wellcarePortalPreferences';

const defaults = {
    theme: 'wellcare-classic',
    mode: 'light',
};

function loadPreferences() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return { ...defaults };
        }
        return { ...defaults, ...JSON.parse(raw) };
    } catch {
        return { ...defaults };
    }
}

function savePreferences(preferences) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
    } catch (error) {
        console.warn('Unable to persist theme preferences', error);
    }
}

function applyTheme(preferences) {
    const body = document.body;
    body.dataset.theme = preferences.theme;
    const isDark = preferences.mode === 'dark';
    body.classList.toggle('theme-dark', isDark);
    document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
}

export function initThemeManager() {
    const preferences = loadPreferences();
    applyTheme(preferences);

    const themeSelects = Array.from(document.querySelectorAll('[data-role="theme-select"]'));
    const modeToggles = Array.from(document.querySelectorAll('[data-role="mode-toggle"]'));
    const themeButtons = Array.from(document.querySelectorAll('[data-role="theme-button"]'));

    const syncThemeSelects = () => {
        themeSelects.forEach((select) => {
            if (select.value !== preferences.theme) {
                select.value = preferences.theme;
            }
        });
        themeButtons.forEach((button) => {
            const isActive = button.dataset.themeTarget === preferences.theme;
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.classList.toggle('is-active', isActive);
        });
    };

    const syncModeToggles = () => {
        const isDark = preferences.mode === 'dark';
        modeToggles.forEach((toggle) => {
            if (toggle.checked !== isDark) {
                toggle.checked = isDark;
            }
        });
    };

    syncThemeSelects();
    syncModeToggles();

    themeSelects.forEach((select) => {
        select.addEventListener('change', (event) => {
            preferences.theme = event.target.value;
            applyTheme(preferences);
            savePreferences(preferences);
            syncThemeSelects();
        });
    });

    modeToggles.forEach((toggle) => {
        toggle.addEventListener('change', (event) => {
            preferences.mode = event.target.checked ? 'dark' : 'light';
            applyTheme(preferences);
            savePreferences(preferences);
            syncModeToggles();
        });
    });

    themeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.themeTarget) {
                preferences.theme = button.dataset.themeTarget;
            }
            if (button.dataset.modeTarget) {
                preferences.mode = button.dataset.modeTarget;
            }
            applyTheme(preferences);
            savePreferences(preferences);
            syncThemeSelects();
            syncModeToggles();
        });
    });
}
