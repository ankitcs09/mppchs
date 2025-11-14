export function initFormValidation(root = document) {
    const forms = Array.from(root.querySelectorAll('.needs-validation'));

    if (!forms.length) {
        return;
    }

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}
