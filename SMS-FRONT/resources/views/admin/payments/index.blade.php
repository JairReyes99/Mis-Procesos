@extends('layout.default')

@section('title', 'Métodos de Pago')

@section('content')
<div x-data="paymentsApp()" x-init="init()" x-cloak>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="pay-page-header">
        <div>
            <h2 class="pay-page-title">Métodos de Pago</h2>
            <p class="pay-page-sub">Gestiona tu tarjeta y recarga créditos para enviar SMS.</p>
        </div>
        <div class="pay-balance-badge">
            <div class="pay-balance-label">Saldo disponible</div>
            <div class="pay-balance-amount company-stat-balance">
                ${{ number_format($company->balance ?? 0, 2) }} MXN
            </div>
        </div>
    </div>

    {{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
    <div class="pay-tabs">
        <button class="pay-tab" :class="activeTab === 'metodos' ? 'active' : ''"
            @click="activeTab = 'metodos'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            Métodos de pago
        </button>
        <button class="pay-tab" :class="activeTab === 'historial' ? 'active' : ''"
            @click="activeTab = 'historial'; initHistorial()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
            Historial de movimientos
        </button>
    </div>

    {{-- ── Tab: Métodos de pago ────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'metodos'">

    {{-- ── Main grid ───────────────────────────────────────────────────────── --}}
    <div class="pay-grid">

        {{-- ── LEFT: Tarjeta guardada ───────────────────────────── --}}
        <div class="pay-section">
            <div class="pay-card">
                <div class="pay-card-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    <span>Tarjeta guardada</span>
                </div>
                <div class="pay-card-body">

                    @if($cardSummary)
                    {{-- Credit card visual --}}
                    <div class="cc-visual">
                        <div class="cc-chip">
                            <svg width="28" height="22" viewBox="0 0 38 28" fill="none"><rect x="1" y="1" width="36" height="26" rx="4" fill="#D4AF37" stroke="#B8952A" stroke-width="1.5"/><line x1="1" y1="10" x2="37" y2="10" stroke="#B8952A" stroke-width="1"/><line x1="1" y1="18" x2="37" y2="18" stroke="#B8952A" stroke-width="1"/><line x1="14" y1="1" x2="14" y2="27" stroke="#B8952A" stroke-width="1"/><line x1="24" y1="1" x2="24" y2="27" stroke="#B8952A" stroke-width="1"/></svg>
                        </div>
                        <div class="cc-number">•••• •••• •••• {{ $cardSummary['last4'] }}</div>
                        <div class="cc-footer">
                            <div>
                                <div class="cc-label-small">VENCE</div>
                                <div class="cc-value">{{ $cardSummary['exp'] }}</div>
                            </div>
                            <div class="cc-brand">
                                @if(strtolower($cardSummary['brand']) === 'visa')
                                <span class="cc-brand-visa">VISA</span>
                                @elseif(strtolower($cardSummary['brand']) === 'mastercard')
                                <span class="cc-brand-mc">MC</span>
                                @else
                                <span class="cc-brand-other">{{ strtoupper($cardSummary['brand']) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pay-card-info-row">
                        <div class="pay-card-meta">
                            <div class="pay-card-name">{{ ucfirst($cardSummary['brand']) }} terminada en {{ $cardSummary['last4'] }}</div>
                            <div class="pay-card-exp">Vence {{ $cardSummary['exp'] }}</div>
                        </div>
                        <span class="pill pill-ok">Activa</span>
                    </div>

                    <button class="btn pay-btn-danger" @click="removeCard()" :disabled="loadingRemove">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        <span x-show="!loadingRemove">Eliminar tarjeta</span>
                        <span x-show="loadingRemove">Eliminando…</span>
                    </button>

                    @else
                    {{-- Add card form --}}
                    <div class="pay-empty-state">
                        <div class="pay-empty-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                        <p class="pay-empty-text">Agrega una tarjeta para habilitar recargas automáticas.</p>
                    </div>

                    <div class="pay-field">
                        <label class="pay-label">Número de tarjeta</label>
                        <div id="card-element" class="pay-stripe-element"></div>
                        <p class="pay-field-error" x-text="errorMsg" x-show="errorMsg"></p>
                    </div>

                    <button class="btn btn-primary pay-btn-full" @click="saveCard()" :disabled="loadingCard || !cardReady">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        <span x-show="!loadingCard">Guardar tarjeta</span>
                        <span x-show="loadingCard">Guardando…</span>
                    </button>
                    @endif

                    <div class="pay-secure-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Pagos seguros vía Stripe SSL
                    </div>

                </div>
            </div>

            {{-- ── Auto-recharge ─────────────────────────────────── --}}
            <div class="pay-card">
                <div class="pay-card-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                    <span>Recarga automática</span>
                </div>
                <div class="pay-card-body">

                    @if(!$cardSummary)
                    <div class="pay-locked-state">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <p>Guarda una tarjeta primero para activar la recarga automática.</p>
                    </div>
                    @else

                    <div class="pay-toggle-row">
                        <div class="pay-toggle-info">
                            <div class="pay-toggle-title">Activar recarga automática</div>
                            <div class="pay-toggle-sub">Se recargará cuando tu saldo llegue a cero</div>
                        </div>
                        <label class="pay-switch" aria-label="Activar recarga automática">
                            <input type="checkbox"
                                x-model="autoRecharge.enabled"
                                @change="autoRecharge.enabled || saveAutoRecharge()">
                            <span class="pay-switch-track" :class="autoRecharge.enabled ? 'active' : ''"></span>
                            <span class="pay-switch-thumb" :class="autoRecharge.enabled ? 'active' : ''"></span>
                        </label>
                    </div>

                    <div x-show="autoRecharge.enabled" x-transition:enter="ar-enter" x-transition:enter-start="ar-enter-start" x-transition:enter-end="ar-enter-end" style="margin-top:16px;">

                        <div class="pay-field">
                            <label class="pay-label">Monto a recargar (MXN) <span class="pay-req">*</span></label>
                            <div class="pay-quick-amounts">
                                <button type="button" class="pay-quick-btn" :class="autoRecharge.amount == 200 ? 'selected' : ''" @click="autoRecharge.amount = 200">$200</button>
                                <button type="button" class="pay-quick-btn" :class="autoRecharge.amount == 500 ? 'selected' : ''" @click="autoRecharge.amount = 500">$500</button>
                                <button type="button" class="pay-quick-btn" :class="autoRecharge.amount == 1000 ? 'selected' : ''" @click="autoRecharge.amount = 1000">$1,000</button>
                                <button type="button" class="pay-quick-btn" :class="autoRecharge.amount == 2000 ? 'selected' : ''" @click="autoRecharge.amount = 2000">$2,000</button>
                            </div>
                            <div class="pay-input-prefix">
                                <span class="pay-prefix-symbol">$</span>
                                <input type="number" class="pay-input pay-input-prefixed"
                                    x-model="autoRecharge.amount"
                                    placeholder="1000" min="100" step="50">
                            </div>
                            <p class="pay-field-hint">Mínimo $100 MXN.</p>
                        </div>

                        <button class="btn btn-accent pay-btn-full"
                            @click="saveAutoRecharge()"
                            :disabled="loadingAutoRecharge || !autoRecharge.amount || autoRecharge.amount < 100">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span x-show="!loadingAutoRecharge">Guardar configuración</span>
                            <span x-show="loadingAutoRecharge">Guardando…</span>
                        </button>
                    </div>

                    <div x-show="autoSuccessMsg" x-transition class="pay-alert pay-alert-ok" x-text="autoSuccessMsg"></div>
                    <div x-show="autoErrorMsg" x-transition class="pay-alert pay-alert-err" x-text="autoErrorMsg"></div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ── RIGHT: Recargas manuales ─────────────────────────── --}}
        <div class="pay-section">

            {{-- Stripe --}}
            @if($cardSummary)
            <div class="pay-card">
                <div class="pay-card-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>Recarga con tarjeta</span>
                    <span class="pay-card-badge">Instantánea</span>
                </div>
                <div class="pay-card-body">

                    <div class="pay-card-linked">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        {{ ucfirst($cardSummary['brand']) }} •••• {{ $cardSummary['last4'] }}
                    </div>

                    <div class="pay-field">
                        <label class="pay-label">Monto (MXN) <span class="pay-req">*</span></label>
                        <div class="pay-quick-amounts">
                            <button type="button" class="pay-quick-btn" :class="manualAmount == 100 ? 'selected' : ''" @click="manualAmount = 100">$100</button>
                            <button type="button" class="pay-quick-btn" :class="manualAmount == 500 ? 'selected' : ''" @click="manualAmount = 500">$500</button>
                            <button type="button" class="pay-quick-btn" :class="manualAmount == 1000 ? 'selected' : ''" @click="manualAmount = 1000">$1,000</button>
                            <button type="button" class="pay-quick-btn" :class="manualAmount == 2000 ? 'selected' : ''" @click="manualAmount = 2000">$2,000</button>
                        </div>
                        <div class="pay-input-prefix">
                            <span class="pay-prefix-symbol">$</span>
                            <input type="number" class="pay-input pay-input-prefixed"
                                x-model="manualAmount"
                                placeholder="500" min="50" step="50">
                        </div>
                        <p class="pay-field-hint">Mínimo $50 MXN.</p>
                    </div>

                    <button class="btn btn-primary pay-btn-full pay-btn-charge"
                        @click="manualRecharge()"
                        :disabled="loadingRecharge || !manualAmount || manualAmount < 50">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        <span x-show="!loadingRecharge">Recargar ahora</span>
                        <span x-show="loadingRecharge">Procesando…</span>
                    </button>

                    <div x-show="rechargeSuccessMsg" x-transition class="pay-alert pay-alert-ok" x-text="rechargeSuccessMsg"></div>
                    <div x-show="rechargeErrorMsg" x-transition class="pay-alert pay-alert-err" x-text="rechargeErrorMsg"></div>

                </div>
            </div>
            @else
            <div class="pay-card pay-card-muted">
                <div class="pay-card-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>Recarga con tarjeta</span>
                </div>
                <div class="pay-card-body">
                    <div class="pay-locked-state">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <p>Agrega una tarjeta para habilitar esta opción.</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- PayPal — oculto temporalmente
            <div class="pay-card" x-data="paypalApp()" x-init="init()">
                <div class="pay-card-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 11C4.467 11 3 9.167 3 7c0-2.4 1.6-4 4-4h7.4c2.133 0 3.467 1.133 3.6 2.8"/><path d="M7 11h8.6c2.133 0 3.467-1.133 3.6-2.8L19.4 5"/><path d="M7 11l-1.4 8h5.6c1.867 0 3.2-.933 3.467-2.533L15.8 11"/></svg>
                    <span>Pagar con PayPal</span>
                </div>
                <div class="pay-card-body">
                    <div class="pay-field">
                        <label class="pay-label">Monto (MXN) <span class="pay-req">*</span></label>
                        <div class="pay-quick-amounts">
                            <button type="button" class="pay-quick-btn" :class="ppAmount == 100 ? 'selected' : ''" @click="ppAmount = 100; onAmountChange()">$100</button>
                            <button type="button" class="pay-quick-btn" :class="ppAmount == 500 ? 'selected' : ''" @click="ppAmount = 500; onAmountChange()">$500</button>
                            <button type="button" class="pay-quick-btn" :class="ppAmount == 1000 ? 'selected' : ''" @click="ppAmount = 1000; onAmountChange()">$1,000</button>
                            <button type="button" class="pay-quick-btn" :class="ppAmount == 2000 ? 'selected' : ''" @click="ppAmount = 2000; onAmountChange()">$2,000</button>
                        </div>
                        <div class="pay-input-prefix">
                            <span class="pay-prefix-symbol">$</span>
                            <input type="number" class="pay-input pay-input-prefixed"
                                x-model="ppAmount" min="50" step="50"
                                @change="onAmountChange()"
                                placeholder="500">
                        </div>
                        <p class="pay-field-hint">Mínimo $50 MXN.</p>
                    </div>
                    <div id="paypal-button-container" x-show="ppAmount >= 50" style="min-height:48px;"></div>
                    <p x-show="!ppAmount || ppAmount < 50" class="pay-field-hint" style="text-align:center;padding:10px 0;">
                        Ingresa el monto para ver el botón de PayPal.
                    </p>
                    <div x-show="ppSuccessMsg" x-transition class="pay-alert pay-alert-ok" x-text="ppSuccessMsg"></div>
                    <div x-show="ppErrorMsg" x-transition class="pay-alert pay-alert-err" x-text="ppErrorMsg"></div>
                </div>
            </div>
            --}}

        </div>
    </div>

    </div>{{-- /tab metodos --}}

    {{-- ── Tab: Historial ─────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'historial'" x-cloak>

        <div class="card">
            <div class="card-h">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent)"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                <div>
                    <h3>Historial de movimientos</h3>
                    <p>Recargas y cargos de tu cuenta</p>
                </div>
            </div>

            {{-- Filtros --}}
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                <div class="field" style="margin:0;min-width:160px;">
                    <label for="filter_type" style="font-size:11.5px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:3px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Tipo
                    </label>
                    <select id="filter_type" class="select" style="height:36px;font-size:13px;">
                        <option value="">Todos</option>
                        <option value="1">Recarga</option>
                        <option value="2">Cargo</option>
                    </select>
                </div>
                <div class="field" style="margin:0;min-width:140px;">
                    <label for="filter_from" style="font-size:11.5px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:3px;"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        Desde
                    </label>
                    <input type="date" id="filter_from" class="input" style="height:36px;font-size:13px;" />
                </div>
                <div class="field" style="margin:0;min-width:140px;">
                    <label for="filter_to" style="font-size:11.5px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:3px;"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        Hasta
                    </label>
                    <input type="date" id="filter_to" class="input" style="height:36px;font-size:13px;" />
                </div>
                <button type="button" id="btn-clear-tx-filters" class="btn btn-ghost" style="height:36px;align-self:flex-end;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                    Limpiar
                </button>
            </div>

            <div class="card-b" style="padding:0;">
                <div class="tbl-wrap">
                    <table class="tbl" id="tbl-transactions" style="width:100%">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Concepto</th>
                                <th style="text-align:right;">Monto</th>
                                <th style="text-align:right;">Saldo resultante</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- /tab historial --}}

</div>
@endsection

@push('styles')
<style>
[x-cloak] { display:none !important; }

/* ── Page layout ─────────────────────────────── */
.pay-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.pay-page-title {
    margin: 0 0 4px;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text);
}
.pay-page-sub {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.85rem;
}
.pay-balance-badge {
    background: var(--accent-muted);
    border: 1px solid oklch(72% 0.18 145 / 0.25);
    border-radius: 12px;
    padding: 12px 18px;
    text-align: right;
    min-width: 180px;
}
.pay-balance-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-muted);
    margin-bottom: 2px;
}
.pay-balance-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--accent);
    font-variant-numeric: tabular-nums;
}

