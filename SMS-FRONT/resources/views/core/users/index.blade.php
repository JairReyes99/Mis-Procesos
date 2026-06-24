@extends('layout.default')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="card">
    <div class="card-h">
        <div>
            <h3>Usuarios</h3>
            <p>Administración de cuentas de usuario</p>
        </div>
        <div style="margin-left:auto;">
            @if($p_crear)
                <a href="{{ route('management.accounts.create') }}" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                    Nuevo Usuario
                </a>
            @endif
        </div>
    </div>
    <div class="card-b">
        <table class="tbl" id="kt_datatable_users" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol Activo</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    "use strict";

    var toggleUrl = "{{ url('management/accounts') }}";
    var editUrl   = "{{ url('management/accounts') }}";
    var csrfToken = "{{ csrf_token() }}";
    var canEdit   = {{ $p_editar   ? 'true' : 'false' }};
    var canDelete = {{ $p_eliminar ? 'true' : 'false' }};
    var authId    = {{ auth()->id() }};

    var table = $('#kt_datatable_users').DataTable({
        responsive: true,
        searchDelay: 500,
        processing: true,
        serverSide: true,
        language: window.DT_ES,
        ajax: {
            url: "{{ route('management.accounts.index') }}",
            type: 'GET',
        },
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            {
                data: 'role_name', name: 'role_name', orderable: false,
                render: function (data) {
                    return data
                        ? '<span class="badge badge-primary">' + data + '</span>'
                        : '<span class="badge badge-warning">Sin rol</span>';
                }
            },
            {
                data: 'status_id', name: 'status_id', orderable: false,
                render: function (data, type, row) {
                    var badge = data == 1
                        ? '<span class="pill pill-ok"><span class="dot"></span>Activo</span>'
                        : '<span class="pill pill-err"><span class="dot"></span>Inactivo</span>';
                    if (row.must_change_password) {
                        badge += ' <span class="pill pill-warn" title="Debe cambiar contraseña"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg></span>';
                    }
                    return badge;
                }
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false, width: '100px',
                render: function (data, type, row) {
                    var btn = '<div style="display:flex;align-items:center;gap:4px;">';
                    if (canEdit) {
                        btn += '<a href="' + editUrl + '/' + data + '/edit" class="btn-icon" title="Editar"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                    }
                    if (canDelete && data !== authId) {
                        var icon  = row.status_id == 1
                            ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>'
                            : '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                        var title = row.status_id == 1 ? 'Desactivar' : 'Activar';
                        btn += '<button type="button" data-id="' + data + '" class="btn-icon btn-toggle-status" title="' + title + '">' + icon + '</button>';
                    }
                    btn += '</div>';
                    return btn || '<span class="pill pill-err">Sin permisos</span>';
                }
            },
        ],
    });

    $(document).on('click', '.btn-toggle-status', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '¿Confirmar acción?',
            text: 'Se cambiará el estatus del usuario.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: toggleUrl + '/' + id,
                    type: 'DELETE',
                    data: { _token: csrfToken },
                    success: function (res) {
                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    },
                    error: function (res) {
                        toastr.error(res.responseJSON ? res.responseJSON.message : 'Error al procesar.');
                    }
                });
            }
        });
    });
</script>
@endpush
