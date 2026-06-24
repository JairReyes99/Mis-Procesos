@extends('layout.default')

@section('title', 'Gestión de Submenús')

@section('content')
<div class="card">
    <div class="card-h">
        <div>
            <h3>Submenús</h3>
            <p>Administración de módulos del sistema</p>
        </div>
        <div style="margin-left:auto;">
            @if($p_crear)
                <button type="button" class="btn btn-primary" id="btn-new-submenu">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                    Nuevo Submenú
                </button>
            @endif
        </div>
    </div>
    <div class="card-b">
        <table class="tbl" id="kt_datatable_submenus" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Menú Padre</th>
                    <th>Nombre</th>
                    <th>Ruta</th>
                    <th>Permiso</th>
                    <th>Orden</th>
                    <th>Visible</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Modal Create/Edit --}}
<div id="modal-submenu" style="display:none;position:fixed;inset:0;z-index:50;background:oklch(20% 0.01 100 / 0.4);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:32px;">
    <div class="card" style="width:100%;max-width:520px;max-height:90vh;overflow-y:auto;">
        <div class="card-h" style="position:sticky;top:0;background:var(--bg-elev);z-index:10;">
            <h3 id="modal-submenu-title">Nuevo Submenú</h3>
            <button type="button" data-dismiss="modal" class="btn-icon" style="margin-left:auto;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="card-b">
            <form id="form-submenu" style="display:flex;flex-direction:column;gap:14px;">
                @csrf
                <input type="hidden" id="submenu_id" name="submenu_id" value="" />

                <div class="field">
                    <label for="s_menu_id">Menú Padre <span class="req">*</span></label>
                    <select name="menu_id" id="s_menu_id" class="select select2-submenu" required>
                        <option value="">-- Seleccionar Menú --</option>
                        @foreach($menus as $menu)
                            <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                        @endforeach
                    </select>
                    <p id="err_s_menu_id" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="s_name">Nombre <span class="req">*</span></label>
                    <input type="text" name="name" id="s_name" class="input"
                           placeholder="Nombre del módulo" required />
                    <p id="err_s_name" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="s_route">Ruta (route name)</label>
                    <input type="text" name="route" id="s_route" class="input"
                           placeholder="ej: management.users.index" />
                    <p id="err_s_route" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="s_icon">Ícono</label>
                    <input type="text" name="icon" id="s_icon" class="input"
                           placeholder="ej: menu-bullet menu-bullet-dot" />
                    <p id="err_s_icon" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="s_permission">Permiso</label>
                    <input type="text" name="permission" id="s_permission" class="input"
                           placeholder="ej: ver.usuarios" />
                    <p class="field-help">El permiso que el usuario debe tener para ver este módulo.</p>
                    <p id="err_s_permission" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="s_order">Orden</label>
                    <input type="number" name="order" id="s_order" class="input" style="max-width:120px;" value="0" min="0" />
                    <p id="err_s_order" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label>Visible en menú</label>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" id="s_visible_menu" name="visible_menu" value="1" checked
                               style="width:14px;height:14px;" />
                        <span style="font-size:13px;color:var(--ink-2);">Mostrar en el menú lateral</span>
                    </label>
                </div>

                <div id="s_status_group" style="display:none;">
                    <div class="field">
                        <label for="s_status_id">Estatus</label>
                        <select name="status_id" id="s_status_id" class="select" style="max-width:160px;">
                            <option value="1">Activo</option>
                            <option value="2">Inactivo</option>
                        </select>
                    </div>
                </div>

            </form>
        </div>
        <div class="card-f" style="position:sticky;bottom:0;">
            <button type="button" class="btn" data-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btn-save-submenu">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Guardar
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    "use strict";

    var storeUrl  = "{{ route('management.submenus.store') }}";
    var baseUrl   = "{{ url('management/submenus') }}";
    var csrfToken = "{{ csrf_token() }}";
    var canEdit   = {{ $p_editar   ? 'true' : 'false' }};
    var canDelete = {{ $p_eliminar ? 'true' : 'false' }};

    function showModal(id) {
        var el = document.getElementById(id);
        if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }
    function hideModal(id) {
        var el = document.getElementById(id);
        if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
    }
    function clearSubmenuErrors() {
        $('#modal-submenu .field-error').text('').hide();
        $('#modal-submenu .input, #modal-submenu .select').removeClass('is-invalid');
    }
    document.querySelectorAll('[data-dismiss="modal"]').forEach(function(btn){
        btn.addEventListener('click', function(){ hideModal('modal-submenu'); });
    });
    document.getElementById('modal-submenu').addEventListener('click', function(e){
        if (e.target === this) hideModal('modal-submenu');
    });

    var table = $('#kt_datatable_submenus').DataTable({
        responsive: true,
        searchDelay: 500,
        processing: true,
        serverSide: true,
        language: window.DT_ES,
        ajax: { url: "{{ route('management.submenus.index') }}", type: 'GET' },
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'menu_name', name: 'menu.name' },
            { data: 'name', name: 'name' },
            { data: 'route', name: 'route', render: function(d){ return d ? '<code style="font-family:var(--font-mono);font-size:11px;background:var(--bg-muted);padding:2px 5px;border-radius:4px;border:1px solid var(--line);">' + d + '</code>' : '—'; } },
            { data: 'permission', name: 'permission', render: function(d){ return d ? '<code style="font-family:var(--font-mono);font-size:11px;background:var(--bg-muted);padding:2px 5px;border-radius:4px;border:1px solid var(--line);">' + d + '</code>' : '—'; } },
            { data: 'order', name: 'order', width: '70px' },
            { data: 'visible_menu', name: 'visible_menu', orderable: false,
              render: function(d){ return d ? '<span class="pill pill-ok"><span class="dot"></span>Visible</span>' : '<span class="pill pill-muted">Oculto</span>'; }
            },
            { data: 'status_id', name: 'status_id', orderable: false,
              render: function(d){ return d == 1 ? '<span class="pill pill-ok"><span class="dot"></span>Activo</span>' : '<span class="pill pill-err"><span class="dot"></span>Inactivo</span>'; }
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false, width: '100px',
                render: function (data) {
                    var btn = '<div style="display:flex;align-items:center;gap:4px;">';
                    if (canEdit)   btn += '<a href="javascript:;" data-id="' + data + '" class="btn-icon btn-edit-submenu" title="Editar"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                    if (canDelete) btn += '<button type="button" data-id="' + data + '" class="btn-icon btn-delete-submenu" title="Eliminar" style="color:var(--err);"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                    btn += '</div>';
                    return btn || '<span class="pill pill-err">Sin permisos</span>';
                }
            },
        ],
    });

    function initModalSelect2() {
        $('#s_menu_id').select2({ dropdownParent: $('#modal-submenu'), width: '100%' });
    }

    $('#btn-new-submenu').on('click', function () {
        $('#form-submenu')[0].reset();
        $('#submenu_id').val('');
        $('#s_status_group').hide();
        $('#s_visible_menu').prop('checked', true);
        $('#modal-submenu-title').text('Nuevo Submenú');
        clearSubmenuErrors();
        showModal('modal-submenu');
        initModalSelect2();
    });

    $(document).on('click', '.btn-edit-submenu', function () {
        var id = $(this).data('id');
        $.get(baseUrl + '/' + id + '/edit', function (data) {
            $('#submenu_id').val(data.id);
            $('#s_name').val(data.name);
            $('#s_route').val(data.route);
            $('#s_icon').val(data.icon);
            $('#s_permission').val(data.permission);
            $('#s_order').val(data.order);
            $('#s_visible_menu').prop('checked', data.visible_menu == 1);
            $('#s_status_id').val(data.status_id);
            $('#s_status_group').show();
            $('#modal-submenu-title').text('Editar Submenú');
            clearSubmenuErrors();
            showModal('modal-submenu');
            initModalSelect2();
            $('#s_menu_id').val(data.menu_id).trigger('change');
        });
    });

    $('#btn-save-submenu').on('click', function () {
        var id      = $('#submenu_id').val();
        var url     = id ? baseUrl + '/' + id : storeUrl;
        var method  = id ? 'PUT' : 'POST';
        var visible = $('#s_visible_menu').is(':checked') ? 1 : 0;
        var formData = {
            _token: csrfToken, menu_id: $('#s_menu_id').val(), name: $('#s_name').val(),
            route: $('#s_route').val(), icon: $('#s_icon').val(), permission: $('#s_permission').val(),
            order: $('#s_order').val(), visible_menu: visible, status_id: $('#s_status_id').val() || 1,
        };
        if (method === 'PUT') formData['_method'] = 'PUT';
        $.ajax({
            url: url, type: 'POST', data: formData,
            success: function (res) {
                toastr.success(res.success);
                hideModal('modal-submenu');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    clearSubmenuErrors();
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        var fieldMap = {
                            menu_id: 's_menu_id', name: 's_name', route: 's_route',
                            icon: 's_icon', permission: 's_permission', order: 's_order'
                        };
                        var inputId = fieldMap[field];
                        if (inputId) {
                            $('#' + inputId).addClass('is-invalid');
                        }
                        $('#err_s_' + field).text(messages[0]).show();
                    });
                    toastr.error('Revisa los campos marcados en el formulario.');
                } else {
                    toastr.error('Error al guardar el submenú.');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-submenu', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar submenú?', text: 'Esta acción no se puede deshacer.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + '/' + id, type: 'DELETE', data: { _token: csrfToken },
                    success: function (res) { toastr.success(res.success); table.ajax.reload(null, false); },
                    error: function () { toastr.error('Error al eliminar el submenú.'); }
                });
            }
        });
    });
</script>
@endpush