.pay-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 860px) {
    .pay-grid { grid-template-columns: 1fr; }
}
.pay-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ── Card ────────────────────────────────────── */
.pay-card {
    background: var(--bg-elev);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    transition: box-shadow .2s;
}
.pay-card:hover {
    box-shadow: 0 4px 20px oklch(0% 0 0 / .06);
}
.pay-card-muted {
    opacity: .7;
}
.pay-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text);
    background: var(--bg);
}
.pay-card-header svg {
    flex-shrink: 0;
    color: var(--accent);
}
.pay-card-badge {
    margin-left: auto;
    background: oklch(72% 0.18 145 / 0.15);
    color: oklch(50% 0.18 145);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 99px;
    letter-spacing: .04em;
}
.pay-card-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* ── Credit card visual ──────────────────────── */
.cc-visual {
    background: linear-gradient(135deg, oklch(25% 0.05 240), oklch(18% 0.08 260));
    border-radius: 12px;
    padding: 18px 20px 16px;
    color: #fff;
    position: relative;
    overflow: hidden;
    min-height: 110px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.cc-visual::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 130px; height: 130px;
    border-radius: 50%;
    background: oklch(72% 0.18 145 / 0.12);
}
.cc-chip { margin-bottom: 10px; }
.cc-number {
    font-size: 1.05rem;
    font-weight: 600;
    letter-spacing: .15em;
    font-variant-numeric: tabular-nums;
    font-family: 'JetBrains Mono', monospace;
}
.cc-footer {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-top: 10px;
}
.cc-label-small {
    font-size: 0.62rem;
    letter-spacing: .1em;
    opacity: .7;
    text-transform: uppercase;
}
.cc-value {
    font-size: 0.85rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.cc-brand-visa {
    font-size: 1.1rem;
    font-weight: 900;
    font-style: italic;
    letter-spacing: .06em;
    color: #fff;
}
.cc-brand-mc, .cc-brand-other {
    font-size: 0.9rem;
    font-weight: 700;
    color: #fff;
}

.pay-card-info-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
.pay-card-name {
    font-weight: 600;
    font-size: 0.88rem;
    text-transform: capitalize;
}
.pay-card-exp {
    font-size: 0.78rem;
    color: var(--text-muted);
}
.pay-card-linked {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    color: var(--text-muted);
    background: var(--bg-muted);
    border-radius: 8px;
    padding: 7px 12px;
}

/* ── Secure badge ────────────────────────────── */
.pay-secure-badge {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: var(--text-muted);
    opacity: .75;
    justify-content: center;
    padding-top: 2px;
}
.pay-secure-badge svg { color: var(--ok); }

/* ── Toggle switch ───────────────────────────── */
.pay-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 4px 0;
}
.pay-toggle-title {
    font-weight: 600;
    font-size: 0.9rem;
}
.pay-toggle-sub {
    font-size: 0.78rem;
    color: var(--text-muted);
    margin-top: 2px;
}
.pay-switch {
    position: relative;
    width: 46px;
    height: 26px;
    flex-shrink: 0;
    cursor: pointer;
    display: block;
}
.pay-switch input {
    opacity: 0;
    position: absolute;
    inset: 0;
    cursor: pointer;
    z-index: 1;
    margin: 0;
}
.pay-switch-track {
    position: absolute;
    inset: 0;
    border-radius: 999px;
    background: var(--border);
    border: 2px solid oklch(45% 0 0 / 0.5);
    transition: background .2s, border-color .2s;
}
.pay-switch-track.active {
    background: var(--accent);
    border-color: oklch(55% 0.18 145 / 0.6);
}
.pay-switch-thumb {
    position: absolute;
    top: 3px; left: 3px;
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
    transition: transform .2s;
    pointer-events: none;
}
.pay-switch-thumb.active { transform: translateX(20px); }

