@extends('layout.default')

@section('title', 'Campañas SMS')

@section('content')

{{-- Breadcrumb --}}
<div class="crumbs" style="margin-bottom:20px;">
    <a href="{{ route('home') }}" style="text-decoration:none;color:var(--ink-3);">Inicio</a>
    <span class="sep"><i class="ti ti-chevron-right" style="font-size:11px;"></i></span>
    <span style="color:var(--ink-3);">SMS</span>
    <span class="sep"><i class="ti ti-chevron-right" style="font-size:11px;"></i></span>
    <b>Campañas</b>
</div>

<div class="page-head">
    <div>
        <h1 style="display:flex;align-items:center;gap:10px;">
            <i class="ti ti-send" style="color:var(--accent);font-size:26px;"></i>
            Campañas SMS
        </h1>
        <p>Gestión y seguimiento de campañas de mensajería masiva</p>
    </div>
    <div class="page-head-actions">
        <a href="{{ route('sms.campaigns.create') }}" class="btn btn-primary">
            <i class="ti ti-plus"></i>
            Nueva Campaña
        </a>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <i class="ti ti-list" style="color:var(--ink-3);font-size:18px;"></i>
        <div>
            <h3>Todas las campañas</h3>
            <p>Listado completo de campañas SMS</p>
        </div>
    </div>

    {{-- Barra de filtros --}}
    <div style="padding:14px 18px;border-bottom:1px solid var(--line);display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div class="field" style="margin:0;min-width:160px;">
            <label for="filter_status" style="font-size:11.5px;">
                <i class="ti ti-filter" style="font-size:11px;"></i> Estado
            </label>
            <select id="filter_status" class="select" style="height:36px;font-size:13px;">
                <option value="">Todos los estados</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin:0;min-width:140px;">
            <label for="filter_from" style="font-size:11.5px;">
                <i class="ti ti-calendar" style="font-size:11px;"></i> Desde
            </label>
            <input type="date" id="filter_from" class="input" style="height:36px;font-size:13px;" />
        </div>
        <div class="field" style="margin:0;min-width:140px;">
            <label for="filter_to" style="font-size:11.5px;">
                <i class="ti ti-calendar" style="font-size:11px;"></i> Hasta
            </label>
            <input type="date" id="filter_to" class="input" style="height:36px;font-size:13px;" />
        </div>
        <button type="button" id="btn-clear-filters" class="btn btn-ghost" style="height:36px;align-self:flex-end;">
            <i class="ti ti-x"></i> Limpiar
        </button>
    </div>

    <div class="card-b" style="padding:0;">
        <div class="tbl-wrap">
            <table class="tbl" id="tbl-campaigns" style="width:100%">
                <thead>
                    <tr>
                        <th>UUID</th>
                        <th>Nombre</th>
                        <th>Total Envíos</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                        <th>Creada</th>
                        <th style="width:110px;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
"use strict";

var CAMPAIGNS_URL = "{{ route('sms.campaigns.index') }}";
var CSRF          = "{{ csrf_token() }}";

