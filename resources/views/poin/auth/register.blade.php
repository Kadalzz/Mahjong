@extends('layouts.poin')

@section('title', 'Daftar')

@section('content')
<x-poin.auth-screen title="Buat Akun" subtitle="Poin kamu tercatat di leaderboard sepanjang tahun." active="register" watermark="register"
    :action="route('poin.register')" submit="Daftar" switch-text="Sudah punya akun?" switch-link="Masuk" :switch-href="route('poin.login')">
    <x-poin.input label="NAMA PEMAIN *" name="nickname" placeholder="mis. Rina W." autocomplete="username" maxlength="30" :value="old('nickname')" :error="$errors->first('nickname') ?: $errors->first('nickname_key')" />
    <x-poin.input label="EMAIL *" name="email" type="email" placeholder="nama@email.com" autocomplete="email" maxlength="150" :value="old('email')" :error="$errors->first('email')" />
    <x-poin.input label="KATA SANDI *" name="password" type="password" placeholder="••••••••" autocomplete="new-password" maxlength="100" :error="$errors->first('password')" />
</x-poin.auth-screen>
@endsection
