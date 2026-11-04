@extends('layouts.poin')

@section('title', 'Token Desain')

@section('content')
<main class="flex flex-col gap-8 p-6">
    <h1 class="t-h1">Token &amp; Komponen Poin</h1>

    <section class="flex flex-col gap-4">
        <h2 class="t-h3">Warna</h2>
        @foreach($colors as $group => $names)
            <h3 class="t-title-md">{{ $group }}</h3>
            <div class="flex flex-wrap gap-3">
                @foreach($names as $name)
                    <div class="flex flex-col gap-1">
                        <span class="block h-16 w-16 rounded-md border border-alpha-cream-45" style="background: var(--{{ $name }})"></span>
                        <span class="t-caption">{{ $name }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </section>

    <section class="flex flex-col gap-3 rounded-xl bg-neutral-cream p-6 text-text-ink">
        <h2 class="t-h3">Tipografi</h2>
        @foreach($types as $class => $label)
            <div class="flex flex-col gap-1 border-b border-alpha-ink-14 pb-2">
                <span class="t-body-sm text-text-mute">{{ $label }}</span>
                <span class="{{ $class }}">{{ $samples[$class] ?? 'Chicken Hand — Main Sekarang 1234' }}</span>
            </div>
        @endforeach
    </section>

    <section class="flex flex-col gap-4">
        <h2 class="t-h3">Spacing, Radius, Stroke</h2>
        <div class="flex flex-col gap-1">
            @foreach($spaces as $space)
                <div class="flex items-center gap-3">
                    <span class="t-caption w-16">space/{{ $space }}</span>
                    <span class="block h-3 rounded-xs bg-accent-gold" style="width: var(--space-{{ $space }})"></span>
                </div>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach($radii as $radius)
                <div class="flex flex-col gap-1">
                    <span class="block h-16 w-16 bg-brand-red-light" style="border-radius: var(--radius-{{ $radius }})"></span>
                    <span class="t-caption">radius/{{ $radius }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach($strokes as $stroke)
                <div class="flex flex-col gap-1">
                    <span class="block h-12 w-16 rounded-md border-solid border-neutral-cream" style="border-width: var(--stroke-{{ $stroke }})"></span>
                    <span class="t-caption">stroke/{{ $stroke }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="flex flex-col gap-4">
        <h2 class="t-h3">Shadow</h2>
        <div class="flex flex-wrap gap-6 rounded-xl bg-neutral-cream-dark p-6 text-text-ink">
            @foreach($shadows as $shadow)
                <div class="flex flex-col gap-2">
                    <span class="block h-16 w-16 rounded-md bg-neutral-white" style="box-shadow: var(--shadow-{{ $shadow }})"></span>
                    <span class="t-caption">{{ $shadow }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="flex flex-col gap-4">
        <h2 class="t-h3">Ikon</h2>
        <div class="flex flex-wrap gap-4">
            @foreach($icons as $icon)
                <div class="flex flex-col items-center gap-1">
                    <x-poin.icon :name="$icon" />
                    <span class="t-caption">{{ $icon }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex items-center gap-4">
            @foreach([16, 20, 24, 32, 48] as $size)
                <x-poin.icon name="chevron-right" :size="$size" />
            @endforeach
        </div>
    </section>

    <section class="flex flex-col gap-4">
        <h2 class="t-h3">Ilustrasi</h2>
        <div class="flex flex-wrap items-center gap-10 p-8">
            @foreach($illustrations as $name)
                <x-poin.illustration :name="$name" />
            @endforeach
            <x-poin.logo-mark tone="light" />
            <x-poin.logo-mark tone="dark" />
        </div>
    </section>

    <section class="flex max-w-screen flex-col gap-stack">
        <h2 class="t-h3">Button</h2>
        @foreach(['primary', 'secondary', 'ghost'] as $kind)
            <x-poin.button :kind="$kind">{{ ucfirst($kind) }}</x-poin.button>
            <x-poin.button :kind="$kind" state="hover">Hover</x-poin.button>
            <x-poin.button :kind="$kind" state="pressed">Pressed</x-poin.button>
            <x-poin.button :kind="$kind" disabled>Disabled</x-poin.button>
        @endforeach
        <x-poin.submit-button loading>Menyimpan…</x-poin.submit-button>

        <h2 class="t-h3">Icon Button</h2>
        <div class="flex gap-4">
            <x-poin.icon-button label="Kembali" />
            <x-poin.icon-button label="Kembali" state="hover" />
            <x-poin.icon-button label="Kembali" state="pressed" />
        </div>

        <h2 class="t-h3">Input</h2>
        <x-poin.input label="NAMA PEMAIN" name="a" placeholder="mis. Rina W." />
        <x-poin.input label="NAMA PEMAIN" name="b" value="mis. Rina W." state="focus" />
        <x-poin.input label="NAMA PEMAIN" name="c" value="mis. Rina W." />
        <x-poin.input label="NAMA PEMAIN" name="d" value="mis. Rina W." error="Format tidak valid" />
        <x-poin.input label="NAMA PEMAIN" name="e" placeholder="mis. Rina W." disabled />

        <h2 class="t-h3">Badge</h2>
        <div class="flex flex-wrap gap-3">
            <x-poin.badge type="ready" />
            <x-poin.badge type="auto" />
            <x-poin.badge type="waiting" />
            <x-poin.badge type="outline">RONDE 3</x-poin.badge>
            <x-poin.badge type="lose">POV LOSE</x-poin.badge>
            <x-poin.badge type="win">WIN BY DECK</x-poin.badge>
        </div>

        <h2 class="t-h3">Tabs</h2>
        <x-poin.segmented>
            <x-poin.tab selected>Tahun Ini</x-poin.tab>
            <x-poin.tab>Bulan Ini</x-poin.tab>
            <x-poin.tab>Minggu Ini</x-poin.tab>
        </x-poin.segmented>
        <x-poin.segmented>
            <x-poin.tab selected>Masuk</x-poin.tab>
            <x-poin.tab>Daftar</x-poin.tab>
        </x-poin.segmented>

        <h2 class="t-h3">Game Controls</h2>
        <x-poin.win-button />
        <x-poin.win-button state="hover" />
        <x-poin.win-button state="pressed" />
        <div class="flex flex-wrap gap-2">
            <x-poin.confirm-button />
            <x-poin.confirm-button state="hover" />
            <x-poin.confirm-button state="pressed" />
        </div>

        <h2 class="t-h3">Player</h2>
        <div class="flex items-center gap-4">
            <x-poin.avatar />
            <x-poin.score-chip label="P1" value="+6" />
            <x-poin.score-chip tone="lose" label="P2" value="-2" />
        </div>
        <div class="flex flex-wrap gap-3">
            <x-poin.player-seat name="P2" />
            <x-poin.player-seat name="P2" status="waiting" />
            <x-poin.player-seat you />
            <x-poin.player-seat you status="waiting" />
        </div>
        <x-poin.player-row rank="1" name="Kamu" score="8" delta="+4" />
        <x-poin.player-row highlight rank="1" name="Kamu" score="8" delta="+4" />
        <x-poin.player-row size="compact" rank="4" name="Nama pemain" meta="96 main · 31 menang" score="143" />
        <x-poin.player-row size="compact" highlight rank="4" name="Nama pemain" meta="96 main · 31 menang" score="143" />

        <h2 class="t-h3">List</h2>
        <x-poin.option-row>Label</x-poin.option-row>
        <x-poin.option-row state="hover">Label</x-poin.option-row>
        <x-poin.option-row state="pressed">Label</x-poin.option-row>
        <x-poin.menu-row>Label</x-poin.menu-row>

        <h2 class="t-h3">Layout</h2>
        <x-poin.app-bar title="Judul" back="#" />
        <x-poin.app-bar title="Judul" back="#" profile="#" />
        <x-poin.ornament />
        <x-poin.brand-lockup class="self-start" />

        <h2 class="t-h3">Home</h2>
        <div class="flex gap-6">
            <x-poin.game-card href="#" />
            <x-poin.game-card type="coming" />
        </div>
        <div class="flex gap-6">
            <x-poin.mahjong-tile />
            <x-poin.mahjong-tile tone="dim" />
        </div>

        <h2 class="t-h3">Leaderboard</h2>
        <div class="flex items-end gap-2">
            <x-poin.podium-step rank="2" name="Rina W." score="187" />
            <x-poin.podium-step rank="1" name="Budi S." score="212" />
            <x-poin.podium-step rank="3" name="Andi P." score="161" />
        </div>

        <h2 class="t-h3">Flow</h2>
        <x-poin.notice />
        @foreach(['P1', 'P2', 'P3', 'P4'] as $you)
            <x-poin.table-positions :you="$you" />
        @endforeach
        <x-poin.game-info-card />
        <x-poin.game-info-card type="coming" />

        <h2 class="t-h3">Pilih Game</h2>
        <x-poin.game-button />
        <x-poin.game-button state="hover" />
        <x-poin.game-button state="pressed" />
        <div class="pt-8"><x-poin.game-button type="locked" /></div>

        <h2 class="t-h3">States</h2>
        <x-poin.status-banner tone="error">Tidak terhubung ke server. Coba lagi.</x-poin.status-banner>
        <x-poin.status-banner tone="warning">Koneksi terputus. Menyambungkan kembali…</x-poin.status-banner>
        <x-poin.status-banner tone="success">Foto profil berhasil diperbarui.</x-poin.status-banner>
        <x-poin.spinner />
        <x-poin.skeleton />
        <x-poin.state-message title="Tidak ada koneksi" desc="Periksa internetmu, lalu coba lagi." />
        <x-poin.state-message icon="inbox" title="Belum ada poin musim ini" desc="Mainkan satu ronde di meja venue untuk masuk ke leaderboard." />

        <h2 class="t-h3">Dialog</h2>
        <x-poin.confirm-dialog title="Judul dialog" desc="Penjelasan singkat apa yang akan terjadi." />
    </section>
</main>
@endsection
