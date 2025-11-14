export default function initAdminChangeRequest(root) {
    if (!root) {
        return;
    }

    const filterButtons = root.querySelectorAll('[data-filter-status]');
    const cards = Array.from(root.querySelectorAll('[data-item-status]'));

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            filterButtons.forEach((btn) => btn.classList.remove('btn-primary'));
            filterButtons.forEach((btn) => btn.classList.add('btn-outline-secondary'));
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-primary');

            const status = button.dataset.filterStatus;
            cards.forEach((card) => {
                const cardStatus = card.dataset.itemStatus;
                const shouldShow = status === undefined || status === cardStatus;
                card.hidden = !shouldShow;
            });
        });
    });
}
