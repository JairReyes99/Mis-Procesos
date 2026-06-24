@extends('layout.default')

@section('title', 'Mi Perfil')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Banner contraseña temporal --}}
    @if($user->must_change_password)
        <div class="alert-warning" style="border-radius:var(--radius-sm);">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p style="font-weight:600;margin:0;">Contraseña temporal</p>
                <p style="font-size:13px;margin:2px 0 0;">
                    Estás usando una contraseña temporal. Debes cambiarla antes de poder navegar el sistema.
                    <a href="#card-password" style="text-decoration:underline;font-weight:500;">Ir a cambiar contraseña &darr;</a>
                </p>
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">

        {{-- Columna izquierda: info del usuario --}}
        <div class="card">
            <div class="card-b" style="text-align:center;padding:32px 18px;">
                <div style="width:72px;height:72px;border-radius:50%;background:var(--accent-soft);border:3px solid var(--accent-line);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <span style="font-size:28px;font-weight:700;color:var(--accent-ink);">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                </div>
                <div style="font-size:17px;font-weight:600;letter-spacing:-0.01em;">{{ $user->name }}</div>
                <div style="font-size:13px;color:var(--ink-3);margin-top:4px;">{{ $user->email }}</div>

                @if($user->activeRole)
                    <div style="margin-top:12px;">
                        <span class="badge badge-primary">{{ $user->activeRole->name }}</span>
                    </div>
                @endif

                <div style="margin-top:8px;">
                    @if($user->status_id == 1)
                        <span class="pill pill-ok"><span class="dot"></span>Activo</span>
                    @else
                        <span class="pill pill-err"><span class="dot"></span>Inactivo</span>
                    @endif
                </div>

                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--line);font-size:12px;color:var(--ink-4);">
                    Miembro desde {{ $user->created_at->format('d/m/Y') }}
                </div>
            </div>
        </div>

        {{-- Columna derecha: formularios --}}
        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Información personal --}}
            <div class="card">
                <div class="card-h">
                    <h3>Información Personal</h3>
                </div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-b" style="display:flex;flex-direction:column;gap:14px;">
                        @if($errors->has('name') || $errors->has('email'))
                            <div class="alert-danger" style="border-radius:var(--radius-sm);padding:12px;">
                                <ul style="margin:0;padding-left:16px;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="field">
                            <label for="name">Nombre</label>
                            <input type="text" id="name" name="name"
                                   class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   value="{{ old('name', $user->name) }}" required />
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email"
                                   class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   value="{{ old('email', $user->email) }}" required />
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="card-f">
                        <button type="submit" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Actualizar Perfil
                        </button>
                    </div>
                </form>
            </div>

            {{-- Cambio de contraseña --}}
            <div class="card" id="card-password" style="{{ $user->must_change_password ? 'outline:2px solid var(--warn);outline-offset:2px;' : '' }}">
                <div class="card-h" style="{{ $user->must_change_password ? 'background:var(--warn-soft);' : '' }}">
                    <div>
                        <h3>Cambiar Contraseña</h3>
                        @if($user->must_change_password)
                            <p style="color:var(--warn);font-weight:500;">Cambio requerido</p>
                        @endif
                    </div>
                    @if($user->must_change_password)
                        <span class="pill pill-warn" style="margin-left:auto;">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
                            Requerido
                        </span>
                    @endif
                </div>
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-b" style="display:flex;flex-direction:column;gap:14px;">
                        @if($errors->has('current_password') || $errors->has('password'))
                            <div class="alert-danger" style="border-radius:var(--radius-sm);padding:12px;">
                                <ul style="margin:0;padding-left:16px;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @unless($user->must_change_password)
                        <div class="field">
                            <label for="current_password">Contraseña Actual</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="input {{ $errors->has('current_password') ? 'is-invalid' : '' }}"
                                   placeholder="Contraseña actual" required />
                            @error('current_password')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        @endunless

                        <div class="field">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="password"
                                   class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   placeholder="Mínimo 8 caracteres" required />
                            @error('password')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="password_confirmation">Confirmar Contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="input" placeholder="Repetir contraseña" required />
                        </div>
                    </div>
                    <div class="card-f">
                        <button type="submit" class="btn btn-warning">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection

@if($user->must_change_password)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var el = document.getElementById('card-password');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 400);
    });
</script>
@endpush
@endif
