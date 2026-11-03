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
    const busyLabel = form.dataset.busyLabel ?? 'Memproses…';
    const retryLabel = form.dataset.retryLabel ?? 'Coba Lagi';
    const connectionError = form.dataset.connectionError ?? CONNECTION_ERROR;

    const setBusy = (busy) => {
        button.disabled = busy;
        button.classList.toggle('is-disabled', busy);
        spinner.hidden = !busy;
        label.textContent = busy ? busyLabel : idleLabel;
        screen?.classList.toggle('has-state', busy || !banner.hidden);
    };

    const fail = (message, retry) => {
        bannerText.textContent = message;
        banner.hidden = false;
        setBusy(false);
        label.textContent = retry ? retryLabel : idleLabel;
    };

    form.poinFail = fail;

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
                const unplaced = [];
                Object.entries(data.errors ?? {}).forEach(([name, messages]) => {
                    const target = name === 'nickname_key' ? 'nickname' : name;
                    if (!showFieldError(form, target, messages[0])) {
                        unplaced.push(messages[0]);
                    }
                });
                setBusy(false);
                if (unplaced.length || !data.errors) {
                    fail(unplaced[0] ?? connectionError, !unplaced.length);
                }
                return;
            }

            if (response.status === 419) {
                window.location.reload();
                return;
            }

            fail(response.status === 429 ? THROTTLE_ERROR : connectionError, response.status !== 429);
        } catch {
            fail(connectionError, true);
        }
    });
}

document.querySelectorAll('form[data-poin-form]').forEach(bindForm);

const PHOTO_TYPES = ['image/jpeg', 'image/png'];
const PHOTO_MAX_BYTES = 5 * 1024 * 1024;
const PHOTO_MAX_SIDE = 512;
const PHOTO_QUALITY = 0.85;

async function shrinkPhoto(file) {
    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, PHOTO_MAX_SIDE / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        const context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', PHOTO_QUALITY));

        return blob ? new File([blob], 'foto.jpg', { type: 'image/jpeg' }) : file;
    } catch {
        return file;
    }
}

function bindPhoto(form) {
    const inputs = [...form.querySelectorAll('[data-poin-photo-input]')];
    const preview = form.querySelector('[data-poin-photo-preview]');
    let objectUrl = null;

    const showPreview = (file) => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        objectUrl = URL.createObjectURL(file);
        let image = preview.querySelector('.pn-avatar__image');
        if (!image) {
            preview.replaceChildren();
            image = document.createElement('img');
            image.className = 'pn-avatar__image';
            image.alt = '';
            preview.append(image);
        }
        image.src = objectUrl;
    };

    inputs.forEach((input) => input.addEventListener('change', async () => {
        let file = input.files[0];

        if (!file) {
            return;
        }

        if (!PHOTO_TYPES.includes(file.type) || file.size > PHOTO_MAX_BYTES) {
            input.value = '';
            form.poinFail(PHOTO_TYPES.includes(file.type) ? 'Ukuran foto maksimal 5 MB.' : 'Format foto harus JPG atau PNG.', false);
            return;
        }

        const small = await shrinkPhoto(file);

        if (small !== file) {
            try {
                const transfer = new DataTransfer();
                transfer.items.add(small);
                input.files = transfer.files;
                file = small;
            } catch {
            }
        }

        inputs.forEach((other) => {
            other.disabled = other !== input;
            if (other !== input) {
                other.value = '';
            }
        });
        form.querySelector('[data-poin-banner]').hidden = true;
        form.querySelector('[data-poin-removed]')?.remove();
        showPreview(file);
    }));
}

document.querySelectorAll('form[data-poin-photo]').forEach(bindPhoto);

function bindPasswordRules(form) {
    const fresh = form.elements.sandi_baru;
    const repeat = form.elements.ulangi_sandi;
    const rules = {
        length: () => fresh.value.length >= 8,
        mix: () => /\p{L}/u.test(fresh.value) && /\d/.test(fresh.value),
        upper: () => /\p{Lu}/u.test(fresh.value),
        match: () => fresh.value !== '' && fresh.value === repeat.value,
    };

    const update = () => {
        form.querySelectorAll('[data-poin-rule]').forEach((item) => {
            item.classList.toggle('is-met', rules[item.dataset.poinRule]());
        });
    };

    fresh.addEventListener('input', update);
    repeat.addEventListener('input', update);
    update();
}

