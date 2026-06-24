@extends('layout.default')

@section('title', 'Crear Empresa')

@section('content')
<div class="card" style="max-width:720px;margin:0 auto;">
    <div class="card-h">
        <div>
            <h3>Nueva Empresa</h3>
            <p>Registrar una nueva empresa / tenant</p>
        </div>
        <a href="{{ route('management.companies.index') }}" class="btn btn-sm" style="margin-left:auto;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver
        </a>
    </div>

    <form action="{{ route('management.companies.store') }}" method="POST">
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

            <div class="field" style="grid-column:1/-1;">
                <label for="name">Nombre <span class="req">*</span></label>
                <input type="text" id="name" name="name"
                       class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                       value="{{ old('name') }}" placeholder="Nombre de la empresa" />
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="rfc">RFC</label>
                <input type="text" id="rfc" name="rfc"
                       class="input {{ $errors->has('rfc') ? 'is-invalid' : '' }}"
                       value="{{ old('rfc') }}" placeholder="RFC (máx. 13 caracteres)" maxlength="13" />
                @error('rfc')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email"
                       class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       value="{{ old('email') }}" placeholder="contacto@empresa.com" />
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="phone">Teléfono</label>
                <input type="text" id="phone" name="phone"
                       class="input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                       value="{{ old('phone') }}" placeholder="+52 55 0000 0000" maxlength="20" />
                @error('phone')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="status_id">Estatus <span class="req">*</span></label>
                <select id="status_id" name="status_id" class="select {{ $errors->has('status_id') ? 'is-invalid' : '' }}">
                    <option value="1" {{ old('status_id', 1) == 1 ? 'selected' : '' }}>Activo</option>
                    <option value="2" {{ old('status_id', 1) == 2 ? 'selected' : '' }}>Inactivo</option>
                </select>
                @error('status_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>

        </div>
        <div class="card-f">
            <a href="{{ route('management.companies.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Guardar
            </button>
        </div>
    </form>
</div>
@endsection
