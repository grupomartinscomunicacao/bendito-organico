/**
 * Quantity stepper with a live running total.
 *
 * The total shown here is a courtesy for the shopper — the server prices the
 * order again from the database when it is placed, so nothing computed in this
 * file can affect what is charged.
 */

const parseNumber = (value) => {
    const parsed = Number.parseFloat(String(value).replace(',', '.'));

    return Number.isFinite(parsed) ? parsed : 0;
};

const formatBrl = (value) =>
    value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const formatQuantity = (value, step) => {
    const decimals = step < 1 ? 1 : 0;

    return value.toFixed(decimals).replace('.', ',');
};

export default function initQuantityStepper() {
    document.querySelectorAll('[data-quantity-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('input');
        const decrease = stepper.querySelector('[data-step="down"]');
        const increase = stepper.querySelector('[data-step="up"]');

        if (!input) {
            return;
        }

        const step = parseNumber(input.dataset.step || input.step || 1) || 1;
        const min = parseNumber(input.dataset.min || input.min || step) || step;
        const max = parseNumber(input.dataset.max || input.max || 99) || 99;
        const unitPrice = parseNumber(input.dataset.unitPrice);

        const totalTargets = document.querySelectorAll('[data-quantity-total]');
        const submit = document.querySelector('[data-quantity-submit]');

        const clamp = (value) => Math.min(max, Math.max(min, value));

        const render = (value) => {
            input.value = formatQuantity(value, step);

            if (decrease) decrease.disabled = value <= min;
            if (increase) increase.disabled = value >= max;

            if (unitPrice > 0) {
                const total = formatBrl(Math.round(unitPrice * value * 100) / 100);
                totalTargets.forEach((el) => {
                    el.textContent = total;
                });
            }

            if (submit) {
                submit.disabled = max < min;
            }
        };

        const apply = (delta) => {
            // Round to the step grid so repeated clicks cannot drift into
            // values like 1.4999999999.
            const next = clamp(Math.round((parseNumber(input.value) + delta) / step) * step);
            render(next);
        };

        decrease?.addEventListener('click', () => apply(-step));
        increase?.addEventListener('click', () => apply(step));

        input.addEventListener('change', () => render(clamp(parseNumber(input.value))));
        input.addEventListener('blur', () => render(clamp(parseNumber(input.value))));

        render(clamp(parseNumber(input.value) || min));
    });
}
