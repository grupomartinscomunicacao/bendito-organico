/**
 * Fills the address fields from a Brazilian postcode using ViaCEP.
 *
 * Purely a convenience: the customer can always type the address by hand, and
 * every field is validated server-side regardless of where it came from. A
 * failed lookup is silent — the form still works offline.
 */

const ENDPOINT = 'https://viacep.com.br/ws';

export default function initAddressLookup() {
    const zipInput = document.querySelector('[data-address-zip]');

    if (!zipInput) {
        return;
    }

    const form = zipInput.closest('form');
    const status = document.querySelector('[data-address-status]');

    const field = (name) => form?.querySelector(`[name="${name}"]`);

    const setStatus = (message, variant = 'muted') => {
        if (!status) return;

        status.textContent = message;
        status.className = `form-hint text-${variant}`;
    };

    let lastLookup = '';

    const lookup = async () => {
        const zip = zipInput.value.replace(/\D/g, '');

        if (zip.length !== 8 || zip === lastLookup) {
            return;
        }

        lastLookup = zip;
        setStatus('Buscando endereço…');

        try {
            const response = await fetch(`${ENDPOINT}/${zip}/json/`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.erro) {
                setStatus('CEP não encontrado. Preencha o endereço manualmente.', 'danger');

                return;
            }

            const mapping = {
                street: data.logradouro,
                district: data.bairro,
                city: data.localidade,
                state: data.uf,
            };

            Object.entries(mapping).forEach(([name, value]) => {
                const input = field(name);

                // Never overwrite something the customer already typed.
                if (input && value && input.value.trim() === '') {
                    input.value = value;
                }
            });

            setStatus('Endereço preenchido. Confira e complete o número.', 'success');
            field('number')?.focus();
        } catch (error) {
            setStatus('Não conseguimos buscar o CEP. Preencha o endereço manualmente.', 'muted');
        }
    };

    zipInput.addEventListener('blur', lookup);
    zipInput.addEventListener('input', () => {
        if (zipInput.value.replace(/\D/g, '').length === 8) {
            lookup();
        }
    });
}
