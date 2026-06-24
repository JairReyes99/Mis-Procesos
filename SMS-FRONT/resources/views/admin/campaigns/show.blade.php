@extends('layout.default')

@section('title', 'Campaña: ' . $campaign->name)

@push('styles')
<style>
    .progress-bar-wrap {
        height: 6px;
        background: var(--bg-sunk);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
        width: 100%;
    }
    .progress-bar-fill {
        height: 6px;
        border-radius: 4px;
        transition: width .4s;
    }
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }
    .detail-row {
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--line);
    }
    .detail-row:nth-child(odd) {
        border-right: 1px solid var(--line);
    }
    .detail-row:nth-last-child(-n+2) {
        border-bottom: none;
    }
    .detail-label {
        font-size: 11.5px;
        color: var(--ink-3);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .detail-val {
        font-size: 14px;
        color: var(--ink);
        font-weight: 500;
    }
    .detail-val.muted { color: var(--ink-3); font-weight: 400; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="crumbs" style="margin-bottom:20px;">
    <a href="{{ route('home') }}" style="text-decoration:none;color:var(--ink-3);">Inicio</a>
    <span class="sep"><i class="ti ti-chevron-right" style="font-size:11px;"></i></span>
    <span style="color:var(--ink-3);">SMS</span>
    <span class="sep"><i class="ti ti-chevron-right" style="font-size:11px;"></i></span>
    <a href="{{ route('sms.campaigns.index') }}" style="text-decoration:none;color:var(--ink-3);">Campañas</a>
    <span class="sep"><i class="ti ti-chevron-right" style="font-size:11px;"></i></span>
    <b>{{ $campaign->name }}</b>
</div>

<div class="page-head">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <h1 style="margin:0;display:flex;align-items:center;gap:10px;">
                <i class="ti ti-send" style="color:var(--accent);font-size:26px;"></i>
                {{ $campaign->name }}
            </h1>
            <span class="pill {{ $campaign->statusColor }}">
                <span class="dot"></span>
                {{ $campaign->statusLabel }}
            </span>
        </div>
        <p style="color:var(--ink-3);font-size:13px;font-family:var(--font-mono);">
            UUID: {{ $campaign->uuid }}
        </p>
    </div>
    <div class="page-head-actions">
        <a href="{{ route('sms.campaigns.index') }}" class="btn">
            <i class="ti ti-arrow-left"></i>
            Volver
        </a>
        @if($campaign->campaign_status <= 2)
            <button
                type="button"
                class="btn btn-danger"
                id="btn-cancel-campaign"
                data-uuid="{{ $campaign->uuid }}"
                data-name="{{ $campaign->name }}"
            >
                <i class="ti ti-ban"></i>
                Cancelar campaña
            </button>
        @endif
    </div>
</div>

{{-- ─── STATS ─── --}}
@php
    $pct = $campaign->total_recipients > 0
        ? round(($campaign->sent_count / $campaign->total_recipients) * 100, 1)
        : 0;
@endphp

<div class="stats" style="margin-bottom:24px;">
    <div class="stat">
        <div class="stat-label">
            <i class="ti ti-users" style="font-size:14px;"></i>
            Total envíos
        </div>
        <div class="stat-value">{{ number_format($campaign->total_recipients, 0, '.', ',') }}</div>
        <div class="stat-foot">Destinatarios registrados</div>
    </div>

    <div class="stat" id="stat-sent" style="{{ $campaign->sent_count > 0 ? 'border-color:color-mix(in oklch, var(--ok) 30%, transparent);' : '' }}">
        <div class="stat-label">
            <i class="ti ti-circle-check" style="font-size:14px;{{ $campaign->sent_count > 0 ? 'color:var(--ok)' : '' }}"></i>
            Enviados
        </div>
        <div class="stat-value" id="val-sent" style="{{ $campaign->sent_count > 0 ? 'color:var(--ok)' : '' }}">
            {{ number_format($campaign->sent_count, 0, '.', ',') }}
        </div>
        <div class="stat-foot">Mensajes entregados</div>
    </div>

    <div class="stat" id="stat-failed" style="{{ $campaign->failed_count > 0 ? 'border-color:color-mix(in oklch, var(--err) 30%, transparent);' : '' }}">
        <div class="stat-label">
            <i class="ti ti-alert-triangle" style="font-size:14px;{{ $campaign->failed_count > 0 ? 'color:var(--err)' : '' }}"></i>
            Fallidos
        </div>
        <div class="stat-value" id="val-failed" style="{{ $campaign->failed_count > 0 ? 'color:var(--err)' : '' }}">
            {{ number_format($campaign->failed_count, 0, '.', ',') }}
        </div>
        <div class="stat-foot">Mensajes con error</div>
    </div>

    <div class="stat">
        <div class="stat-label">
            <i class="ti ti-chart-pie" style="font-size:14px;"></i>
            Completado
        </div>
        <div class="stat-value" id="val-pct">{{ $pct }}%</div>
        <div class="progress-bar-wrap">
            <div id="progress-fill"
                class="progress-bar-fill"
                style="width:{{ $pct }}%;background:{{ $pct >= 100 ? 'var(--ok)' : ($pct > 0 ? 'var(--accent)' : 'var(--line-strong)') }};"
            ></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start;">

    {{-- ─── COLUMNA IZQUIERDA: Detalles ─── --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Card: Detalles --}}
        <div class="card">
            <div class="card-h">
                <i class="ti ti-info-circle" style="color:var(--ink-3);font-size:18px;"></i>
                <h3>Detalles de la campaña</h3>
            </div>
            <div class="detail-grid">
                <div class="detail-row">
                    <span class="detail-label"><i class="ti ti-send" style="font-size:11px;"></i> Tipo de envío</span>
                    <span class="detail-val">{{ $campaign->sendType->name ?? '—' }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="ti ti-hash" style="font-size:11px;"></i> Estado</span>
                    <span class="detail-val">
                        <span class="pill {{ $campaign->statusColor }}">
                            <span class="dot"></span>
                            {{ $campaign->statusLabel }}
                        </span>
                    </span>
                </div>

                @if($campaign->scheduled_at)
                <div class="detail-row" style="grid-column:1/-1;">
                    <span class="detail-label"><i class="ti ti-calendar-time" style="font-size:11px;"></i> Fecha programada</span>
                    <span class="detail-val">{{ $campaign->scheduled_at->format('d/m/Y H:i') }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-label"><i class="ti ti-user" style="font-size:11px;"></i> Creada por</span>
                    <span class="detail-val">{{ $campaign->creator->name ?? '—' }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="ti ti-calendar" style="font-size:11px;"></i> Fecha creación</span>
                    <span class="detail-val">{{ $campaign->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="ti ti-id" style="font-size:11px;"></i> UUID</span>
                    <span class="detail-val mono" style="font-size:12px;color:var(--ink-3);">{{ $campaign->uuid }}</span>
                </div>

                @if($campaign->company)
                <div class="detail-row">
                    <span class="detail-label"><i class="ti ti-building" style="font-size:11px;"></i> Empresa</span>
                    <span class="detail-val">{{ $campaign->company->name }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Card: Restricciones horarias --}}
        @if(!empty($campaign->no_send_rules) && count($campaign->no_send_rules) > 0)
        <div class="card">
            <div class="card-h">
                <i class="ti ti-ban" style="color:var(--warn);font-size:18px;"></i>
                <div>
                    <h3>Restricciones horarias</h3>
                    <p>Rangos en que los mensajes no se envían</p>
                </div>
            </div>
            <div class="card-b" style="display:flex;flex-direction:column;gap:8px;">
                @foreach($campaign->no_send_rules as $rule)
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--warn-soft);border:1px solid color-mix(in oklch,var(--warn) 25%,transparent);border-radius:var(--radius-sm);">
                    <i class="ti ti-clock-off" style="color:var(--warn);font-size:16px;flex-shrink:0;"></i>
                    <span style="font-family:var(--font-mono);font-size:13px;font-weight:500;color:var(--warn);">
                        {{ $rule['from'] ?? '—' }} — {{ $rule['to'] ?? '—' }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- ─── COLUMNA DERECHA: Destinatarios ─── --}}
    <div class="card">
        <div class="card-h">
            <i class="ti ti-users" style="color:var(--ink-3);font-size:18px;"></i>
            <div>
                <h3>Destinatarios</h3>
                <p>{{ number_format($recipients->total(), 0, '.', ',') }} registros en total</p>
            </div>
        </div>

        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Teléfono</th>
                        <th>Mensaje</th>
                        <th style="width:50px;">SMS</th>
                        <th style="width:80px;">Encoding</th>
                        <th style="width:100px;">Estado</th>
                        <th style="width:130px;">Enviado el</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipients as $r)
                    <tr>
                        <td>
                            <span class="mono" style="font-size:12px;color:var(--ink-4);">
                                {{ $recipients->firstItem() + $loop->index }}
                            </span>
                        </td>
                        <td>
                            <span class="mono" style="font-size:12.5px;">{{ $r->phone }}</span>
                        </td>
                        <td>
                            <span
                                title="{{ $r->message }}"
                                style="font-size:13px;color:var(--ink-2);max-width:240px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                            >
                                {{ Str::limit($r->message, 50) }}
                            </span>
                        </td>
                        <td>
                            <span class="mono" style="font-size:12px;">{{ $r->segments ?? '—' }}</span>
                        </td>
                        <td>
                            @if($r->encoding)
                                <span class="pill {{ $r->encoding === 'GSM-7' ? 'pill-ok' : 'pill-info' }}" style="font-size:10.5px;">
                                    {{ $r->encoding }}
                                </span>
                            @else
                                <span style="color:var(--ink-4);">—</span>
                            @endif
                        </td>
                        <td>
                            @php $ss = $r->sendStatusCatalog; @endphp
                            <span class="pill {{ $ss?->color ?? 'pill-muted' }}" style="font-size:10.5px;">
                                <span class="dot"></span>
                                {{ $ss?->name ?? 'Desconocido' }}
                            </span>
                        </td>
                        <td>
                            @if($r->sent_at)
                                <span style="font-size:12px;color:var(--ink-3);font-family:var(--font-mono);">
                                    {{ $r->sent_at->format('d/m/Y H:i') }}
                                </span>
                            @else
                                <span style="color:var(--ink-4);">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:32px;color:var(--ink-4);">
                            <i class="ti ti-inbox" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                            Sin destinatarios registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación Laravel --}}
        @if($recipients->hasPages())
        <div class="pag">
            <span>
                Mostrando {{ $recipients->firstItem() }}–{{ $recipients->lastItem() }}
                de {{ number_format($recipients->total(), 0, '.', ',') }} registros
            </span>
            <div class="pag-ctrl">
                @if($recipients->onFirstPage())
                    <button class="pag-btn" disabled><i class="ti ti-chevron-left" style="font-size:12px;"></i></button>
                @else
                    <a href="{{ $recipients->previousPageUrl() }}" class="pag-btn"><i class="ti ti-chevron-left" style="font-size:12px;"></i></a>
                @endif

                @foreach($recipients->getUrlRange(max(1, $recipients->currentPage() - 2), min($recipients->lastPage(), $recipients->currentPage() + 2)) as $page => $url)
                    <a
                        href="{{ $url }}"
                        class="pag-btn {{ $page == $recipients->currentPage() ? 'active' : '' }}"
                    >{{ $page }}</a>
                @endforeach

                @if($recipients->hasMorePages())
                    <a href="{{ $recipients->nextPageUrl() }}" class="pag-btn"><i class="ti ti-chevron-right" style="font-size:12px;"></i></a>
                @else
                    <button class="pag-btn" disabled><i class="ti ti-chevron-right" style="font-size:12px;"></i></button>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script>
"use strict";

// ── Tiempo real vía Reverb ──────────────────────────────────────────────
@if(in_array($campaign->campaign_status, [2, 3, 5]))
document.addEventListener('DOMContentLoaded', function () {
    function fmt(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    window.Echo.private('campaign.{{ $campaign->id }}')
        .listen('.progress', function (e) {
            document.getElementById('val-sent').textContent   = fmt(e.sent_count);
            document.getElementById('val-failed').textContent = fmt(e.failed_count);
            document.getElementById('val-pct').textContent    = e.percent + '%';
            var fill = document.getElementById('progress-fill');
            fill.style.width      = e.percent + '%';
            fill.style.background = e.percent >= 100 ? 'var(--ok)' : 'var(--accent)';

            if (e.status === 5 && e.paused_reason === 'no_balance') {
                toastr.warning(
                    'Recarga tu crédito y reactiva la campaña.',
                    'Campaña pausada: saldo insuficiente',
                    { timeOut: 0, extendedTimeOut: 0, closeButton: true }
                );
            }
        })
        .listen('.completed', function (e) {
            document.getElementById('val-sent').textContent   = fmt(e.sent_count);
            document.getElementById('val-failed').textContent = fmt(e.failed_count);
            document.getElementById('val-pct').textContent    = '100%';
            document.getElementById('progress-fill').style.width      = '100%';
            document.getElementById('progress-fill').style.background = 'var(--ok)';

            var balEl = document.querySelector('.company-stat-balance');
            if (balEl) balEl.textContent = e.balance;

            toastr.success('Campaña completada. Costo: ' + e.cost);
        });
});
@endif

@if($campaign->campaign_status <= 2)
document.getElementById('btn-cancel-campaign').addEventListener('click', function () {
    var uuid = this.dataset.uuid;
    var name = this.dataset.name;

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
            url: '{{ url("sms/campaigns") }}/' + uuid,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                toastr.success(res.message || 'Campaña cancelada correctamente.');
                setTimeout(function () {
                    window.location.href = '{{ route("sms.campaigns.index") }}';
                }, 1500);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON
                    ? (xhr.responseJSON.message || xhr.responseJSON.error)
                    : 'Error al cancelar la campaña.';
                toastr.error(msg);
            }
        });
    });
});
@endif
</script>
@endpush
