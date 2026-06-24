<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Iniciar sesión — SMS Intelix</title>
    @vite(['resources/css/app.css'])
    <style>
        .login-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--bg);
        }
        .login-card {
            width: 100%;
            max-width: 380px;
        }
        .login-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-bottom: 32px;
            text-align: center;
        }
        .login-title {
            font-size: 22px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin: 12px 0 4px;
        }
        .login-sub { font-size: 13px; color: var(--ink-3); }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <svg width="40" height="40" viewBox="0 0 32 32" fill="none">
                <rect x="1" y="1" width="30" height="30" rx="8" fill="var(--ink)" />
                <path d="M10 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" />
                <path d="M7 20c0-5 4-9 9-9s9 4 9 9" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" opacity="0.55" />
                <circle cx="16" cy="20" r="2" fill="var(--accent)" />
            </svg>
            <div>
                <div class="login-title">SMS<span style="color:var(--accent)">·</span>Intelix</div>
                <div class="login-sub">Grupo Concentra</div>
            </div>
        </div>

        <div class="card">
            <div class="card-b" style="padding: 24px;">
                @if($errors->any())
                    <div style="padding: 10px 12px; background: var(--err-soft); border: 1px solid color-mix(in oklch, var(--err) 25%, transparent); border-radius: var(--radius-sm); color: var(--err); font-size: 13px; margin-bottom: 16px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="field" style="margin-bottom: 12px;">
                        <label for="email">Correo electrónico</label>
                        <input id="email" name="email" type="email"
                               class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               placeholder="nombre@grupoconcentra.com" value="{{ old('email') }}" autofocus />
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field" style="margin-bottom: 20px;">
                        <label for="password">Contraseña</label>
                        <input id="password" name="password" type="password"
                               class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="••••••••" />
                        @error('password')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        Iniciar sesión
                    </button>
                </form>
            </div>
        </div>
        <p style="text-align: center; margin-top: 20px; font-size: 12px; color: var(--ink-4);">
            © {{ date('Y') }} Grupo Concentra
        </p>
    </div>
</div>
</body>
</html>
