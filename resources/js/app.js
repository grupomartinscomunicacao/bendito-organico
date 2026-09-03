import * as bootstrap from 'bootstrap';

import initQuantityStepper from './modules/quantity-stepper';
import initInputMasks from './modules/masks';
import initAddressLookup from './modules/address-lookup';
import initImagePicker from './modules/image-picker';
import initPasswordToggle from './modules/password-toggle';
import initConfirmActions from './modules/confirm-actions';
import initFormLoading from './modules/form-loading';

window.bootstrap = bootstrap;

/**
 * Every module is defensive: it looks for its own hooks and does nothing when
 * the page has none, so a single bundle can serve the storefront, the checkout
 * and the admin panel without any per-page wiring.
 */
const boot = () => {
    initQuantityStepper();
    initInputMasks();
    initAddressLookup();
    initImagePicker();
    initPasswordToggle();
    initConfirmActions();
    initFormLoading();

    // Opt-in Bootstrap plugins.
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach((el) => new bootstrap.Tooltip(el));

    document.querySelectorAll('.js-autodismiss').forEach((el) => {
        window.setTimeout(() => bootstrap.Alert.getOrCreateInstance(el).close(), 6000);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
