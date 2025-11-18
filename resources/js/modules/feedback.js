export function showToast({ message, type = 'info', duration = 4000 }) {
    let container = document.querySelector('[data-role="toast-container"]');

    if (!container) {
        container = document.createElement('div');
        container.dataset.role = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 show`;
    toast.role = 'status';
    toast.ariaLive = 'polite';
    toast.ariaAtomic = 'true';

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Close"></button>
        </div>
    `;

    container.appendChild(toast);

    const closeButton = toast.querySelector('.btn-close');
    const timer = window.setTimeout(() => {
        toast.classList.remove('show');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, duration);

    closeButton.addEventListener('click', () => {
        window.clearTimeout(timer);
        toast.remove();
    });
}

export function initAsyncFeedback() {
    document.querySelectorAll('[data-feedback-message]').forEach((element) => {
        const message = element.dataset.feedbackMessage;
        const type = element.dataset.feedbackType || 'info';
        const duration = Number(element.dataset.feedbackDuration) || 4000;

        if (message) {
            showToast({ message, type, duration });
        }
    });
}
