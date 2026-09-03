/**
 * Show/hide switch for password fields.
 */
export default function initPasswordToggle() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const wrapper = button.closest('.input-with-action');
        const input = wrapper?.querySelector('input');
        const icon = button.querySelector('.bi');

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const revealed = input.type === 'text';

            input.type = revealed ? 'password' : 'text';
            button.setAttribute('aria-label', revealed ? 'Mostrar senha' : 'Ocultar senha');
            icon?.classList.toggle('bi-eye', revealed);
            icon?.classList.toggle('bi-eye-slash', !revealed);
        });
    });
}
