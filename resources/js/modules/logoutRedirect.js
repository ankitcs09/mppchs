export function initLogoutRedirect() {
    const triggers = document.querySelectorAll('.logout-redirect-trigger[data-logout-url]');

    if (!triggers.length) {
        return;
    }

    triggers.forEach((node) => {
        const url = node.getAttribute('data-logout-url');
        if (!url) {
            return;
        }
        const delay = parseInt(node.getAttribute('data-delay-ms') ?? '0', 10);
        const timeout = Number.isFinite(delay) && delay >= 0 ? delay : 0;

        window.setTimeout(() => {
            window.location.href = url;
        }, timeout);
    });
}