document.querySelectorAll('form[data-poin-password-rules]').forEach(bindPasswordRules);

function bindLoader(loader) {
    const states = Object.fromEntries(
        [...loader.querySelectorAll(':scope > [data-poin-state]')].map((element) => [element.dataset.poinState, element]),
    );
    let loaded = false;

    const show = (name) => {
        Object.entries(states).forEach(([key, element]) => {
            element.hidden = key !== name;
        });
    };

    const load = async () => {
        show('loading');

        try {
            const response = await fetch(loader.dataset.url, {
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }

            if (response.status === 404 && loader.dataset.notFound) {
                window.location.assign(loader.dataset.notFound);
                return;
            }

            if (!response.ok) {
                show('error');
                return;
            }

            states.content.innerHTML = await response.text();
            loaded = true;
            show('content');
        } catch {
            show(navigator.onLine === false ? 'offline' : 'error');
        }
    };

    loader.querySelectorAll('[data-poin-retry]').forEach((button) => button.addEventListener('click', load));
    window.addEventListener('offline', () => {
        if (!loaded) {
            show('offline');
        }
    });
    window.addEventListener('online', () => {
        if (!loaded) {
            load();
        }
    });

    load();
}

document.querySelectorAll('[data-poin-loader]').forEach(bindLoader);

const POLL_INTERVAL = 3000;

function bindPoll(page) {
    const banner = page.querySelector('[data-poin-poll-banner]');
    const loading = page.querySelector('[data-poin-poll-loading]');
    const content = page.querySelector('[data-poin-poll-content]');
    let current = null;

    const setOffline = (offline) => {
        banner.hidden = !offline;
        page.classList.toggle('is-offline', offline);
        page.querySelectorAll('[data-poin-needs-connection]').forEach((button) => {
            if (offline) {
                button.dataset.poinWasDisabled = button.disabled ? '1' : '';
                button.disabled = true;
            } else if (button.dataset.poinWasDisabled !== undefined) {
                button.disabled = button.dataset.poinWasDisabled === '1';
                delete button.dataset.poinWasDisabled;
            }
        });
    };

    const tick = async () => {
        try {
            const response = await fetch(page.dataset.url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.redirected || response.status === 403) {
                window.location.assign(response.redirected ? response.url : '/');
                return;
            }

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const data = await response.json();

            if (data.redirect) {
                window.location.assign(data.redirect);
                return;
            }

            if (data.html !== current) {
                current = data.html;
                content.innerHTML = data.html;
            }

            loading.hidden = true;
            content.hidden = false;
            setOffline(false);
        } catch {
            setOffline(true);
        }

        window.setTimeout(tick, POLL_INTERVAL);
    };

    tick();
}

document.querySelectorAll('[data-poin-poll]').forEach(bindPoll);

function bindWatch(page) {
    const tick = async () => {
        try {
            const response = await fetch(page.dataset.url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.redirected || response.status === 403) {
                window.location.assign(response.redirected ? response.url : '/');
                return;
            }

            if (response.ok) {
                const data = await response.json();

                if (data.redirect) {
                    window.location.assign(data.redirect);
                    return;
                }
            }
        } catch {
        }

        window.setTimeout(tick, POLL_INTERVAL);
    };

    window.setTimeout(tick, POLL_INTERVAL);
}

document.querySelectorAll('[data-poin-watch]').forEach(bindWatch);

function bindFlow(page) {
    const steps = [...page.querySelectorAll('[data-poin-step]')];
    let lastForm = null;
    let busy = false;

    const show = (name) => {
        page.classList.remove('is-sheet-open');
        steps.forEach((step) => {
            step.hidden = step.dataset.poinStep !== name;
        });
    };

    const hasStep = (name) => steps.some((step) => step.dataset.poinStep === name);

    const setButtonBusy = (form, state) => {
        const button = form.querySelector('button[type="submit"]');

        if (!button || !form.dataset.poinBusyLabel) {
            return;
        }

        button.dataset.poinIdle ??= button.textContent;
        button.disabled = state;
        button.classList.toggle('is-disabled', state);
        button.textContent = state ? form.dataset.poinBusyLabel : button.dataset.poinIdle;
    };

    const run = async (form) => {
        if (busy) {
            return;
        }

        busy = true;
        lastForm = form;

        if (hasStep('saving')) {
            show('saving');
        } else {
            page.classList.remove('is-sheet-open');
            setButtonBusy(form, true);
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.status === 419) {
                window.location.reload();
                return;
            }

            if (response.ok) {
                const data = await response.json();
                window.location.assign(data.redirect);
                return;
            }

            throw new Error(String(response.status));
        } catch {
            busy = false;
            setButtonBusy(form, false);
            show('failed');
        }
    };

    page.querySelectorAll('form[data-poin-action]').forEach((form) => form.addEventListener('submit', (event) => {
        event.preventDefault();
        run(form);
    }));
    page.querySelectorAll('[data-poin-action-retry]').forEach((button) => button.addEventListener('click', () => {
        if (lastForm) {
            run(lastForm);
        }
    }));
    page.querySelectorAll('[data-poin-step-back]').forEach((button) => button.addEventListener('click', () => show('main')));
    page.querySelectorAll('[data-poin-sheet-open]').forEach((button) => button.addEventListener('click', () => page.classList.add('is-sheet-open')));
    page.querySelectorAll('[data-poin-sheet-close]').forEach((button) => button.addEventListener('click', () => page.classList.remove('is-sheet-open')));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            page.classList.remove('is-sheet-open');
        }
    });
}

