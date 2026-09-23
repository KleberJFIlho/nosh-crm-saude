document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => sidebar?.classList.toggle('open'));

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const inputId = button.getAttribute('aria-controls');
            const input = inputId ? document.getElementById(inputId) : null;
            if (!input) return;

            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.classList.toggle('is-visible', !isVisible);
            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', isVisible ? 'Mostrar palavra-passe' : 'Ocultar palavra-passe');
            button.setAttribute('title', isVisible ? 'Mostrar palavra-passe' : 'Ocultar palavra-passe');
            input.focus({ preventScroll: true });
        });
    });

    document.querySelector('[data-flash-close]')?.addEventListener('click', (event) => event.currentTarget.closest('[data-flash]')?.remove());

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.querySelector(`[data-modal="${button.dataset.modalOpen}"]`);
            if (modal) modal.hidden = false;
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('[data-modal]');
            if (modal) modal.hidden = true;
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-modal]:not([hidden])').forEach((modal) => {
                modal.hidden = true;
            });
        }
    });

    const notificationCenter = document.querySelector('[data-notification-center]');
    if (notificationCenter) {
        const toggle = notificationCenter.querySelector('[data-notification-toggle]');
        const popup = notificationCenter.querySelector('[data-notification-popup]');
        const badge = notificationCenter.querySelector('[data-notification-badge]');
        const list = notificationCenter.querySelector('[data-notification-list]');
        const summary = notificationCenter.querySelector('[data-notification-summary]');
        const endpoint = notificationCenter.dataset.notificationUrl;
        let loading = false;

        const closeNotifications = () => {
            if (!popup || !toggle) return;
            popup.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        const renderNotification = (item) => {
            const link = document.createElement('a');
            link.className = `notification-item priority-${item.priority || 'normal'}`;
            link.href = item.url || '#';

            const icon = document.createElement('span');
            icon.className = 'notification-item-icon';
            icon.textContent = item.icon || '•';

            const body = document.createElement('span');
            body.className = 'notification-item-body';
            const title = document.createElement('strong');
            title.textContent = item.title || 'Notificação';
            const message = document.createElement('span');
            message.textContent = item.message || '';
            const time = document.createElement('small');
            time.textContent = item.time || '';
            body.append(title, message, time);
            link.append(icon, body);
            return link;
        };

        const loadNotifications = async () => {
            if (!endpoint || loading) return;
            loading = true;
            try {
                const response = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                const count = Number(data.count || 0);

                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = count === 0;
                toggle.classList.toggle('has-notifications', count > 0);
                toggle.setAttribute('aria-label', count > 0 ? `Abrir notificações (${count})` : 'Abrir notificações');

                const counts = data.counts || {};
                summary.innerHTML = '';
                [
                    ['Transferências', counts.transfers || 0],
                    ['Em atraso', counts.overdue_tasks || 0],
                    ['Consultas 24h', counts.appointments || 0],
                ].forEach(([label, value]) => {
                    const chip = document.createElement('span');
                    chip.textContent = `${label}: ${value}`;
                    summary.appendChild(chip);
                });

                list.innerHTML = '';
                if (!Array.isArray(data.items) || data.items.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'notification-empty';
                    empty.innerHTML = '<strong>Tudo em dia</strong><span>Não existem alertas pendentes para esta clínica.</span>';
                    list.appendChild(empty);
                    return;
                }
                data.items.forEach((item) => list.appendChild(renderNotification(item)));
            } catch (error) {
                list.innerHTML = '<div class="notification-empty"><strong>Não foi possível atualizar</strong><span>Tente novamente ao abrir o sino.</span></div>';
            } finally {
                loading = false;
            }
        };

        toggle?.addEventListener('click', async () => {
            if (!popup) return;
            const willOpen = popup.hidden;
            popup.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', String(willOpen));
            if (willOpen) await loadNotifications();
        });

        document.addEventListener('click', (event) => {
            if (!notificationCenter.contains(event.target)) closeNotifications();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeNotifications();
        });

        loadNotifications();
        window.setInterval(loadNotifications, 60000);
    }

});

// NOSH CRM Saude v0.4.0 - preview da foto do profissional
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-professional-photo-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const preview = document.querySelector('[data-professional-photo-preview]');
            if (!file || !preview || !file.type.startsWith('image/')) return;

            const oldUrl = preview.dataset.objectUrl;
            if (oldUrl) URL.revokeObjectURL(oldUrl);

            const url = URL.createObjectURL(file);
            preview.dataset.objectUrl = url;
            preview.innerHTML = '';
            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Pré-visualização da foto do profissional';
            preview.appendChild(image);
        });
    });
});

