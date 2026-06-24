@extends('layout.default')

@section('title', 'Edición de Rol')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Card: datos del rol --}}
    <div class="card" style="max-width:720px;margin:0 auto;">
        <div class="card-h">
            <div>
                <h3>{{ $role->exists ? 'Edición: ' . $role->name : 'Nuevo Rol' }}</h3>
                <p>{{ $role->exists ? 'Modifica el nombre del rol' : 'Crear un nuevo rol' }}</p>
            </div>
            <a href="{{ route('management.roles.index') }}" class="btn btn-sm" style="margin-left:auto;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
        </div>
        <form action="{{ $role->exists ? route('management.roles.update', $role->id) : route('management.roles.store') }}"
              method="POST">
            @csrf
            @if($role->exists) @method('PUT') @endif
            <div class="card-b" style="display:flex;flex-direction:column;gap:14px;">
                <div class="field">
                    <label for="name">Nombre del Rol</label>
                    <input type="text" id="name" name="name"
                           class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name', $role->name) }}" />
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="card-f">
                <a href="{{ route('management.roles.index') }}" class="btn">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Guardar
                </button>
            </div>
        </form>
    </div>

    {{-- Card: permisos del rol --}}
    @if($role->exists)
    <div class="card">
        <div class="card-h">
            <div>
                <h3>Administración de Permisos</h3>
                <p>Asignar permisos al rol: <strong>{{ $role->name }}</strong></p>
            </div>
        </div>
        <div class="card-b">

            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <label class="form-label" style="margin:0;">Filtrar por Permisos:</label>
                <select id="filter-permissions" class="select" style="width:auto;">
                    <option value="">Todas</option>
                    <option value="no_permissions">Sin permisos</option>
                    <option value="with_permissions">Con permisos</option>
                </select>
            </div>

            <div class="tbl-wrap">
                <table id="tabla_permission" class="tbl" style="width:100%">
                    <thead>
                        <tr>
                            <th>Componente (Menú)</th>
                            <th>Módulo (Submenú)</th>
                            <th style="width:50%">Permisos</th>
                            <th style="display:none;">Conteo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($modules as $item)
                            <tr>
                                <td>{{ $item->menu->name ?? 'N/A' }}</td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    <select class="select multiple-select2" multiple="multiple"
                                            data-module-id="{{ $item->id }}">
                                        @foreach ($item->permissions as $permission)
                                            <option value="{{ $permission->id }}"
                                                {{ $permission->check_alias ? 'selected' : '' }}>
                                                {{ $permission->name }}
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
    @endif

</div>
@endsection

@push('scripts')
<script>
    var role_id = "{{ $role->id }}";
    var update_permission_url = "{{ route('management.roles.update_permission') }}";
    var csrf_token = "{{ csrf_token() }}";

    $(document).ready(function () {
        $('.multiple-select2').select2({
            placeholder: "Seleccione permisos",
            allowClear: true,
            width: '100%'
        });

        var table = $('#tabla_permission').DataTable({
            responsive: true,
            paging: false,
            info: false,
            order: [[3, 'desc']],
            columnDefs: [{ targets: 3, visible: false }]
        });

        $('#filter-permissions').on('change', function () {
            var value = $(this).val();
            $.fn.dataTable.ext.search.pop();
            if (value !== '') {
                $.fn.dataTable.ext.search.push(function (settings, data) {
                    var count = parseInt(data[3]) || 0;
                    if (value === 'no_permissions')   return count === 0;
                    if (value === 'with_permissions') return count > 0;
                    return true;
                });
            }
            table.draw();
        });

        $('.multiple-select2').on('select2:select', function (e) {
            updatePermission(e.params.data.id, true);
        });
        $('.multiple-select2').on('select2:unselect', function (e) {
            updatePermission(e.params.data.id, false);
        });

        function updatePermission(pid, checked) {
            $.post(update_permission_url, {
                _token: csrf_token, role_id: role_id, pid: pid, checked: checked
            }, function (response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                } else {
                    toastr.error('Error al actualizar permiso');
                }
            }).fail(function () {
                toastr.error('Error de conexión');
            });
        }
    });
</script>
@endpush
