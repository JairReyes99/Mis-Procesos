@extends('layout.default')

@section('title', 'Inicio')

@push('styles')
<style>
    .test-sms-wrap {
        max-width: 520px;
    }

    .phone-row {
        display: flex;
        gap: 8px;
    }
    .phone-row .select {
        width: 160px;
        flex-shrink: 0;
    }
    .phone-row .input {
        flex: 1;
    }

    .char-counter {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 11px;
        background: var(--bg-muted);
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        font-size: 12px;
        color: var(--ink-3);
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .char-counter .cc-item { display: flex; align-items: center; gap: 4px; }
    .char-counter .cc-val  { font-family: var(--font-mono); color: var(--ink); font-weight: 600; }
    .char-counter .cc-sep  { color: var(--line-strong); }

    .seg-bar      { height: 4px; background: var(--bg-sunk); border-radius: 3px; overflow: hidden; margin-top: 8px; }
    .seg-bar-fill { height: 4px; background: var(--accent); border-radius: 3px; transition: width .2s; }

    .send-result {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: var(--radius-sm);
        font-size: 13.5px;
        font-weight: 500;
    }
    .send-result.ok      { background: var(--ok-soft,#ecfdf5);   color: var(--ok,#16a34a);   border: 1px solid var(--ok-line,#bbf7d0); }
    .send-result.err     { background: var(--err-soft,#fef2f2);  color: var(--err,#dc2626);  border: 1px solid var(--err-line,#fecaca); }
    .send-result.pending { background: var(--bg-muted,#f8f8f8);  color: var(--ink-3,#6b7280); border: 1px solid var(--line,#e5e7eb); }

    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner {
        display: inline-block;
        width: 14px; height: 14px;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin .7s linear infinite;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <h1>Bienvenido, {{ Auth::user()->name }}</h1>
        <p>{{ $currentCompany?->name ?? 'SMS Intelix' }} — Panel de administración</p>
    </div>
    <div class="page-head-actions">
        <span class="badge badge-primary">
            {{ Auth::user()->activeRole?->name ?? (Auth::user()->roles->first()?->name ?? 'Sin rol') }}
        </span>
    </div>
</div>

@role('Empresa')

{{-- ══ ENVIAR SMS DE PRUEBA ══════════════════════════════════════════════════ --}}
<div class="test-sms-wrap">
    <div class="card" x-data="testSmsForm()" x-cloak>
        <div class="card-h">
            <div>
                <h3 style="display:flex;align-items:center;gap:8px;">
                    <i class="ti ti-device-mobile-message" style="color:var(--accent);font-size:18px;"></i>
                    Enviar SMS de prueba
                </h3>
                <p>Verifica que el envío funcione antes de lanzar una campaña</p>
            </div>
        </div>
        <div class="card-b" style="display:flex;flex-direction:column;gap:18px;">

            {{-- Resultado --}}
            <div x-show="result" x-transition class="send-result"
                :class="result?.ok === true ? 'ok' : (result?.ok === false ? 'err' : 'pending')"
            >
                <span x-show="result?.ok === null" class="spinner"></span>
                <i class="ti" x-show="result?.ok !== null"
                    :class="result?.ok ? 'ti-circle-check' : 'ti-alert-circle'"
                    style="font-size:18px;flex-shrink:0;"
                ></i>
                <span x-text="result?.message"></span>
            </div>

            {{-- Destinatario --}}
            <div class="field">
                <label>
                    <i class="ti ti-phone" style="font-size:11px;"></i>
                    Número destino <span class="req">*</span>
                </label>
                <div class="phone-row">
                    <select class="select" x-model="countryCode" @change="phoneMaxLen = $event.target.selectedOptions[0].dataset.len">
                        @foreach ($countries as $c)
                            <option
                                value="{{ $c['code'] }}"
                                data-len="{{ $c['len'] }}"
                                {{ $c['code'] === '+52' ? 'selected' : '' }}
                            >{{ $c['flag'] }} {{ $c['name'] }} ({{ $c['code'] }})</option>
                        @endforeach
                    </select>
                    <input
                        type="tel"
                        class="input"
                        x-model="phone"
                        :maxlength="phoneMaxLen"
                        placeholder="Número sin prefijo"
                        inputmode="numeric"
                        @input="phone = $event.target.value.replace(/\D/g,'')"
                    />
                </div>
                <p class="field-help">
                    El prefijo del país se añade automáticamente.
                    Ej: México → <code style="font-family:var(--font-mono);font-size:11px;">+52 55 1234 5678</code>
                </p>
            </div>

            {{-- Mensaje --}}
            <div class="field">
                <label>
                    <i class="ti ti-message" style="font-size:11px;"></i>
                    Mensaje <span class="req">*</span>
                </label>
                <textarea
                    class="input"
                    style="resize:vertical;min-height:100px;font-family:var(--font-mono);font-size:13px;line-height:1.5;"
                    x-model="message"
                    @input="calcSegments"
                    placeholder="Escribe el mensaje de prueba..."
                ></textarea>

                {{-- Contador --}}
                <div class="char-counter">
                    <div class="cc-item">
                        <i class="ti ti-binary" style="font-size:11px;"></i>
                        Encoding:
                        <span class="cc-val" x-text="seg.encoding"></span>
                    </div>
                    <span class="cc-sep">|</span>
                    <div class="cc-item">
                        Caracteres:
                        <span class="cc-val"
                            x-text="seg.length + ' / ' + seg.limit"
                            :style="seg.length > seg.limit ? 'color:var(--err)' : ''"
                        ></span>
                    </div>
                    <span class="cc-sep">|</span>
                    <div class="cc-item">
                        <i class="ti ti-stack" style="font-size:11px;"></i>
                        Segmentos:
                        <span class="cc-val" x-text="seg.segments + ' SMS'"></span>
                    </div>
                </div>
                <div class="seg-bar">
                    <div class="seg-bar-fill"
                        :style="'width:' + Math.min(100, Math.round((seg.length / seg.limit) * 100)) + '%'"
                    ></div>
                </div>
            </div>

            {{-- Botón --}}
            <div style="display:flex;justify-content:flex-end;">
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="sending || !canSend"
                    :style="!canSend ? 'opacity:.5;cursor:not-allowed;' : ''"
                    @click="send"
                >
                    <span x-show="sending" class="spinner"></span>
                    <i class="ti ti-send" x-show="!sending"></i>
                    <span x-text="sending ? 'Enviando…' : 'Enviar SMS de prueba'"></span>
                </button>
            </div>

        </div>
    </div>
</div>

@else

{{-- ══ PANEL DE SISTEMA (solo Administrador) ════════════════════════════════ --}}
<div class="card">
    <div class="card-h">
        <div>
            <h3>Estado del sistema</h3>
            <p>Información técnica del entorno</p>
        </div>
    </div>
    <div class="card-b">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">

            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--bg-muted); border:1px solid var(--line); border-radius:var(--radius-sm);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px;height:32px;background:var(--accent-soft);border-radius:var(--radius-sm);display:grid;place-items:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-ink)" stroke-width="2"><path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--ink-3);font-weight:500;text-transform:uppercase;letter-spacing:.04em;">Laravel</div>
                        <div style="font-weight:600;font-size:14px;">v{{ app()->version() }}</div>
                    </div>
                </div>
                <span class="pill pill-ok"><span class="dot"></span> Activo</span>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--bg-muted); border:1px solid var(--line); border-radius:var(--radius-sm);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px;height:32px;background:var(--info-soft);border-radius:var(--radius-sm);display:grid;place-items:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--ink-3);font-weight:500;text-transform:uppercase;letter-spacing:.04em;">Base de datos</div>
                        <div style="font-weight:600;font-size:14px;">SQL Server</div>
                    </div>
                </div>
                @php
                    try { DB::connection()->getPdo(); $dbOk = true; } catch(\Exception $e) { $dbOk = false; }
                @endphp
                @if($dbOk)
                    <span class="pill pill-ok"><span class="dot"></span> Conectada</span>
                @else
                    <span class="pill pill-err"><span class="dot"></span> Error</span>
                @endif
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--bg-muted); border:1px solid var(--line); border-radius:var(--radius-sm);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px;height:32px;background:var(--bg-sunk);border-radius:var(--radius-sm);display:grid;place-items:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ink-3)" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--ink-3);font-weight:500;text-transform:uppercase;letter-spacing:.04em;">PHP</div>
                        <div style="font-weight:600;font-size:14px;">v{{ PHP_VERSION }}</div>
                    </div>
                </div>
                <span class="pill pill-ok"><span class="dot"></span> Activo</span>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--bg-muted); border:1px solid var(--line); border-radius:var(--radius-sm);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px;height:32px;background:var(--bg-sunk);border-radius:var(--radius-sm);display:grid;place-items:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ink-3)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--ink-3);font-weight:500;text-transform:uppercase;letter-spacing:.04em;">Entorno</div>
                        <div style="font-weight:600;font-size:14px;">{{ ucfirst(app()->environment()) }}</div>
                    </div>
                </div>
                <span class="pill pill-muted">{{ config('app.timezone') }}</span>
            </div>

        </div>
    </div>
