(() => {
    const root = document.querySelector('[data-patient-access]');
    if (!root) return;

    const password = document.getElementById('patientPortalPassword');
    const confirmation = document.getElementById('patientPortalPasswordConfirmation');
    const generate = document.getElementById('generatePatientPassword');
    const copy = document.getElementById('copyPatientPassword');
    const copyUrl = document.getElementById('copyPatientPortalUrl');
    const portalUrl = document.getElementById('patientPortalLoginUrl');
    const feedback = document.getElementById('patientPasswordFeedback');

    const setFeedback = (text, type = '') => {
        if (!feedback) return;
        feedback.textContent = text;
        feedback.className = `pa-feedback${type ? ` ${type}` : ''}`;
    };

    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe');
        });
    });

    const secureRandom = (max) => {
        if (window.crypto?.getRandomValues) {
            const values = new Uint32Array(1);
            window.crypto.getRandomValues(values);
            return values[0] % max;
        }
        return Math.floor(Math.random() * max);
    };

    const pick = (chars) => chars[secureRandom(chars.length)];
    const shuffle = (items) => {
        for (let i = items.length - 1; i > 0; i--) {
            const j = secureRandom(i + 1);
            [items[i], items[j]] = [items[j], items[i]];
        }
        return items;
    };

    const makePassword = () => {
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghijkmnopqrstuvwxyz';
        const digits = '23456789';
        const symbols = '@#$%&*!?';
        const all = upper + lower + digits + symbols;
        const chars = [pick(upper), pick(lower), pick(digits), pick(symbols)];
        while (chars.length < 16) chars.push(pick(all));
        return shuffle(chars).join('');
    };

    const copyText = async (text) => {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }
        const area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        const copied = document.execCommand('copy');
        area.remove();
        if (!copied) throw new Error('copy failed');
    };

    generate?.addEventListener('click', () => {
        const generated = makePassword();
        password.value = generated;
        confirmation.value = generated;
        password.type = 'text';
        confirmation.type = 'text';
        root.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.setAttribute('aria-pressed', 'true');
            button.setAttribute('aria-label', 'Ocultar palavra-passe');
        });
        copy.disabled = false;
        setFeedback('Senha provisória gerada. Copie-a e entregue-a ao paciente por um canal seguro.', 'success');
        password.focus();
        password.select();
    });

    copy?.addEventListener('click', async () => {
        if (!password?.value) return;
        try {
            await copyText(password.value);
            setFeedback('Senha copiada para a área de transferência.', 'success');
        } catch {
            setFeedback('Não foi possível copiar automaticamente. Selecione e copie a senha manualmente.', 'error');
        }
    });

    copyUrl?.addEventListener('click', async () => {
        if (!portalUrl?.textContent) return;
        try {
            await copyText(portalUrl.textContent.trim());
            setFeedback('Endereço de login do paciente copiado.', 'success');
        } catch {
            setFeedback('Não foi possível copiar o endereço automaticamente.', 'error');
        }
    });

    password?.addEventListener('input', () => {
        if (copy) copy.disabled = password.value.length === 0;
    });
})();
