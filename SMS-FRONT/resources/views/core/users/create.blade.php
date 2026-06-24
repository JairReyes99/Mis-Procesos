@extends('layout.default')

@section('title', 'Crear Usuario')

@section('content')
<div class="card" style="max-width:720px;margin:0 auto;">
    <div class="card-h">
        <div>
            <h3>Nuevo Usuario</h3>
            <p>Crear una nueva cuenta de usuario</p>
        </div>
        <a href="{{ route('management.accounts.index') }}" class="btn btn-sm" style="margin-left:auto;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver
        </a>
    </div>

    <form action="{{ route('management.accounts.store') }}" method="POST">
        @csrf
        <div class="card-b" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

            @if($errors->any())
                <div style="grid-column:1/-1;border-radius:var(--radius-sm);padding:12px;" class="alert-danger">
                    <ul style="margin:0;padding-left:16px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="field">
                <label for="name">Nombre <span class="req">*</span></label>
                <input type="text" id="name" name="name"
                       class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                       value="{{ old('name') }}" placeholder="Nombre completo" />
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email"
                       class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       value="{{ old('email') }}" placeholder="correo@ejemplo.com" />
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="password">Contraseña <span class="req">*</span></label>
                <input type="password" id="password" name="password"
                       class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                       placeholder="Mínimo 8 caracteres" />
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirmar Contraseña <span class="req">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="input {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
                       placeholder="Repetir contraseña" />
                @error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="role_id">Rol <span class="req">*</span></label>
                <select id="role_id" name="role_id" class="select select2 {{ $errors->has('role_id') ? 'is-invalid' : '' }}">
                    <option value="">-- Sin rol --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="company_id">Empresa</label>
                <select id="company_id" name="company_id" class="select select2 {{ $errors->has('company_id') ? 'is-invalid' : '' }}">
                    <option value="">-- Sin empresa --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                @error('company_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>

        </div>
        <div class="card-f">
            <a href="{{ route('management.accounts.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Guardar
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
    });
</script>
@endpush
