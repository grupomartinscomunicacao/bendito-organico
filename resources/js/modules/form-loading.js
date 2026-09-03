/**
 * Submit buttons that show they are working.
 *
 * Opt in with `data-loading` on the form. The button keeps its size and swaps
 * its label for a spinner, so a slow lookup or a redirect to the payment
 * gateway never leaves a phone screen looking like nothing happened — and a
 * second tap cannot fire the request twice.
 */
export default function initFormLoading() {
    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            // A form the browser is about to reject never leaves: spinning
            // here would strand the button in a state nothing clears.
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }

            const button = form.querySelector('[type="submit"]');

            if (!button) {
                return;
            }

            button.classList.add('is-loading');

            // Disabling inside the submit handler can cancel the submission in
            // some browsers; one tick later the request is already on its way.
            window.setTimeout(() => {
                button.disabled = true;
            }, 0);
        });
    });
}