/* Transition for auto-recharge section */
.ar-enter        { transition: all .25s ease; }
.ar-enter-start  { opacity: 0; transform: translateY(-8px); }
.ar-enter-end    { opacity: 1; transform: translateY(0); }

/* ── Amount presets ──────────────────────────── */
.pay-quick-amounts {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-bottom: 8px;
}
.pay-quick-btn {
    padding: 6px 4px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-muted);
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--text-muted);
    cursor: pointer;
    transition: all .15s;
    text-align: center;
}
.pay-quick-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-muted);
}
.pay-quick-btn.selected {
    border-color: var(--accent);
    background: var(--accent-muted);
    color: oklch(50% 0.18 145);
    font-weight: 700;
}

/* ── Input / Field ───────────────────────────── */
.pay-field { display: flex; flex-direction: column; gap: 4px; }
.pay-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
}
.pay-req { color: var(--err); }
.pay-input-prefix {
    display: flex;
    align-items: center;
}
.pay-prefix-symbol {
    height: 38px;
    display: flex;
    align-items: center;
    padding: 0 12px;
    background: var(--bg-muted);
    border: 1px solid var(--border);
    border-right: none;
    border-radius: 8px 0 0 8px;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 500;
}
.pay-input {
    height: 38px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-elev);
    color: var(--text);
    padding: 0 12px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color .15s;
    width: 100%;
}
.pay-input:focus { border-color: var(--accent); }
.pay-input-prefixed { border-radius: 0 8px 8px 0; }
.pay-field-hint {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
}
.pay-field-error {
    font-size: 0.8rem;
    color: var(--err);
    margin: 0;
}