var table = $('#tbl-campaigns').DataTable({
    responsive: true,
    searchDelay: 400,
    processing: true,
    serverSide: true,
    language: window.DT_ES,
    order: [[5, 'desc']],
    ajax: {
        url: CAMPAIGNS_URL,
        type: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data: function (d) {
            d.filter_status = $('#filter_status').val();
            d.filter_from   = $('#filter_from').val();
            d.filter_to     = $('#filter_to').val();
        }
    },
    columns: [
        {
            data: 'uuid',
            name: 'uuid',
            searchable: false,
            width: '120px',
            render: function (d) {
                var short = d ? d.substring(0, 8) : '—';
                return '<span class="mono" style="font-size:12px;color:var(--ink-2);" title="' + d + '">' + short + '&hellip;</span>';
            }
        },
        {
            data: 'name',
            name: 'name',
            render: function (d) {
                return '<span style="font-weight:500;">' + (d || '—') + '</span>';
            }
        },
        {
            data: 'total_recipients',
            name: 'total_recipients',
            render: function (d) {
                return '<span class="mono">' + Number(d || 0).toLocaleString('es-MX') + '</span>';
            }
        },
        {
            data: 'sent_count',
            name: 'sent_count',
            orderable: false,
            render: function (d, type, row) {
                var sent  = Number(row.sent_count  || 0);
                var total = Number(row.total_recipients || 0);
                var pct   = total > 0 ? Math.round((sent / total) * 100) : 0;
                return '<span class="mono" style="font-size:12.5px;">' +
                    sent.toLocaleString('es-MX') + ' / ' + total.toLocaleString('es-MX') +
                    '</span>' +
                    '<div style="height:3px;background:var(--bg-sunk);border-radius:2px;margin-top:5px;width:80px;">' +
                    '<div style="height:3px;background:var(--ok);border-radius:2px;width:' + pct + '%"></div>' +
                    '</div>';
            }
        },
        {
            data: 'campaign_status',
            name: 'campaign_status',
            orderable: false,
            render: function (d, type, row) {
                var color = row.status_color || 'pill--draft';
                var label = row.status_label || ('Estado ' + d);
                return '<span class="pill ' + color + '"><span class="dot"></span>' + label + '</span>';
            }
        },
        {
            data: 'created_at',
            name: 'created_at',
            render: function (d) {
                if (!d) return '—';
                var dt = new Date(d);
                return dt.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' }) +
                    ' ' + dt.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });
            }
        },
        {
            data: 'uuid',
            name: 'actions',
            orderable: false,
            searchable: false,
            render: function (uuid, type, row) {
                var showUrl = "{{ url('sms/campaigns') }}/" + uuid;
                var html = '<div style="display:flex;align-items:center;gap:4px;">';
                html += '<a href="' + showUrl + '" class="btn btn-sm btn-ghost" title="Ver campaña">' +
                    '<i class="ti ti-eye"></i></a>';
                if (row.campaign_status === 2) {
                    html += '<button type="button" class="btn btn-sm btn-cancel-campaign" style="color:var(--err);border-color:var(--err);" ' +
                        'data-uuid="' + uuid + '" data-name="' + row.name + '" ' +
                        'title="Cancelar campaña">' +
                        '<i class="ti ti-ban"></i></button>';
                }
                if (row.campaign_status === 2 || row.campaign_status === 3) {
                    html += '<button type="button" class="btn btn-sm btn-pause-campaign" ' +
                        'data-uuid="' + uuid + '" data-name="' + row.name + '" title="Pausar campaña">' +
                        '<i class="ti ti-player-pause"></i></button>';
                }
                if (row.campaign_status === 5) {
                    html += '<button type="button" class="btn btn-sm btn-resume-campaign" style="color:var(--ok);border-color:var(--ok);" ' +
                        'data-uuid="' + uuid + '" data-name="' + row.name + '" title="Reanudar campaña">' +
                        '<i class="ti ti-player-play"></i></button>';
                }
                html += '</div>';
                return html;
            }
        }
    ]
});

/* Filtros */
$('#filter_status, #filter_from, #filter_to').on('change', function () {
    table.ajax.reload(null, false);
});
$('#btn-clear-filters').on('click', function () {
    $('#filter_status').val('');
    $('#filter_from').val('');
    $('#filter_to').val('');
    table.ajax.reload(null, false);
});

/* Pausar campaña */
$(document).on('click', '.btn-pause-campaign', function () {
    var uuid = $(this).data('uuid');
    var name = $(this).data('name');

    Swal.fire({
        title: '¿Pausar campaña?',
        html: 'Se pausará el envío de <strong>' + name + '</strong>.<br>Podrás reanudarla más adelante.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, pausar',
        cancelButtonText: 'No, continuar',
        confirmButtonColor: 'var(--accent)',
    }).then(function (result) {
        if (!result.isConfirmed) return;

        $.ajax({
            url: "{{ url('sms/campaigns') }}/" + uuid + '/pause',
            type: 'POST',
            data: { _token: CSRF, _method: 'PATCH' },
            success: function (res) {
                toastr.success(res.message || 'Campaña pausada correctamente.');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : 'Error al pausar la campaña.';
                toastr.error(msg);
            }
        });
    });
});

/* Reanudar campaña */
$(document).on('click', '.btn-resume-campaign', function () {
    var uuid = $(this).data('uuid');
    var name = $(this).data('name');

    Swal.fire({
        title: '¿Reanudar campaña?',
        html: 'Se reanudará el envío de <strong>' + name + '</strong>.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reanudar',
        cancelButtonText: 'No, mantener pausada',
        confirmButtonColor: 'var(--ok)',
    }).then(function (result) {
        if (!result.isConfirmed) return;

        $.ajax({
            url: "{{ url('sms/campaigns') }}/" + uuid + '/resume',
            type: 'POST',
            data: { _token: CSRF, _method: 'PATCH' },
            success: function (res) {
                toastr.success(res.message || 'Campaña reanudada correctamente.');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : 'Error al reanudar la campaña.';
                toastr.error(msg);
            }
        });
    });
});

/* Cancelar campaña */
$(document).on('click', '.btn-cancel-campaign', function () {
    var uuid = $(this).data('uuid');
    var name = $(this).data('name');

    Swal.fire({
        title: '¿Cancelar campaña?',
        html: 'Se cancelará <strong>' + name + '</strong>.<br>Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, conservar',
        confirmButtonColor: 'var(--err)',
    }).then(function (result) {
        if (!result.isConfirmed) return;

        $.ajax({
            url: "{{ url('sms/campaigns') }}/" + uuid,
            type: 'DELETE',
            data: { _token: CSRF },
            success: function (res) {
                toastr.success(res.message || 'Campaña cancelada correctamente.');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : 'Error al cancelar la campaña.';
                toastr.error(msg);
            }
        });
    });
});
</script>
@endpush
