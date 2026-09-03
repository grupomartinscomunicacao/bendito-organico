/**
 * Confirmation gate for destructive submits.
 *
 * This is a courtesy prompt, not a security control: the real authorisation
 * check lives in the policy on the server.
 */
export default function initConfirmActions() {
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('submit', (event) => {
            if (!window.confirm(element.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // Buttons that submit a form they are not inside.
    document.querySelectorAll('button[data-confirm-click]').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!window.confirm(button.dataset.confirmClick)) {
                event.preventDefault();
            }
        });
    });
}