/* ── Stripe card element ─────────────────────── */
.pay-stripe-element {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px;
    background: var(--bg-elev);
    min-height: 40px;
    transition: border-color .15s;
}

/* ── Buttons ─────────────────────────────────── */
.pay-btn-full { width: 100%; justify-content: center; gap: 7px; }
.pay-btn-danger {
    border: 1px solid var(--err);
    color: var(--err);
    background: transparent;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    transition: background .15s;
}
.pay-btn-danger:hover { background: var(--err-muted); }
.pay-btn-charge {
    font-size: 0.92rem;
    padding: 10px 16px;
}

/* ── States ──────────────────────────────────── */
.pay-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 8px 0 4px;
}
.pay-empty-icon {
    width: 52px; height: 52px;
    border-radius: 12px;
    background: var(--bg-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}
.pay-empty-text {
    margin: 0;
    font-size: 0.83rem;
    color: var(--text-muted);
    text-align: center;
}
.pay-locked-state {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: var(--bg-muted);
    border-radius: 10px;
    color: var(--text-muted);
    font-size: 0.83rem;
}
.pay-locked-state svg { flex-shrink: 0; color: var(--text-muted); }
.pay-locked-state p { margin: 0; }

/* ── Tabs ────────────────────────────────────── */
.pay-tabs {
    display: flex;
    gap: 2px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 22px;
}
.pay-tab {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    font-size: 0.87rem;
    font-weight: 500;
    color: var(--text-muted);
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    border-radius: 6px 6px 0 0;
    transition: color .15s, border-color .15s, background .15s;
}
.pay-tab:hover { color: var(--text); background: var(--bg-muted); }
.pay-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    font-weight: 600;
}
.pay-tab svg { flex-shrink: 0; }

