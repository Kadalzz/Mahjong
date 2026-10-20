<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index()
    {
        $users = User::orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")->orderBy('name')->get();

        return view('dashboard.accounts.index', compact('users'));
    }

    public function create()
    {
        return view('dashboard.accounts.form', ['account' => new User(), 'permissions' => User::PERMISSIONS]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $account = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => $data['password'],
            'role'        => 'staff',
            'permissions' => $data['permissions'],
            'is_active'   => true,
        ]);

        ActivityLog::record($request->user(), 'account_create', detail: "{$account->name} ({$account->email})");

        return redirect()->route('dashboard.accounts.index')
            ->with('success', "Akun {$account->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        abort_if($user->isAdmin(), 403, 'Akun admin utama tidak bisa diubah di sini.');

        return view('dashboard.accounts.form', ['account' => $user, 'permissions' => User::PERMISSIONS]);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 403, 'Akun admin utama tidak bisa diubah di sini.');

        $data = $this->validated($request, $user);

        $user->fill([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'permissions' => $data['permissions'],
        ]);

        if (!empty($data['password'])) {
            $user->password = $data['password'];
            $user->setRememberToken(\Illuminate\Support\Str::random(60));
            $this->endSessions($user);
        }

        $user->save();

        ActivityLog::record($request->user(), 'account_update', detail: "{$user->name} ({$user->email})" . (!empty($data['password']) ? ', password direset' : ''));

        return redirect()->route('dashboard.accounts.index')
            ->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    public function toggle(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 403, 'Akun admin utama tidak bisa dinonaktifkan.');

        $user->update(['is_active' => !$user->is_active]);

        if (!$user->is_active) {
            $this->endSessions($user);
        }

        ActivityLog::record($request->user(), 'account_toggle', detail: "{$user->name}: " . ($user->is_active ? 'diaktifkan' : 'dinonaktifkan'));

        return back()->with('success', $user->is_active
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan.");
    }

    private function validated(Request $request, ?User $account = null): array
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($account?->id)],
            'password'      => [$account ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'permissions'   => 'nullable|array',
            'permissions.*' => ['string', Rule::in(array_keys(User::PERMISSIONS))],
        ]);

        $data['permissions'] = array_values(array_unique($data['permissions'] ?? []));

        return $data;
    }

    private function endSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
