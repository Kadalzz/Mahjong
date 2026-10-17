<script>
window.initBookingLines = function (config) {
    const list = document.getElementById('lineList');
    const addBtn = document.getElementById('addLineBtn');
    const countHint = document.getElementById('lineCountHint');
    const maxLines = parseInt(list.dataset.max, 10);
    const dateInput = config.dateInput;
    const baseUrl = @json(url('/booking/table'));
    const timers = new WeakMap();

    const rows = () => Array.from(list.querySelectorAll('[data-line]'));

    function computeEnd(start, hours) {
        const [h, m] = start.split(':').map(Number);
        const total = h * 60 + m + hours * 60;
        const wrapped = total % (24 * 60);
        const label = `${String(Math.floor(wrapped / 60)).padStart(2, '0')}:${String(wrapped % 60).padStart(2, '0')}`;
        return { label, nextDay: total >= 24 * 60 };
    }

    function renumber() {
        rows().forEach((row, i) => {
            row.querySelectorAll('[data-field]').forEach(el => {
                el.name = `lines[${i}][${el.dataset.field}]`;
            });
            row.querySelector('.line-remove').style.visibility = rows().length > 1 ? 'visible' : 'hidden';
        });
        const used = rows().map(r => r.querySelector('.line-table').value);
        rows().forEach(row => {
            const own = row.querySelector('.line-table').value;
            row.querySelectorAll('.line-table option').forEach(opt => {
                opt.disabled = opt.value !== own && used.includes(opt.value);
            });
        });
        const total = row => row.querySelectorAll('.line-table option').length;
        const canAdd = rows().length < maxLines && rows().length < total(rows()[0]);
        addBtn.disabled = !canAdd;
        countHint.textContent = `${rows().length} dari ${Math.min(maxLines, total(rows()[0]))} meja`;
    }

    function lineInfo(row) {
        const select = row.querySelector('.line-table');
        const option = select.options[select.selectedIndex];
        const start = row.querySelector('.line-start').value;
        const hours = parseInt(row.querySelector('.line-duration').value, 10);
        const end = start ? computeEnd(start, hours) : null;
        return {
            tableName: option ? option.text.split(' — ')[0] : '',
            start,
            hours,
            end,
            price: (option ? parseInt(option.dataset.price, 10) : 0) * hours,
            state: row.dataset.state || 'incomplete',
        };
    }

    function emit() {
        config.onChange(rows().map(lineInfo));
    }

    function setStatus(row, state, html) {
        row.dataset.state = state;
        row.querySelector('.line-status').innerHTML = html;
        emit();
    }

    function renderBusy(row, busy) {
        const box = row.querySelector('.line-busy');
        if (!busy || busy.length === 0) {
            box.innerHTML = 'Jam terisi di meja ini: belum ada.';
            return;
        }
        box.innerHTML = 'Jam terisi di meja ini: ' + busy.map(b =>
            `<span class="busy-chip ${b.status === 'pending_payment' ? 'pending' : ''}">${b.start} – ${b.end}</span>`
        ).join('');
    }

    function check(row, fromUser = true) {
        const server = row.querySelector('.line-server-error');
        if (server && fromUser) server.remove();

        const date = dateInput.value;
        const start = row.querySelector('.line-start').value;
        const hours = row.querySelector('.line-duration').value;
        const tableId = row.querySelector('.line-table').value;

        if (!date) {
            setStatus(row, 'incomplete', '<span style="color:var(--ink-mute)">Pilih tanggal dulu.</span>');
            return;
        }

        setStatus(row, start ? 'pending' : 'incomplete', start
            ? '<span class="availability-badge avail-checking"><i class="bi bi-hourglass-split"></i> Mengecek...</span>'
            : '<span style="color:var(--ink-mute)">Pilih jam mulai, atau tekan Main Sekarang.</span>');

        clearTimeout(timers.get(row));
        timers.set(row, setTimeout(() => {
            fetch(`${baseUrl}/${tableId}/availability`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({ booking_date: date, start_time: start || null, duration_hours: hours, allow_past: config.allowPast ? 1 : 0 }),
            })
            .then(r => r.json())
            .then(data => {
                renderBusy(row, data.busy);
                if (!start) return;
                if (data.available) {
                    setStatus(row, 'ok', `<span class="availability-badge avail-ok"><i class="bi bi-check-circle-fill"></i> Tersedia</span>
                        <span class="ms-1" style="color:var(--ink-mute)">Selesai ${data.end_time}${data.ends_next_day ? ' (besok)' : ''}</span>`);
                } else {
                    setStatus(row, 'bad', `<span class="availability-badge avail-no"><i class="bi bi-x-circle-fill"></i> Bentrok</span>
                        <span class="ms-1" style="color:#9b2c2c;font-weight:700">${data.message || 'Jam ini tidak tersedia.'}</span>`);
                }
            })
            .catch(() => setStatus(row, 'pending', '<span style="color:#9b2c2c">Gagal mengecek. Periksa koneksi lalu coba lagi.</span>'));
        }, 350));
    }

    function bind(row) {
        row.querySelector('.line-table').addEventListener('change', () => { renumber(); check(row); });
        row.querySelector('.line-start').addEventListener('change', () => check(row));
        row.querySelector('.line-duration').addEventListener('change', () => check(row));
        row.querySelector('.line-remove').addEventListener('click', () => {
            if (rows().length <= 1) return;
            row.remove();
            renumber();
            emit();
        });
    }

    addBtn.addEventListener('click', () => {
        const source = rows()[rows().length - 1];
        const clone = source.cloneNode(true);
        clone.querySelector('.line-server-error')?.remove();
        clone.dataset.state = '';
        const used = rows().map(r => r.querySelector('.line-table').value);
        const select = clone.querySelector('.line-table');
        const free = Array.from(select.options).find(o => !used.includes(o.value));
        if (!free) return;
        select.value = free.value;
        clone.querySelector('.line-start').value = source.querySelector('.line-start').value;
        clone.querySelector('.line-duration').value = source.querySelector('.line-duration').value;
        list.appendChild(clone);
        bind(clone);
        renumber();
        check(clone);
    });

    dateInput.addEventListener('change', () => rows().forEach(row => check(row)));

    if (config.playNowBtn) {
        config.playNowBtn.addEventListener('click', () => {
            const now = config.serverNow();
            dateInput.value = now.toISOString().slice(0, 10);
            const time = `${String(now.getUTCHours()).padStart(2, '0')}:${String(now.getUTCMinutes()).padStart(2, '0')}`;
            rows().forEach(row => { row.querySelector('.line-start').value = time; });
            rows().forEach(row => check(row));
        });
    }

    rows().forEach(bind);
    renumber();
    rows().forEach(row => check(row, false));

    return { rows, lineInfo };
};
</script>