/* ── History ─────────────────────────────────── */
.pay-history {
    margin-top: 28px;
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
}
.pay-history-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: var(--bg);
    border-bottom: 1px solid var(--border);
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text);
}
.pay-history-header svg { color: var(--accent); }
.pay-history-count {
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--text-muted);
    background: var(--bg-muted);
    padding: 3px 10px;
    border-radius: 99px;
}
.pill-err {
    background: var(--err-muted);
    color: var(--err);
}

/* ── Alerts ──────────────────────────────────── */
.pay-alert {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.83rem;
    display: flex;
    align-items: flex-start;
    gap: 6px;
}
.pay-alert-ok { background: var(--ok-muted); color: var(--ok); }
.pay-alert-err { background: var(--err-muted); color: var(--err); }
</style>
@endpush

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
{{-- PayPal SDK — oculto temporalmente
<script src="https://www.paypal.com/sdk/js?client-id={{ config('paypal.client_id') }}&currency=MXN&intent=capture"></script>
--}}
<script>
"use strict";

var txTable = null;

function initHistorial() {
    if (txTable) return;

    txTable = $('#tbl-transactions').DataTable({
        responsive: true,
        searchDelay: 400,
        processing: true,
        serverSide: true,
        language: window.DT_ES,
        order: [[0, 'desc']],
        ajax: {
            url: '{{ route('payments.transactions') }}',
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: function (d) {
                d.filter_type = $('#filter_type').val();
                d.filter_from = $('#filter_from').val();
                d.filter_to   = $('#filter_to').val();
            }
        },
        columns: [
            {
                data: 'created_at',
                name: 'created_at',
                width: '130px',
                render: function (d) {
                    if (!d) return '—';
                    var dt = new Date(d);
                    return '<span style="color:var(--ink-2);font-size:12.5px;">' +
                        dt.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' }) + ' ' +
                        dt.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' }) +
                        '</span>';
                }
            },
            {
                data: 'type',
                name: 'type',
                width: '100px',
                orderable: false,
                render: function (d) {
                    return d === 1
                        ? '<span class="pill pill-ok">Recarga</span>'
                        : '<span class="pill pill-err">Cargo</span>';
                }
            },
            {
                data: 'concept',
                name: 'concept',
                render: function (d) {
                    return '<span style="font-size:0.87rem;">' + (d || '—') + '</span>';
                }
            },
            {
                data: 'amount_fmt',
                name: 'amount',
                searchable: false,
                className: 'text-end',
                render: function (d, type, row) {
                    var sign  = row.type === 1 ? '+' : '-';
                    var color = row.type === 1 ? 'var(--ok)' : 'var(--err)';
                    return '<span style="font-weight:600;font-variant-numeric:tabular-nums;color:' + color + ';">' +
                        sign + '$' + d + ' MXN</span>';
                }
            },
            {
                data: 'balance_after_fmt',
                name: 'balance_after',
                searchable: false,
                className: 'text-end',
                render: function (d) {
                    return '<span style="font-variant-numeric:tabular-nums;font-weight:600;">$' + d + ' MXN</span>';
                }
            }
        ]
    });

    $('#filter_type, #filter_from, #filter_to').on('change', function () {
        txTable.ajax.reload(null, false);
    });

    $('#btn-clear-tx-filters').on('click', function () {
        $('#filter_type').val('');
        $('#filter_from').val('');
        $('#filter_to').val('');
        txTable.ajax.reload(null, false);
    });
}

