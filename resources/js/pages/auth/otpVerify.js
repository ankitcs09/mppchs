export function initOtpVerify() {
    const container = document.querySelector('[data-module="auth-otp-verify"]');

    if (!container) {
        return;
    }

    const resendButton = container.querySelector('#otp-resend-button');

    if (resendButton) {
        const baseLabel = resendButton.dataset.label || resendButton.textContent || 'Resend OTP';
        let remaining = Number.parseInt(resendButton.dataset.cooldown || '0', 10);
        let timerId;

        const updateLabel = () => {
            if (remaining <= 0) {
                resendButton.disabled = false;
                resendButton.textContent = baseLabel;
                if (timerId) {
                    window.clearInterval(timerId);
                }
                return;
            }

            resendButton.textContent = `Resend available in ${remaining}s`;
            remaining -= 1;
        };

        if (Number.isFinite(remaining) && remaining > 0) {
            resendButton.disabled = true;
            updateLabel();
            timerId = window.setInterval(updateLabel, 1000);
        }
    }

    const isLoggedIn = container.dataset.isLoggedIn === 'true';
    const dashboardUrl = container.dataset.dashboardUrl;
    const otpUrl = container.dataset.otpUrl;

    if (dashboardUrl && otpUrl) {
        window.addEventListener('pageshow', (event) => {
            const entries = performance.getEntriesByType('navigation');
            const navigationType = entries.length ? entries[0].type : undefined;
            const fromHistory = event.persisted || navigationType === 'back_forward';

            if (!fromHistory) {
                return;
            }

            if (isLoggedIn) {
                window.location.replace(dashboardUrl);
            } else {
                window.location.replace(otpUrl);
            }
        });
    }
}
