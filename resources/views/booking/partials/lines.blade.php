@php
    $initialLines = old('lines') ? array_values(old('lines')) : [['table_id' => $defaultTableId ?? $tables->first()?->id, 'start_time' => '', 'duration_hours' => 1]];
    $oldKeys = old('lines') ? array_keys(old('lines')) : [0];
@endphp

<div id="lineList" data-max="{{ $maxTables }}">
    @foreach($initialLines as $i => $line)
    @php $errorKey = 'lines.' . ($oldKeys[$i] ?? $i) . '.start_time'; @endphp
    <div class="line-row" data-line>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label">Meja</label>
                <select class="form-select line-table" data-field="table_id" required>
                    @foreach($tables as $t)
                    <option value="{{ $t->id }}" data-price="{{ (int) $t->getCurrentPricePerHour() }}"
                        {{ (int) ($line['table_id'] ?? 0) === $t->id ? 'selected' : '' }}>
                        {{ $t->name }} — Rp {{ number_format($t->getCurrentPricePerHour(), 0, ',', '.') }}/jam
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Jam Mulai</label>
                <input type="time" class="form-control line-start" data-field="start_time" step="60"
                    value="{{ $line['start_time'] ?? '' }}" required>
            </div>
            <div class="col-4 col-md-3">
                <label class="form-label">Durasi</label>
                <select class="form-select line-duration" data-field="duration_hours" required>
                    @for($h = 1; $h <= 8; $h++)
                    <option value="{{ $h }}" {{ (int) ($line['duration_hours'] ?? 1) === $h ? 'selected' : '' }}>{{ $h }} Jam</option>
                    @endfor
                </select>
            </div>
            <div class="col-2 col-md-1 text-end">
                <button type="button" class="btn btn-link line-remove p-2" title="Hapus meja ini" style="color:var(--red)">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>
        <div class="line-status mt-2" style="font-size:0.82rem"></div>
        @if($errors->has($errorKey))
        <div class="line-server-error mt-1" style="font-size:0.82rem;color:#9b2c2c;font-weight:700">{{ $errors->first($errorKey) }}</div>
        @endif
        <div class="line-busy mt-1" style="font-size:0.78rem;color:var(--ink-mute)"></div>
    </div>
    @endforeach
</div>

<button type="button" id="addLineBtn" class="btn btn-outline-secondary btn-sm mt-2" style="border-radius:999px;font-weight:700">
    <i class="bi bi-plus-lg me-1"></i>Tambah Meja
</button>
<span id="lineCountHint" class="ms-2" style="font-size:0.78rem;color:var(--ink-mute)"></span>
@error('lines')
<div class="mt-2" style="font-size:0.85rem;color:#9b2c2c;font-weight:700">{{ $message }}</div>
@enderror