function paymentsApp() {
    return {
        activeTab: 'metodos',
        loadingCard:        false,
        loadingRemove:      false,
        loadingAutoRecharge: false,
        loadingRecharge:    false,
        cardReady:          false,
        successMsg:         '',
        errorMsg:           '',
        autoSuccessMsg:     '',
        autoErrorMsg:       '',
        rechargeSuccessMsg: '',
        rechargeErrorMsg:   '',
        manualAmount:       null,

        autoRecharge: {
            enabled:   {{ $autoRecharge['enabled'] ? 'true' : 'false' }},
            amount:    {{ $autoRecharge['amount'] ?? 'null' }},
            threshold: {{ $autoRecharge['threshold'] ?? 'null' }},
        },

        stripe:      null,
        cardElement: null,

        init() {
            this.stripe = Stripe('{{ $publishableKey }}');
            this.listenBalance();

            @if(!$cardSummary)
            var elements = this.stripe.elements({
                fonts: [{ cssSrc: 'https://fonts.googleapis.com/css2?family=Inter+Tight' }]
            });
            this.cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize:    '15px',
                        color:       getComputedStyle(document.documentElement).getPropertyValue('--text').trim() || '#212529',
                        fontFamily:  '"Inter Tight", sans-serif',
                        '::placeholder': { color: '#9ca3af' },
                    },
                    invalid: { color: '#ef4444' },
                }
            });
            this.cardElement.mount('#card-element');
            this.cardElement.on('change', (e) => {
                this.cardReady = e.complete;
                this.errorMsg  = e.error ? e.error.message : '';
            });
            @endif
        },

        async saveCard() {
            this.loadingCard = true;
            this.errorMsg    = '';
            try {
                const { data } = await axios.post('{{ route('payments.setup_intent') }}');
                const { setupIntent, error } = await this.stripe.confirmCardSetup(
                    data.client_secret,
                    { payment_method: { card: this.cardElement } }
                );
                if (error) { this.errorMsg = error.message; return; }
                await axios.post('{{ route('payments.save_card') }}', {
                    payment_method_id: setupIntent.payment_method
                });
                toastr.success('Tarjeta guardada correctamente.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (e) {
                this.errorMsg = e.response?.data?.error || 'Error al guardar la tarjeta.';
            } finally {
                this.loadingCard = false;
            }
        },

        async removeCard() {
            if (!confirm('¿Eliminar la tarjeta guardada? Se desactivará la recarga automática.')) return;
            this.loadingRemove = true;
            try {
                await axios.delete('{{ route('payments.remove_card') }}');
                toastr.success('Tarjeta eliminada.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (e) {
                toastr.error(e.response?.data?.error || 'Error al eliminar.');
            } finally {
                this.loadingRemove = false;
            }
        },

        async saveAutoRecharge() {
            this.loadingAutoRecharge = true;
            this.autoSuccessMsg      = '';
            this.autoErrorMsg        = '';
            try {
                await axios.post('{{ route('payments.auto_recharge_config') }}', {
                    enabled:   this.autoRecharge.enabled ? 1 : 0,
                    amount:    this.autoRecharge.amount,
                    threshold: this.autoRecharge.threshold,
                });
                this.autoSuccessMsg = 'Configuración guardada correctamente.';
                setTimeout(() => this.autoSuccessMsg = '', 3500);
            } catch (e) {
                this.autoErrorMsg = e.response?.data?.error || 'Error al guardar.';
                this.autoRecharge.enabled = !this.autoRecharge.enabled;
            } finally {
                this.loadingAutoRecharge = false;
            }
        },

        listenBalance() {
            @auth
            window.Echo.private('company.{{ $company->id }}')
                .listen('.balance.updated', (e) => {
                    this.rechargeSuccessMsg = `Recarga de $${Number(e.amount).toFixed(2)} MXN acreditada. Nuevo saldo: $${Number(e.balance).toFixed(2)} MXN`;
                    const el = document.querySelector('.company-stat-balance');
                    if (el) el.textContent = `$${Number(e.balance).toLocaleString('es-MX', {minimumFractionDigits:2})} MXN`;
                    // Also update page balance badge
                    const pageBadge = document.querySelector('.pay-balance-amount');
                    if (pageBadge) pageBadge.textContent = `$${Number(e.balance).toLocaleString('es-MX', {minimumFractionDigits:2})} MXN`;
                    toastr.success(`Recarga de $${Number(e.amount).toFixed(2)} MXN acreditada`);
                });
            @endauth
        },

        async manualRecharge() {
            this.loadingRecharge    = true;
            this.rechargeErrorMsg   = '';
            this.rechargeSuccessMsg = '';
            try {
                const { data } = await axios.post('{{ route('payments.manual_recharge') }}', {
                    amount: this.manualAmount
                });
                const { paymentIntent, error } = await this.stripe.confirmCardPayment(data.client_secret);
                if (error) { this.rechargeErrorMsg = error.message; return; }
                this.rechargeSuccessMsg = `Pago de $${Number(this.manualAmount).toFixed(2)} MXN procesado. Los créditos se acreditarán en unos segundos.`;
                this.manualAmount = null;
                toastr.success('Pago procesado correctamente.');
            } catch (e) {
                this.rechargeErrorMsg = e.response?.data?.error || 'Error al procesar el pago.';
            } finally {
                this.loadingRecharge = false;
            }
        },
    };
}

