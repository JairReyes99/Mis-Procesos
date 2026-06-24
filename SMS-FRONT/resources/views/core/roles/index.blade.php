@extends('layout.default')

@section('title', 'Gestión de Roles')

@section('content')
<div class="card">
    <div class="card-h">
        <div>
            <h3>Roles</h3>
            <p>Administración de roles del sistema</p>
        </div>
        <div style="margin-left:auto;">
            @if($p_crear)
                <a href="{{ route('management.roles.create') }}" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                    Nuevo Rol
                </a>
            @endif
        </div>
    </div>
    <div class="card-b">
        <table class="tbl" id="kt_datatable_roles" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Guard</th>
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

    var baseUrl   = "{{ url('management/roles') }}";
    var csrfToken = "{{ csrf_token() }}";
    var canEdit   = {{ $p_editar   ? 'true' : 'false' }};
    var canDelete = {{ $p_eliminar ? 'true' : 'false' }};

    var table = $('#kt_datatable_roles').DataTable({
        responsive: true,
        searchDelay: 500,
        processing: true,
        serverSide: true,
        language: window.DT_ES,
        ajax: {
            url: "{{ route('management.roles.index') }}",
            type: 'GET',
        },
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'name', name: 'name' },
            { data: 'guard_name', name: 'guard_name', width: '100px',
              render: function(d){ return '<span class="pill pill-muted">' + d + '</span>'; }
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false, width: '100px',
                render: function (data, type, row) {
                    var btn = '<div style="display:flex;align-items:center;gap:4px;">';
                    if (canEdit) {
                        btn += '<a href="' + baseUrl + '/' + data + '/edit" class="btn-icon" title="Editar"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                    }
                    if (canDelete) {
                        btn += '<button type="button" data-id="' + data + '" data-name="' + row.name + '" class="btn-icon btn-delete-role" title="Eliminar" style="color:var(--err);"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                    }
                    btn += '</div>';
                    return btn || '<span class="pill pill-err">Sin permisos</span>';
                }
            },
        ],
    });

    $(document).on('click', '.btn-delete-role', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title: '¿Eliminar rol?',
            html: 'Se eliminará el rol <strong>' + name + '</strong>.<br>Solo se puede eliminar si no tiene usuarios asignados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + '/' + id, type: 'DELETE',
                    data: { _token: csrfToken },
                    success: function (res) {
                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al eliminar.');
                    }
                });
            }
        });
    });
</script>
@endpush
