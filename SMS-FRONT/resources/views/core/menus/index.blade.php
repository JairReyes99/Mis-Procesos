@extends('layout.default')

@section('title', 'Gestión de Menús')

@section('content')
<div class="card">
    <div class="card-h">
        <div>
            <h3>Menús</h3>
            <p>Administración de menús del sistema</p>
        </div>
        <div style="margin-left:auto;">
            @if($p_crear)
                <button type="button" class="btn btn-primary" id="btn-new-menu">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                    Nuevo Menú
                </button>
            @endif
        </div>
    </div>
    <div class="card-b">
        <table class="tbl" id="kt_datatable_menus" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Ícono</th>
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
<div id="modal-menu" style="display:none;position:fixed;inset:0;z-index:50;background:oklch(20% 0.01 100 / 0.4);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:32px;">
    <div class="card" style="width:100%;max-width:480px;">
        <div class="card-h">
            <h3 id="modal-menu-title">Nuevo Menú</h3>
            <button type="button" data-dismiss="modal" class="btn-icon" style="margin-left:auto;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-menu">
            @csrf
            <input type="hidden" name="id" id="menu_id" />
            <div class="card-b" style="display:flex;flex-direction:column;gap:14px;">
                <div class="field">
                    <label for="m_name">Nombre <span class="req">*</span></label>
                    <input type="text" class="input" name="name" id="m_name" required />
                    <p id="err_m_name" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>
                <div class="field" style="position:relative;">
                    <label for="m_icon">Ícono <span style="font-weight:400;color:var(--ink-3);">— Tabler Icons</span></label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <div style="position:relative;flex:1;">
                            <input type="text" class="input" name="icon" id="m_icon" placeholder="ti ti-settings" autocomplete="off" style="width:100%;" />
                            {{-- Dropdown picker --}}
                            <div id="icon-picker" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg);border:1px solid var(--line);border-radius:8px;box-shadow:0 8px 24px oklch(0% 0 0 / .14);z-index:200;overflow:hidden;">
                                <div style="padding:8px;">
                                    <input type="text" id="icon-search" class="input" placeholder="Buscar icono…" style="width:100%;box-sizing:border-box;" autocomplete="off" />
                                </div>
                                <div id="icon-grid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;padding:0 8px 8px;max-height:200px;overflow-y:auto;"></div>
                                <div id="icon-empty" style="display:none;padding:12px;text-align:center;color:var(--ink-3);font-size:12px;">Sin resultados</div>
                            </div>
                        </div>
                        <i id="m_icon_preview" class="ti ti-settings" style="font-size:20px;color:var(--ink-2);flex-shrink:0;width:24px;text-align:center;"></i>
                    </div>
                    <p id="err_m_icon" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>
                <div class="field">
                    <label for="m_order">Orden</label>
                    <input type="number" class="input" name="order" id="m_order" value="0" min="0" />
                    <p id="err_m_order" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>
                <div class="field">
                    <label for="m_visible">Visible en menú</label>
                    <select class="select" name="visible_menu" id="m_visible">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                    <p id="err_m_visible_menu" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>
            </div>
            <div class="card-f">
                <button type="button" class="btn" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btn-save-menu">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    "use strict";

    var storeUrl  = "{{ route('management.menus.store') }}";
    var baseUrl   = "{{ url('management/menus') }}";
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
    function clearMenuErrors() {
        $('#modal-menu .field-error').text('').hide();
        $('#modal-menu .input, #modal-menu .select').removeClass('is-invalid');
    }
    document.querySelectorAll('[data-dismiss="modal"]').forEach(function(btn){
        btn.addEventListener('click', function(){ hideModal('modal-menu'); });
    });
    document.getElementById('modal-menu').addEventListener('click', function(e){
        if (e.target === this) hideModal('modal-menu');
    });

    var table = $('#kt_datatable_menus').DataTable({
        responsive: true,
        searchDelay: 500,
        processing: true,
        serverSide: true,
        language: window.DT_ES,
        ajax: { url: "{{ route('management.menus.index') }}", type: 'GET' },
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'name', name: 'name' },
            { data: 'icon', name: 'icon', orderable: false,
              render: function (d) { return d ? '<span style="display:flex;align-items:center;gap:6px;"><i class="' + d + '" style="font-size:16px;"></i><code style="font-family:var(--font-mono);font-size:11px;background:var(--bg-muted);padding:2px 6px;border-radius:4px;border:1px solid var(--line);">' + d + '</code></span>' : '—'; }
            },
            { data: 'order', name: 'order', width: '70px' },
            { data: 'visible_menu', name: 'visible_menu', orderable: false,
              render: function (d) { return d ? '<span class="pill pill-ok"><span class="dot"></span>Visible</span>' : '<span class="pill pill-err"><span class="dot"></span>Oculto</span>'; }
            },
            { data: 'status_id', name: 'status_id', orderable: false,
              render: function (d) { return d == 1 ? '<span class="pill pill-ok"><span class="dot"></span>Activo</span>' : '<span class="pill pill-err"><span class="dot"></span>Inactivo</span>'; }
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false, width: '100px',
                render: function (data) {
                    var btn = '<div style="display:flex;align-items:center;gap:4px;">';
                    if (canEdit)   btn += '<a href="javascript:;" data-id="' + data + '" class="btn-icon edit-menu" title="Editar"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                    if (canDelete) btn += '<button type="button" data-id="' + data + '" class="btn-icon btn-delete-menu" title="Eliminar" style="color:var(--err);"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                    btn += '</div>';
                    return btn || '<span class="pill pill-err">Sin permisos</span>';
                }
            },
        ],
    });

    $('#btn-new-menu').on('click', function () {
        $('#form-menu')[0].reset();
        $('#menu_id').val('');
        $('#modal-menu-title').text('Nuevo Menú');
        clearMenuErrors();
        showModal('modal-menu');
    });

    // --- Icon picker ---
    var ICONS = [
        'ti-home','ti-settings','ti-users','ti-user','ti-user-plus','ti-user-check',
        'ti-mail','ti-mail-opened','ti-inbox','ti-send',
        'ti-phone','ti-phone-call','ti-device-mobile',
        'ti-message','ti-messages','ti-message-circle','ti-message-dots',
        'ti-bell','ti-bell-ringing','ti-alert-circle','ti-info-circle','ti-help-circle',
        'ti-check','ti-check-circle','ti-x','ti-x-circle','ti-ban',
        'ti-lock','ti-lock-open','ti-shield','ti-shield-check',
        'ti-chart-bar','ti-chart-pie','ti-chart-line','ti-trending-up','ti-trending-down',
        'ti-table','ti-list','ti-list-check','ti-filter','ti-search',
        'ti-file','ti-file-text','ti-files','ti-file-plus','ti-file-download','ti-file-upload',
        'ti-folder','ti-folder-open','ti-folder-plus',
        'ti-download','ti-upload','ti-refresh','ti-reload',
        'ti-edit','ti-pencil','ti-trash','ti-copy','ti-clipboard',
        'ti-plus','ti-minus','ti-circle-plus','ti-circle-minus',
        'ti-eye','ti-eye-off','ti-star','ti-heart','ti-bookmark',
        'ti-tag','ti-tags','ti-hash','ti-link','ti-external-link',
        'ti-calendar','ti-calendar-event','ti-clock','ti-history',
        'ti-map','ti-map-pin','ti-world','ti-globe',
        'ti-building','ti-building-store','ti-office','ti-briefcase',
        'ti-credit-card','ti-wallet','ti-cash','ti-coins','ti-receipt',
        'ti-truck','ti-package','ti-box','ti-archive',
        'ti-cpu','ti-server','ti-database','ti-cloud','ti-wifi',
        'ti-code','ti-terminal','ti-api','ti-bug','ti-git-branch',
        'ti-palette','ti-photo','ti-camera','ti-video','ti-music',
        'ti-logout','ti-login','ti-key','ti-fingerprint',
        'ti-tool','ti-tools','ti-adjustments','ti-sliders',
        'ti-layout-dashboard','ti-layout-grid','ti-layout-list','ti-menu-2',
        'ti-dots','ti-dots-vertical','ti-grid-dots','ti-apps',
        'ti-arrow-up','ti-arrow-down','ti-arrow-left','ti-arrow-right',
        'ti-chevron-up','ti-chevron-down','ti-chevron-left','ti-chevron-right',
        'ti-sort-ascending','ti-sort-descending',
        'ti-report','ti-report-analytics','ti-presentation','ti-notes',
        'ti-headset','ti-headphones','ti-broadcast','ti-antenna',
        'ti-topology-star','ti-sitemap','ti-hierarchy','ti-share',
        'ti-qrcode','ti-barcode','ti-scan','ti-id-badge',
        'ti-printer','ti-device-desktop','ti-device-laptop',
        'ti-sun','ti-moon','ti-temperature','ti-wind',
        'ti-flag','ti-pin','ti-location','ti-compass',
    ];

    function renderIcons(filter) {
        var grid = document.getElementById('icon-grid');
        var empty = document.getElementById('icon-empty');
        var q = (filter || '').toLowerCase().replace(/^ti(-ti)?-/, '');
        var list = q ? ICONS.filter(function(i){ return i.indexOf(q) !== -1; }) : ICONS;
        grid.innerHTML = '';
        if (!list.length) { empty.style.display='block'; return; }
        empty.style.display = 'none';
        list.forEach(function(name) {
            var cls = 'ti ' + name;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'ti ' + name;
            btn.style.cssText = 'display:flex;align-items:center;justify-content:center;width:100%;aspect-ratio:1;border:1px solid transparent;border-radius:6px;background:none;cursor:pointer;font-size:18px;color:var(--ink-2);transition:background .12s,border-color .12s;';
            btn.innerHTML = '<i class="' + cls + '"></i>';
            btn.addEventListener('mouseenter', function(){ this.style.background='var(--bg-muted)'; this.style.borderColor='var(--line)'; });
            btn.addEventListener('mouseleave', function(){ this.style.background='none'; this.style.borderColor='transparent'; });
            btn.addEventListener('click', function() {
                $('#m_icon').val(cls).trigger('input');
                closePicker();
            });
            grid.appendChild(btn);
        });
    }

    function openPicker() {
        renderIcons($('#m_icon').val());
        $('#icon-search').val('');
        $('#icon-picker').show();
        setTimeout(function(){ $('#icon-search').focus(); }, 50);
    }
    function closePicker() { $('#icon-picker').hide(); }

    $('#m_icon').on('focus', function() { openPicker(); });
    $('#m_icon').on('input', function () {
        var val = $(this).val();
        $('#m_icon_preview').attr('class', val || 'ti ti-photo-off');
        renderIcons(val);
        $('#icon-picker').show();
    });
    $('#icon-search').on('input', function() { renderIcons($(this).val()); });

    $(document).on('mousedown', function(e) {
        if (!$(e.target).closest('#icon-picker, #m_icon').length) closePicker();
    });
    // ---

    $(document).on('click', '.edit-menu', function () {
        var id = $(this).data('id');
        $.get(baseUrl + '/' + id + '/edit', function (data) {
            $('#menu_id').val(data.id);
            $('#m_name').val(data.name);
            $('#m_icon').val(data.icon).trigger('input');
            $('#m_order').val(data.order);
            $('#m_visible').val(data.visible_menu ? '1' : '0');
            $('#modal-menu-title').text('Editar Menú');
            clearMenuErrors();
            showModal('modal-menu');
        });
    });

    $('#form-menu').on('submit', function (e) {
        e.preventDefault();
        var btn = $('#btn-save-menu');
        var id  = $('#menu_id').val();
        var url = id ? baseUrl + '/' + id : storeUrl;
        var formData = $(this).serialize();
        if (id) formData += '&_method=PUT';
        btn.prop('disabled', true);
        $.ajax({
            url: url, type: 'POST', data: formData,
            success: function (res) {
                btn.prop('disabled', false);
                hideModal('modal-menu');
                table.ajax.reload(null, false);
                toastr.success(res.success);
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                if (xhr.status === 422) {
                    clearMenuErrors();
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        var fieldMap = { name: 'm_name', icon: 'm_icon', order: 'm_order', visible_menu: 'm_visible' };
                        var inputId = fieldMap[field];
                        if (inputId) {
                            $('#' + inputId).addClass('is-invalid');
                            $('#err_m_' + field).text(messages[0]).show();
                        }
                    });
                    toastr.error('Revisa los campos marcados en el formulario.');
                } else {
                    toastr.error('Error al guardar.');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-menu', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar menú?',
            text: 'Solo se puede eliminar si no tiene submenús asignados.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + '/' + id, type: 'DELETE', data: { _token: csrfToken },
                    success: function (res) { toastr.success(res.success); table.ajax.reload(null, false); },
                    error: function (xhr) { toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al eliminar.'); }
                });
            }
        });
    });
</script>
@endpush
