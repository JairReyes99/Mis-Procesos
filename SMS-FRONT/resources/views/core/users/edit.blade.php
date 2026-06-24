@extends('layout.default')

@section('title', 'Editar Usuario')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Card: datos del usuario --}}
    <div class="card">
        <div class="card-h">
            <div>
                <h3>
                    Editar Usuario:
                    <span style="color:var(--accent-ink)">{{ $user->name }}</span>
                    @if($user->must_change_password)
                        <span class="pill pill-warn" style="margin-left:6px;">Contraseña temporal</span>
                    @endif
                </h3>
                <p>Modifica los datos de la cuenta</p>
            </div>
            <a href="{{ route('management.accounts.index') }}" class="btn btn-sm" style="margin-left:auto;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
        </div>

        <form action="{{ route('management.accounts.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-b" style="display:flex;flex-direction:column;gap:14px;">

                @if($errors->any())
                    <div class="alert-danger" style="border-radius:var(--radius-sm);padding:12px;">
                        <ul style="margin:0;padding-left:16px;">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="field">
                    <label for="name">Nombre <span class="req">*</span></label>
                    <input type="text" id="name" name="name"
                           class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name', $user->name) }}" />
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email"
                           class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           value="{{ old('email', $user->email) }}" />
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                {{-- Password --}}
                <div class="field">
                    <label>Nueva Contraseña</label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start;">
                        <div style="flex:1;min-width:0;position:relative;">
                            <input type="password" id="input_password" name="password"
                                   class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   placeholder="Dejar vacío para no cambiar" />
                            <button type="button" id="btn-toggle-pass"
                                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--ink-3);cursor:pointer;padding:2px;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            @error('password')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div style="width:180px;">
                            <input type="password" name="password_confirmation"
                                   class="input {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}" placeholder="Confirmar" />
                            @error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <button type="button" id="btn-reset-password"
                                class="btn btn-sm btn-warning"
                                data-id="{{ $user->id }}"
                                title="Asigna contraseña por defecto ({{ \App\Models\User::DEFAULT_PASSWORD }}) y obliga al usuario a cambiarla">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reset contraseña
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="role_id">Rol</label>
                    <select id="role_id" name="role_id" class="select select2 {{ $errors->has('role_id') ? 'is-invalid' : '' }}">
                        <option value="">-- Sin rol --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ old('role_id', $user->active_role_id) == $role->id ? 'selected' : '' }}>
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
                            <option value="{{ $company->id }}"
                                {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label>Estado</label>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="active" value="1"
                               style="width:14px;height:14px;cursor:pointer;"
                               {{ old('active', $user->status_id == 1 ? '1' : '') ? 'checked' : '' }} />
                        <span style="font-size:13px;color:var(--ink-2);">Cuenta activa</span>
                    </label>
                </div>

            </div>
            <div class="card-f">
                <a href="{{ route('management.accounts.index') }}" class="btn">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    {{-- Card: permisos directos --}}
    @can('editar.usuarios')
    <div class="card">
        <div class="card-h">
            <div>
                <h3>Permisos Directos del Usuario</h3>
                <p>Permisos asignados individualmente (independientes del rol)</p>
            </div>
            <button type="button" id="btn-reset-permissions"
                    class="btn btn-sm btn-danger"
                    data-id="{{ $user->id }}"
                    style="margin-left:auto;"
                    title="Elimina todos los permisos directos del usuario">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Reset permisos
            </button>
        </div>
        <div class="card-b">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <label class="form-label" style="margin:0;">Filtrar:</label>
                <select id="filter-user-permissions" class="select" style="width:auto;">
                    <option value="">Todos</option>
                    <option value="with_permissions">Con permisos</option>
                    <option value="no_permissions">Sin permisos</option>
                </select>
            </div>

            <div class="tbl-wrap">
                <table id="tabla_user_permissions" class="tbl" style="width:100%">
                    <thead>
                        <tr>
                            <th>Menú</th>
                            <th>Módulo</th>
                            <th style="width:50%">Permisos</th>
                            <th style="display:none;">conteo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $item)
                            <tr>
                                <td>{{ $item->menu->name ?? 'N/A' }}</td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    <select class="select select2-user-perm" multiple="multiple"
                                            data-module-id="{{ $item->id }}">
                                        @foreach($item->permissions as $perm)
                                            <option value="{{ $perm->id }}"
                                                {{ $perm->check_alias ? 'selected' : '' }}>
                                                {{ $perm->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="display:none;">{{ $item->permissions_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endcan

</div>
@endsection

@push('scripts')
<script>
    var userId        = {{ $user->id }};
    var resetPassUrl  = "{{ route('management.accounts.reset_password', $user->id) }}";
    var permUrl       = "{{ route('management.accounts.update_permission', $user->id) }}";
    var resetPermUrl  = "{{ route('management.accounts.reset_permissions', $user->id) }}";
    var csrfToken     = "{{ csrf_token() }}";

    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });

        $('#btn-toggle-pass').on('click', function () {
            var inp = $('#input_password');
            inp.attr('type', inp.attr('type') === 'password' ? 'text' : 'password');
        });

        $('#btn-reset-password').on('click', function () {
            Swal.fire({
                title: '¿Resetear contraseña?',
                html: 'Se asignará la contraseña por defecto <strong>{{ \App\Models\User::DEFAULT_PASSWORD }}</strong>.<br>El usuario <strong>deberá cambiarla</strong> al iniciar sesión.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, resetear',
                cancelButtonText: 'Cancelar',
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.post(resetPassUrl, { _token: csrfToken }, function (res) {
                    toastr.success(res.message);
                }).fail(function (xhr) {
                    toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al resetear contraseña.');
                });
            });
        });

        var permTable = $('#tabla_user_permissions').DataTable({
            responsive: true,
            paging: false,
            info: false,
            searching: false,
            order: [[3, 'desc']],
            columnDefs: [{ targets: 3, visible: false }],
        });

        $('#filter-user-permissions').on('change', function () {
            var val = $(this).val();
            $.fn.dataTable.ext.search.pop();
            if (val) {
                $.fn.dataTable.ext.search.push(function (settings, data) {
                    var count = parseInt(data[3]) || 0;
                    if (val === 'with_permissions') return count > 0;
                    if (val === 'no_permissions')   return count === 0;
                    return true;
                });
            }
            permTable.draw();
        });

        $('.select2-user-perm').select2({ placeholder: 'Seleccione permisos', allowClear: true, width: '100%' });

        $('.select2-user-perm').on('select2:select', function (e) {
            updateDirectPermission(e.params.data.id, true);
        });
        $('.select2-user-perm').on('select2:unselect', function (e) {
            updateDirectPermission(e.params.data.id, false);
        });

        function updateDirectPermission(pid, checked) {
            $.post(permUrl, { _token: csrfToken, pid: pid, checked: checked }, function (res) {
                toastr.success(res.message);
            }).fail(function () {
                toastr.error('Error al actualizar el permiso.');
            });
        }

        $('#btn-reset-permissions').on('click', function (e) {
            e.stopPropagation();
            Swal.fire({
                title: '¿Resetear todos los permisos directos?',
                text: 'Se eliminarán TODOS los permisos asignados directamente al usuario.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, resetear',
                cancelButtonText: 'Cancelar',
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.post(resetPermUrl, { _token: csrfToken }, function (res) {
                    toastr.success(res.message);
                    $('.select2-user-perm').val(null).trigger('change');
                    permTable.rows().every(function () {
                        var rowData = this.data();
                        rowData[3] = '0';
                        this.data(rowData);
                    });
                    permTable.draw();
                }).fail(function () {
                    toastr.error('Error al resetear los permisos.');
                });
            });
        });
    });
</script>
@endpush
