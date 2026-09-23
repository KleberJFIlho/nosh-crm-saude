(() => {
    const copyUrl = document.getElementById('copyPatientPortalUrl');
    const portalUrl = document.getElementById('patientPortalLoginUrl');
    if (!copyUrl || !portalUrl) return;

    const copyText = async (text) => {
        if (navigator.clipboard?.writeText) return navigator.clipboard.writeText(text);
        const area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        area.remove();
    };

    copyUrl.addEventListener('click', async () => {
        try {
            await copyText(portalUrl.textContent.trim());
            const original = copyUrl.textContent;
            copyUrl.textContent = 'Copiado';
            setTimeout(() => copyUrl.textContent = original, 1400);
        } catch (_) {
            copyUrl.textContent = 'Falhou';
        }
    });
})();
