<?php

namespace App\Http\Controllers\Poin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('poin.auth.register');
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $nickname = trim((string) $request->input('nickname'));
        $request->merge(['nickname_key' => Player::nicknameKey($nickname), 'email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'nickname'     => Player::NICKNAME_RULES,
            'nickname_key' => [Rule::unique('players', 'nickname_key')],
            'email'        => ['required', 'email', 'max:150', Rule::unique('players', 'email')],
            'password'     => Player::PASSWORD_RULES,
        ], [
            'nickname.required'   => 'Nama pemain wajib diisi.',
            'nickname.max'        => 'Nama pemain maksimal ' . Player::NICKNAME_MAX . ' karakter.',
            'nickname.not_regex'  => 'Nama pemain tidak boleh mengandung @.',
            'nickname.regex'      => 'Nama pemain tidak boleh berisi karakter tersembunyi.',
            'nickname_key.unique' => 'Nama pemain sudah dipakai. Coba yang lain.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.max'           => 'Email maksimal 150 karakter.',
            'email.unique'        => 'Email sudah terdaftar. Silakan masuk.',
            'password.required'   => 'Kata sandi wajib diisi.',
            'password.min'        => Player::PASSWORD_MESSAGE,
            'password.regex'      => Player::PASSWORD_MESSAGE,
            'password.max'        => 'Kata sandi maksimal 100 karakter.',
        ]);

        $player = Player::create([
            'nickname' => $nickname,
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        return $this->startSession($request, $player);
    }

    public function showLogin()
    {
        return view('poin.auth.login');
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'login'    => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:100'],
        ], [
            'login.required'    => 'Email atau nama pemain wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $login = trim($data['login']);
        $where = str_contains($login, '@')
            ? ['email' => mb_strtolower($login)]
            : ['nickname_key' => Player::nicknameKey($login)];

        if (!Auth::guard('player')->attempt($where + ['password' => $data['password']])) {
            throw ValidationException::withMessages([
                'login' => 'Email, nama pemain, atau kata sandi salah. Coba lagi.',
            ]);
        }

        return $this->startSession($request, Auth::guard('player')->user());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('player')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('poin.login');
    }

    private function startSession(Request $request, Player $player): JsonResponse|RedirectResponse
    {
        Auth::guard('player')->login($player);
        $request->session()->regenerate();
        $request->session()->put(Player::SESSION_FINGERPRINT, $player->passwordFingerprint());

        $target = $request->session()->pull('url.intended', route('poin.games'));

        return $request->expectsJson()
            ? response()->json(['redirect' => $target])
            : redirect()->to($target);
    }
}