function paypalApp() {
    return {
        ppAmount:     null,
        ppSuccessMsg: '',
        ppErrorMsg:   '',

        init() {},

        onAmountChange() {
            if (this.ppAmount >= 50) this.renderButtons();
        },

        renderButtons() {
            const container = document.getElementById('paypal-button-container');
            if (!container || typeof paypal === 'undefined') return;
            container.innerHTML = '';

            paypal.Buttons({
                style: { layout: 'horizontal', color: 'blue', shape: 'rect', label: 'pay', height: 40 },
                createOrder: async () => {
                    this.ppErrorMsg = '';
                    const { data } = await axios.post('{{ route('payments.paypal_create_order') }}', {
                        amount: this.ppAmount
                    });
                    return data.id;
                },
                onApprove: async (data) => {
                    const res = await axios.post('{{ route('payments.paypal_capture_order') }}', {
                        order_id: data.orderID
                    });
                    this.ppSuccessMsg = res.data.message || 'Pago completado.';
                    this.ppAmount = null;
                    toastr.success('Recarga vía PayPal procesada correctamente.');
                },
                onError:  (err)  => { this.ppErrorMsg = 'Error en el pago con PayPal. Intenta de nuevo.'; console.error(err); },
                onCancel: ()     => { this.ppErrorMsg = 'Pago cancelado.'; },
            }).render('#paypal-button-container');
        },
    };
}
</script>
@endpush
