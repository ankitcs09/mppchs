const DEFAULT_WARNING_MS = 20 * 60 * 1000; // 20 minutes
const DEFAULT_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes

export function initIdleTimeout() {
  const modal = document.querySelector('[data-idle-modal]');
  if (!modal) {
    return;
  }

  const warningMs = parseInt(modal.dataset.warningMs ?? '', 10) || DEFAULT_WARNING_MS;
  const timeoutMs = parseInt(modal.dataset.timeoutMs ?? '', 10) || DEFAULT_TIMEOUT_MS;
  const logoutUrl = modal.dataset.logoutUrl || '/logout';

  if (timeoutMs <= warningMs) {
    // Ensure timeout is always after warning.
    return;
  }

  let warningTimer = null;
  let timeoutTimer = null;
  let isModalVisible = false;

  const stayButton = modal.querySelector('[data-idle-stay]');
  const logoutButton = modal.querySelector('[data-idle-logout]');

  const showModal = () => {
    if (isModalVisible) {
      return;
    }
    modal.hidden = false;
    modal.classList.add('is-visible');
    isModalVisible = true;
  };

  const hideModal = () => {
    if (!isModalVisible) {
      return;
    }
    modal.hidden = true;
    modal.classList.remove('is-visible');
    isModalVisible = false;
  };

  const redirectToLogout = () => {
    window.location.href = logoutUrl;
  };

  const clearTimers = () => {
    if (warningTimer) {
      window.clearTimeout(warningTimer);
      warningTimer = null;
    }
    if (timeoutTimer) {
      window.clearTimeout(timeoutTimer);
      timeoutTimer = null;
    }
  };

  const startTimers = () => {
    clearTimers();
    warningTimer = window.setTimeout(showModal, warningMs);
    timeoutTimer = window.setTimeout(redirectToLogout, timeoutMs);
  };

  const handleActivity = () => {
    if (isModalVisible) {
      return;
    }
    startTimers();
  };

  const staySignedIn = () => {
    hideModal();
    startTimers();
  };

  const bindEvents = () => {
    ['mousemove', 'keydown', 'scroll', 'click'].forEach((eventName) => {
      document.addEventListener(eventName, handleActivity, { passive: true });
    });

    stayButton?.addEventListener('click', staySignedIn);
    logoutButton?.addEventListener('click', redirectToLogout);
  };

  modal.hidden = true;
  bindEvents();
  startTimers();
}
