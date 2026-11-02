const CONNECTION_ERROR = 'Tidak terhubung ke server. Coba lagi.';
const THROTTLE_ERROR = 'Terlalu banyak percobaan. Tunggu sebentar, lalu coba lagi.';

function clearFieldErrors(form) {
    form.querySelectorAll('.pn-field.is-error').forEach((field) => field.classList.remove('is-error'));
    form.querySelectorAll('.pn-field__helper').forEach((helper) => helper.remove());
    form.querySelectorAll('[aria-invalid]').forEach((input) => input.removeAttribute('aria-invalid'));
}

function showFieldError(form, name, message) {
    const input = form.querySelector(`[name="${name}"]`);
    const field = input?.closest('.pn-field');

    if (!field) {
        return false;
    }

    const helper = document.createElement('p');
    helper.className = 'pn-field__helper t-body-sm';
    helper.textContent = message;
    field.classList.add('is-error');
    field.append(helper);
    input.setAttribute('aria-invalid', 'true');

    return true;
}

function bindForm(form) {
    const submit = form.querySelector('[data-poin-submit]');
    const button = submit.querySelector('button');
    const label = submit.querySelector('[data-poin-label]');
    const spinner = submit.querySelector('.pn-spinner');
    const banner = form.querySelector('[data-poin-banner]');
    const bannerText = banner.querySelector('.pn-banner__message');
    const idleLabel = label.textContent;
    const screen = form.closest('.pn-auth');

    const setBusy = (busy) => {
        button.disabled = busy;
        button.classList.toggle('is-disabled', busy);
        spinner.hidden = !busy;
        label.textContent = busy ? 'Memproses…' : idleLabel;
        screen.classList.toggle('has-state', busy || !banner.hidden);
    };

    const fail = (message, retry) => {
        bannerText.textContent = message;
        banner.hidden = false;
        setBusy(false);
        label.textContent = retry ? 'Coba Lagi' : idleLabel;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFieldErrors(form);
        banner.hidden = true;
        setBusy(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                window.location.assign(data.redirect);
                return;
            }

            if (response.status === 422) {
                const data = await response.json();
                let shown = false;
                Object.entries(data.errors ?? {}).forEach(([name, messages]) => {
                    const target = name === 'nickname_key' ? 'nickname' : name;
                    shown = showFieldError(form, target, messages[0]) || shown;
                });
                setBusy(false);
                if (!shown) {
                    fail(CONNECTION_ERROR, true);
                }
                return;
            }

            if (response.status === 419) {
                window.location.reload();
                return;
            }

            fail(response.status === 429 ? THROTTLE_ERROR : CONNECTION_ERROR, response.status !== 429);
        } catch {
            fail(CONNECTION_ERROR, true);
        }
    });
}

document.querySelectorAll('form[data-poin-form]').forEach(bindForm);
