/**
 * Lightweight input masks for Brazilian phone numbers, postcodes and prices.
 *
 * Hand-rolled rather than pulled from a library: three formats do not justify
 * a dependency, and the server normalises and re-validates everything anyway.
 */

const digits = (value) => value.replace(/\D/g, '');

const maskPhone = (value) => {
    const d = digits(value).slice(0, 11);

    if (d.length <= 2) return d;
    if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
    if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;

    return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
};

const maskZip = (value) => {
    const d = digits(value).slice(0, 8);

    return d.length <= 5 ? d : `${d.slice(0, 5)}-${d.slice(5)}`;
};

const maskMoney = (value) => {
    const d = digits(value).slice(0, 9);

    if (d === '') return '';

    return (Number(d) / 100).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const MASKS = {
    phone: maskPhone,
    zip: maskZip,
    money: maskMoney,
};

export default function initInputMasks() {
    document.querySelectorAll('[data-mask]').forEach((input) => {
        const mask = MASKS[input.dataset.mask];

        if (!mask) {
            return;
        }

        const apply = () => {
            const before = input.value;
            const masked = mask(before);

            if (masked !== before) {
                input.value = masked;
            }
        };

        input.addEventListener('input', apply);
        apply();
    });
}