</div>

@endrole

@endsection

@push('scripts')
<script>
const GSM7_CHARS = new Set(
    '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ !"#¤%&\'()*+,-./' +
    '0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿' +
    'abcdefghijklmnopqrstuvwxyzäöñüàÆæßÉ'
);
const GSM7_EXT = new Set('^{}[]\\~|€');

function calcSeg(text) {
    if (!text) return { segments: 0, encoding: 'GSM-7', length: 0, remaining: 160, limit: 160 };
    const isGsm7  = [...text].every(c => GSM7_CHARS.has(c) || GSM7_EXT.has(c));
    const length  = isGsm7 ? [...text].reduce((n, c) => n + (GSM7_EXT.has(c) ? 2 : 1), 0) : text.length;
    const sLimit  = isGsm7 ? 160 : 70;
    const mLimit  = isGsm7 ? 153 : 67;
    const segments = length === 0 ? 0 : (length <= sLimit ? 1 : Math.ceil(length / mLimit));
    const limit    = segments <= 1 ? sLimit : mLimit;
    return { segments, encoding: isGsm7 ? 'GSM-7' : 'Unicode', length, remaining: limit - (segments <= 1 ? length : length - (segments-1)*mLimit), limit };
}

function testSmsForm() {
    return {
        countryCode: '+52',
        phoneMaxLen: 10,
        phone: '',
        message: '',
        seg: { segments: 0, encoding: 'GSM-7', length: 0, remaining: 160, limit: 160 },
        sending: false,
        result: null,
        _echoChannel: null,
        _pendingId: null,

        get canSend() {
            return this.phone.length >= 7 && this.message.trim().length > 0 && !this.sending;
        },

        calcSegments() {
            this.seg = calcSeg(this.message);
        },

        listenResult(testSmsId) {
            if (!window.Echo) return;

            // Suscribirse una sola vez; reutilizar el canal en envíos posteriores
            if (!this._echoChannel) {
                this._echoChannel = window.Echo.private('test-sms.{{ auth()->id() }}')
                    .listen('.result', (e) => {
                        if (e.id !== this._pendingId) return;
                        if (e.status === 1) {
                            this.result  = { ok: true,  message: 'SMS enviado correctamente.' };
                        } else {
                            this.result  = { ok: false, message: e.error || 'El SMS no pudo ser entregado.' };
                        }
                        this.sending    = false;
                        this._pendingId = null;
                    });
            }

            this._pendingId = testSmsId;
        },

        async send() {
            this.sending = true;
            this.result  = null;
            try {
                const res = await fetch('{{ route('sms.test.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        country_code: this.countryCode,
                        phone: this.phone,
                        message: this.message,
                    }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    // Mostrar "en cola" mientras llega el resultado vía Reverb
                    this.result = { ok: null, message: 'SMS en cola de envío, esperando confirmación…' };
                    this.phone   = '';
                    this.message = '';
                    this.seg     = calcSeg('');
                    this.listenResult(data.id);
                } else {
                    const first = data.errors ? Object.values(data.errors).flat()[0] : (data.message ?? 'Error al enviar.');
                    this.result  = { ok: false, message: first };
                    this.sending = false;
                }
            } catch (e) {
                this.result  = { ok: false, message: 'Error de conexión. Intenta de nuevo.' };
                this.sending = false;
            }
        },
    };
}
</script>
@endpush
