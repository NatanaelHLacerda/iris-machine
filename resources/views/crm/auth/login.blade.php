@extends('crm.layouts.guest')

@section('content')
    <main class="auth">
        <div class="auth__card">
            <span class="logo">Iris Machine</span>
            <div class="sidebar__tag">Gestão CRM</div>

            <h1 class="auth__title">Entrar</h1>
            <p class="muted" style="font-size:13px">Área restrita. Acesso só para o admin.</p>

            <form class="form-stack" method="POST" action="{{ route('crm.login.store') }}" style="margin-top:22px">
                @csrf
                <div class="field">
                    <label for="email">E-mail</label>
                    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    @error('email')
                        <p class="field__error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="field">
                    <label for="password">Senha</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <label class="check"><input type="checkbox" name="remember" value="1"> Manter conectado</label>
                <button class="btn btn--primary btn--block" type="submit">Entrar</button>
            </form>
        </div>
    </main>
@endsection
