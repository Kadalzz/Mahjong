<?php

namespace App\Http\Controllers\Poin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use App\Services\Poin\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public const STATUS_MESSAGES = [
        'foto'     => 'Foto profil berhasil diperbarui.',
        'username' => 'Username berhasil diubah.',
        'sandi'    => 'Sandi berhasil diubah.',
    ];

    public function show(Request $request)
    {
        return view('poin.profile', [
            'status' => self::STATUS_MESSAGES[$request->session()->get('poin_status')] ?? null,
        ]);
    }

    public function content()
    {
        $player = $this->player();
        $totals = PlayerRoundScore::where('player_id', $player->id)
            ->selectRaw('COALESCE(SUM(points), 0) as points, COUNT(*) as plays, COALESCE(SUM(CASE WHEN is_winner THEN 1 ELSE 0 END), 0) as wins')
            ->toBase()
            ->first();

        return view('poin.partials.profile', [
            'player' => $player,
            'points' => (int) $totals->points,
            'plays'  => (int) $totals->plays,
            'wins'   => (int) $totals->wins,
        ]);
    }

    public function photo(Request $request)
    {
        $player = $this->player();

        return view('poin.profile-photo', [
            'player'  => $player,
            'removed' => $request->session()->get('poin_avatar_removed') && $player->avatar_previous_path,
        ]);
    }

    public function updatePhoto(Request $request, AvatarService $avatars): JsonResponse|RedirectResponse
    {
        $request->validate([
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:' . Player::AVATAR_MAX_KB, 'dimensions:min_width=1,min_height=1'],
        ], [
            'foto.file'       => 'Foto gagal diunggah. Periksa koneksi lalu coba lagi.',
            'foto.uploaded'   => 'Foto gagal diunggah. Periksa koneksi lalu coba lagi.',
            'foto.mimes'      => 'Format foto harus JPG atau PNG.',
            'foto.mimetypes'  => 'Format foto harus JPG atau PNG.',
            'foto.dimensions' => 'Format foto harus JPG atau PNG.',
            'foto.max'        => 'Ukuran foto maksimal 5 MB.',
        ]);

        if (!$request->hasFile('foto')) {
            return $this->respond($request, route('poin.profile'));
        }

        $avatars->store($this->player(), $request->file('foto'));

        return $this->respond($request, route('poin.profile'), 'foto');
    }

    public function removePhoto(Request $request, AvatarService $avatars): RedirectResponse
    {
        if ($avatars->remove($this->player())) {
            $request->session()->flash('poin_avatar_removed', true);
        }

        return redirect()->route('poin.profile.photo');
    }

    public function restorePhoto(AvatarService $avatars): RedirectResponse
    {
        $avatars->restore($this->player());

        return redirect()->route('poin.profile.photo');
    }

    public function username()
    {
        return view('poin.profile-username', ['player' => $this->player()]);
    }

    public function updateUsername(Request $request): JsonResponse|RedirectResponse
    {
        $player = $this->player();
        $nickname = trim((string) $request->input('nickname'));
        $request->merge(['nickname' => $nickname, 'nickname_key' => Player::nicknameKey($nickname)]);

        $request->validate([
            'nickname'     => Player::NICKNAME_RULES,
            'nickname_key' => [Rule::unique('players', 'nickname_key')->ignore($player->id)],
        ], [
            'nickname.required'   => 'Username wajib diisi.',
            'nickname.max'        => 'Username maksimal ' . Player::NICKNAME_MAX . ' karakter.',
            'nickname.not_regex'  => 'Username tidak boleh mengandung @.',
            'nickname_key.unique' => 'Username sudah dipakai. Coba yang lain.',
        ]);

        $player->update(['nickname' => $nickname]);

        return $this->respond($request, route('poin.profile'), 'username');
    }

    public function password()
    {
        return view('poin.profile-password');
    }

    public function updatePassword(Request $request): JsonResponse|RedirectResponse
    {
        $player = $this->player();

        $data = $request->validate([
            'sandi_lama'   => ['required', 'string', 'max:100'],
            'sandi_baru'   => Player::PASSWORD_RULES,
            'ulangi_sandi' => ['required', 'same:sandi_baru'],
        ], [
            'sandi_lama.required'   => 'Sandi lama wajib diisi.',
            'sandi_baru.required'   => 'Sandi baru wajib diisi.',
            'sandi_baru.min'        => Player::PASSWORD_MESSAGE,
            'sandi_baru.regex'      => Player::PASSWORD_MESSAGE,
            'sandi_baru.max'        => 'Kata sandi maksimal 100 karakter.',
            'ulangi_sandi.required' => 'Ulangi sandi baru.',
            'ulangi_sandi.same'     => 'Sandi tidak cocok dengan sandi baru.',
        ]);

        if (!Hash::check($data['sandi_lama'], $player->password)) {
            throw ValidationException::withMessages(['sandi_lama' => 'Sandi lama salah.']);
        }

        $player->update(['password' => $data['sandi_baru']]);
        $request->session()->regenerate();
        $request->session()->put(Player::SESSION_FINGERPRINT, $player->passwordFingerprint());

        return $this->respond($request, route('poin.profile'), 'sandi');
    }

    private function player(): Player
    {
        return Auth::guard('player')->user();
    }

    private function respond(Request $request, string $target, ?string $status = null): JsonResponse|RedirectResponse
    {
        if ($status) {
            $request->session()->flash('poin_status', $status);
        }

        return $request->expectsJson()
            ? response()->json(['redirect' => $target])
            : redirect()->to($target);
    }
}
