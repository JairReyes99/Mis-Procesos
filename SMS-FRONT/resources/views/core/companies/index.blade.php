@extends('layout.default')

@section('title', 'Gestión de Empresas')

@section('content')
<div class="card">
    <div class="card-h">
        <div>
            <h3>Empresas</h3>
            <p>Administración de empresas / tenants</p>
        </div>
        <div style="margin-left:auto;">
            @if($p_crear)
                <button type="button" class="btn btn-primary" id="btn-new-company">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                    Nueva Empresa
                </button>
            @endif
        </div>
    </div>
    <div class="card-b">
        <table class="tbl" id="kt_datatable_companies" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>RFC</th>
                    <th>Usuarios</th>
                    <th>Saldo</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Modal Create/Edit --}}
<div id="modal-company" style="display:none;position:fixed;inset:0;z-index:50;background:oklch(20% 0.01 100 / 0.4);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:32px;">
    <div class="card" style="width:100%;max-width:520px;max-height:90vh;overflow-y:auto;">
        <div class="card-h" style="position:sticky;top:0;background:var(--bg-elev);z-index:10;">
            <h3 id="modal-company-title">Nueva Empresa</h3>
            <button type="button" data-dismiss="modal" class="btn-icon" style="margin-left:auto;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="card-b">
            <form id="form-company" style="display:flex;flex-direction:column;gap:14px;">
                @csrf
                <input type="hidden" id="company_id" name="company_id" value="" />

                <div class="field">
                    <label for="c_name">Nombre <span class="req">*</span></label>
                    <input type="text" name="name" id="c_name" class="input"
                           placeholder="Nombre de la empresa" required />
                    <p id="err_c_name" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="c_rfc">RFC</label>
                    <input type="text" name="rfc" id="c_rfc" class="input"
                           placeholder="RFC (máx. 13 caracteres)" maxlength="13" />
                    <p id="err_c_rfc" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="c_email">Correo electrónico</label>
                    <input type="email" name="email" id="c_email" class="input"
                           placeholder="contacto@empresa.com" />
                    <p id="err_c_email" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="c_phone">Teléfono</label>
                    <input type="text" name="phone" id="c_phone" class="input"
                           placeholder="+52 55 0000 0000" maxlength="20" />
                    <p id="err_c_phone" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="c_status_id">Estatus <span class="req">*</span></label>
                    <select name="status_id" id="c_status_id" class="select" style="max-width:160px;" required>
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                    </select>
                    <p id="err_c_status_id" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div class="field">
                    <label for="c_sms_price">Precio SMS por segmento (MXN)</label>
                    <input type="number" name="sms_price_per_segment" id="c_sms_price" class="input"
                           placeholder="Dejar vacío para usar precio global" min="0" step="0.0001" style="max-width:220px;" />
                    <p style="font-size:11px;color:var(--ink-4);margin-top:4px;">Si se deja vacío se aplica el precio global del sistema.</p>
                    <p id="err_c_sms_price_per_segment" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>
            </form>
        </div>
        <div class="card-f" style="position:sticky;bottom:0;">
            <button type="button" class="btn" data-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btn-save-company">
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

    var storeUrl    = "{{ route('management.companies.store') }}";
    var baseUrl     = "{{ url('management/companies') }}";
    var toggleBase  = "{{ url('management/companies') }}";
    var creditsBase = "{{ url('management/companies') }}";
    var usersBase   = "{{ url('management/companies') }}";
    var csrfToken   = "{{ csrf_token() }}";
    var canEdit     = {{ $p_editar   ? 'true' : 'false' }};
    var canDelete   = {{ $p_eliminar ? 'true' : 'false' }};

    function showModal(id) {
        var el = document.getElementById(id);
        if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }
    function hideModal(id) {
        var el = document.getElementById(id);
        if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
    }
    function clearCompanyErrors() {
        $('#modal-company .field-error').text('').hide();
        $('#modal-company .input, #modal-company .select').removeClass('is-invalid');
    }

    document.querySelectorAll('[data-dismiss="modal"]').forEach(function (btn) {
        btn.addEventListener('click', function () { hideModal('modal-company'); });
    });
    document.getElementById('modal-company').addEventListener('click', function (e) {
        if (e.target === this) hideModal('modal-company');
    });

    var table = $('#kt_datatable_companies').DataTable({
        responsive: true,
        searchDelay: 500,
        processing: true,
        serverSide: true,
        language: window.DT_ES,
        ajax: { url: "{{ route('management.companies.index') }}", type: 'GET' },
        columns: [
            { data: 'id',   name: 'id',   width: '60px' },
            { data: 'name', name: 'name' },
            { data: 'rfc',  name: 'rfc',  render: function (d) { return d || '—'; } },
            { data: 'users_count', name: 'users_count', orderable: false, searchable: false,
              render: function (d) { return '<span class="pill">' + (d || 0) + '</span>'; }
            },
            { data: 'balance', name: 'balance', orderable: false, searchable: false,
              render: function (d) { return '$' + parseFloat(d || 0).toFixed(2); }
            },
            { data: 'status_id', name: 'status_id', orderable: false,
              render: function (d) {
                  return d == 1
                      ? '<span class="pill pill-ok"><span class="dot"></span>Activo</span>'
                      : '<span class="pill pill-err"><span class="dot"></span>Inactivo</span>';
              }
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false, width: '100px',
                render: function (data, type, row) {
                    var btn = '<div style="display:flex;align-items:center;gap:4px;">';
                    if (canEdit) {
                        btn += '<a href="' + usersBase + '/' + data + '/users" class="btn-icon" title="Usuarios" style="color:var(--accent);"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></a>';
                        btn += '<a href="' + creditsBase + '/' + data + '/credits" class="btn-icon" title="Créditos" style="color:var(--accent);"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v1m0 10v1M6 12h.01M18 12h.01"/><circle cx="12" cy="12" r="10"/></svg></a>';
                        btn += '<a href="javascript:;" data-id="' + data + '" class="btn-icon btn-edit-company" title="Editar"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                        var icon  = row.status_id == 1
                            ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>'
                            : '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                        var title = row.status_id == 1 ? 'Desactivar' : 'Activar';
                        btn += '<button type="button" data-id="' + data + '" class="btn-icon btn-toggle-status" title="' + title + '">' + icon + '</button>';
                    }
                    if (canDelete) {
                        btn += '<button type="button" data-id="' + data + '" data-name="' + row.name + '" class="btn-icon btn-delete-company" title="Eliminar" style="color:var(--err);"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                    }
                    btn += '</div>';
                    return btn || '<span class="pill pill-err">Sin permisos</span>';
                }
            },
        ],
    });

    $('#btn-new-company').on('click', function () {
        $('#form-company')[0].reset();
        $('#company_id').val('');
        $('#modal-company-title').text('Nueva Empresa');
        clearCompanyErrors();
        showModal('modal-company');
    });

    $(document).on('click', '.btn-edit-company', function () {
        var id = $(this).data('id');
        $.get(baseUrl + '/' + id + '/edit', function (data) {
            $('#company_id').val(data.id);
            $('#c_name').val(data.name);
            $('#c_rfc').val(data.rfc || '');
            $('#c_email').val(data.email || '');
            $('#c_phone').val(data.phone || '');
            $('#c_status_id').val(data.status_id);
            $('#c_sms_price').val(data.sms_price_per_segment || '');
            $('#modal-company-title').text('Editar Empresa');
            clearCompanyErrors();
            showModal('modal-company');
        });
    });

    $('#btn-save-company').on('click', function () {
        var id     = $('#company_id').val();
        var url    = id ? baseUrl + '/' + id : storeUrl;
        var method = id ? 'PUT' : 'POST';
        var formData = {
            _token:                csrfToken,
            name:                  $('#c_name').val(),
            rfc:                   $('#c_rfc').val(),
            email:                 $('#c_email').val(),
            phone:                 $('#c_phone').val(),
            status_id:             $('#c_status_id').val(),
            sms_price_per_segment: $('#c_sms_price').val(),
        };
        if (method === 'PUT') formData['_method'] = 'PUT';
        $.ajax({
            url: url, type: 'POST', data: formData,
            success: function (res) {
                toastr.success(res.success);
                hideModal('modal-company');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    clearCompanyErrors();
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        var fieldMap = {
                            name: 'c_name', rfc: 'c_rfc', email: 'c_email',
                            phone: 'c_phone', status_id: 'c_status_id'
                        };
                        var inputId = fieldMap[field];
                        if (inputId) {
                            $('#' + inputId).addClass('is-invalid');
                        }
                        $('#err_c_' + field).text(messages[0]).show();
                    });
                    toastr.error('Revisa los campos marcados en el formulario.');
                } else {
                    toastr.error('Error al guardar la empresa.');
                }
            }
        });
    });

    $(document).on('click', '.btn-toggle-status', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '¿Confirmar acción?',
            text: 'Se cambiará el estatus de la empresa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: toggleBase + '/' + id + '/toggle',
                    type: 'POST',
                    data: { _token: csrfToken, _method: 'PATCH' },
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

    $(document).on('click', '.btn-delete-company', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title: '¿Eliminar empresa?',
            html: 'Se eliminará <strong>' + name + '</strong>.<br>Solo se puede eliminar si no tiene usuarios asignados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + '/' + id,
                    type: 'DELETE',
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
