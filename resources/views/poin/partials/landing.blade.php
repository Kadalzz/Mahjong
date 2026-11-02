<x-poin.ornament />
<h1 class="t-h2 text-center">Hóng Zhōng Mahjong</h1>
<p class="pn-muted t-body-lg text-center">Main mahjong di venue kami. Kumpulkan poin dan masuk leaderboard sepanjang tahun.</p>
<p class="t-kicker">LEADERBOARD CHICKEN HAND</p>
@if($topThree->isEmpty())
    <p class="pn-muted t-body text-center">Belum ada poin musim ini. Mainkan satu ronde di meja venue.</p>
@else
    <div class="pn-rank-list">
        @foreach($topThree as $row)
            <x-poin.rank-row :row="$row" :highlight="$row->rank === 1" />
        @endforeach
    </div>
@endif
<x-poin.button kind="ghost" :href="route('poin.leaderboard')">Lihat Leaderboard</x-poin.button>
<p class="t-kicker">PERMAINAN</p>
<x-poin.game-info-card />
<x-poin.game-info-card type="coming" />
<x-poin.ornament />
<x-poin.button :href="$playUrl">Main Sekarang</x-poin.button>
<p class="pn-faint t-body-sm text-center">Main hanya di meja venue · Poin tercatat sepanjang tahun</p>
