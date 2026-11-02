@extends('layouts.poin')

@section('title', 'Masuk')

@section('content')
<x-poin.auth-screen title="Masuk" subtitle="Masuk untuk mulai bermain dan mengumpulkan poin." active="login" watermark="login"
    :action="route('poin.login')" submit="Masuk" switch-text="Belum punya akun?" switch-link="Daftar" :switch-href="route('poin.register')">
    <x-poin.input label="EMAIL *" name="login" placeholder="nama@email.com" autocomplete="username" maxlength="150" :value="old('login')" :error="$errors->first('login')" />
    <x-poin.input label="KATA SANDI *" name="password" type="password" placeholder="••••••••" autocomplete="current-password" maxlength="100" :error="$errors->first('password')" />
</x-poin.auth-screen>
@endsection