document.querySelectorAll('[data-poin-flow]').forEach(bindFlow);

let dialogOpener = null;

function closeDialog(dialog) {
    dialog.hidden = true;
    document.body.classList.remove('has-dialog');
    dialogOpener?.focus();
    dialogOpener = null;
}

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-poin-dialog-open]');

    if (opener) {
        const dialog = document.getElementById(opener.dataset.poinDialogOpen);
        dialogOpener = opener;
        dialog.hidden = false;
        document.body.classList.add('has-dialog');
        dialog.querySelector('button')?.focus();
        return;
    }

    const dialog = event.target.closest('[data-poin-dialog]');

    if (dialog && (event.target === dialog || event.target.closest('[data-poin-dialog-close]'))) {
        closeDialog(dialog);
    }
});

document.addEventListener('keydown', (event) => {
    const dialog = document.querySelector('[data-poin-dialog]:not([hidden])');

    if (event.key === 'Escape' && dialog) {
        closeDialog(dialog);
    }
});

function parseTableCode(text) {
    const value = text.trim();
    const fromUrl = value.match(/\/meja\/([A-Za-z0-9]+)\/?$/);

    if (fromUrl) {
        return fromUrl[1];
    }

    return /^[A-Za-z0-9]{4,12}$/.test(value) ? value : null;
}

function bindScanner(page) {
    const video = page.querySelector('[data-poin-video]');
    const canvas = document.createElement('canvas');
    let stream = null;
    let decoder = null;
    let scanning = false;

    const show = (name) => {
        page.querySelectorAll('[data-poin-scan-state]').forEach((element) => {
            element.hidden = element.dataset.poinScanState !== name;
        });
    };

    const stop = () => {
        scanning = false;
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
    };

    const openTable = (code) => {
        stop();
        window.location.assign(`${page.dataset.tableUrl}/${encodeURIComponent(code)}`);
    };

    const readFrame = () => {
        if (!scanning) {
            return;
        }

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d', { willReadFrequently: true });
            context.drawImage(video, 0, 0);
            const image = context.getImageData(0, 0, canvas.width, canvas.height);
            const result = decoder(image.data, image.width, image.height);

            if (result) {
                const code = parseTableCode(result.data);

                if (code) {
                    openTable(code);
                } else {
                    stop();
                    show('unknown');
                }
                return;
            }
        }

        window.setTimeout(readFrame, 250);
    };

    const start = async () => {
        show('camera');

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            decoder ??= (await import('jsqr')).default;
            video.srcObject = stream;
            await video.play();
            scanning = true;
            readFrame();
        } catch {
            stop();
            show('denied');
        }
    };

    page.querySelectorAll('[data-poin-scan-retry]').forEach((button) => button.addEventListener('click', start));
    page.querySelectorAll('[data-poin-scan-code]').forEach((button) => button.addEventListener('click', () => {
        stop();
        show('code');
    }));

    page.querySelector('[data-poin-code-form]').addEventListener('submit', (event) => {
        event.preventDefault();
        const code = parseTableCode(event.target.elements.code.value);

        if (code) {
            openTable(code);
        } else {
            show('unknown');
        }
    });

    if (page.dataset.initial === 'camera') {
        start();
    }
}

document.querySelectorAll('[data-poin-scanner]').forEach(bindScanner);
