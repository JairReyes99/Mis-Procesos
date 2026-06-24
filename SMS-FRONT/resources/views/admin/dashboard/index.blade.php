@extends('layout.default')

@section('title', 'Dashboard')

@push('styles')
<style>
/* ── Layout ─────────────────────────────────────────────────────────── */
.dash-filters {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.dash-period-group {
    display: flex;
    background: var(--bg-muted);
    border: 1px solid var(--line);
    border-radius: var(--radius-sm);
    overflow: hidden;
}
.dash-period-group button {
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--ink-3);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background .15s, color .15s;
    white-space: nowrap;
}
.dash-period-group button:not(:last-child) {
    border-right: 1px solid var(--line);
}
.dash-period-group button.active,
.dash-period-group button:hover {
    background: var(--accent);
    color: #000;
}
.dash-company-select {
    margin-left: auto;
}

/* ── KPI Cards ──────────────────────────────────────────────────────── */
.dash-kpis {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 1200px) { .dash-kpis { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 700px)  { .dash-kpis { grid-template-columns: repeat(2, 1fr); } }

.kpi-card {
    background: var(--bg);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    position: relative;
    overflow: hidden;
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--kpi-accent, var(--line));
    border-radius: var(--radius) var(--radius) 0 0;
}
.kpi-card.kpi-sent::before    { background: #4ade80; }
.kpi-card.kpi-failed::before  { background: #f87171; }
.kpi-card.kpi-rate::before    { background: #60a5fa; }
.kpi-card.kpi-active::before  { background: #fb923c; }
.kpi-card.kpi-spend::before   { background: #a78bfa; }
.kpi-card.kpi-balance::before { background: var(--accent); }

.kpi-label {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--ink-3);
    text-transform: uppercase;
    letter-spacing: .04em;
    display: flex;
    align-items: center;
    gap: 5px;
}
.kpi-value {
    font-size: 26px;
    font-weight: 700;
    font-family: var(--font-mono);
    color: var(--ink);
    line-height: 1.1;
    letter-spacing: -.02em;
}
.kpi-sub {
    font-size: 11px;
    color: var(--ink-3);
}

/* ── Charts grid ────────────────────────────────────────────────────── */
.dash-charts-row {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 14px;
    margin-bottom: 14px;
}
@media (max-width: 900px) { .dash-charts-row { grid-template-columns: 1fr; } }

.dash-charts-bottom {
    margin-bottom: 14px;
}

/* ── Chart card header ──────────────────────────────────────────────── */
.chart-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    display: flex;
    align-items: center;
    gap: 6px;
}
.chart-title i { color: var(--accent); font-size: 15px; }
.chart-sub {
    font-size: 11.5px;
    color: var(--ink-3);
    margin-top: 2px;
}

/* ── Table campaigns ────────────────────────────────────────────────── */
.rate-bar-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
}
.rate-bar {
    flex: 1;
    height: 5px;
    background: var(--bg-sunk);
    border-radius: 3px;
    overflow: hidden;
    min-width: 50px;
}
.rate-bar-fill {
    height: 5px;
    background: #4ade80;
    border-radius: 3px;
    transition: width .4s;
}
.rate-num {
    font-size: 11.5px;
    font-family: var(--font-mono);
    color: var(--ink);
    white-space: nowrap;
}

/* ── Delta badges ───────────────────────────────────────────────────── */
.kpi-delta {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 10.5px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 20px;
    white-space: nowrap;
    vertical-align: middle;
}
.kpi-delta i { font-size: 11px; }
.kpi-delta-good    { background: #dcfce7; color: #16a34a; }
.kpi-delta-bad     { background: #fee2e2; color: #dc2626; }
.kpi-delta-neutral { background: var(--bg-muted); color: var(--ink-3); }

/* ── Single-col override ────────────────────────────────────────────── */
.dash-single-col { grid-template-columns: 1fr !important; }

/* ── Loading overlay ────────────────────────────────────────────────── */
.dash-loading {
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    backdrop-filter: blur(2px);
}
.dash-spinner {
    width: 36px; height: 36px;
    border: 3px solid var(--line);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin .65s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Empty state ────────────────────────────────────────────────────── */
.dash-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--ink-3);
    font-size: 13px;
}
.dash-empty i { font-size: 32px; display: block; margin-bottom: 8px; color: var(--line-strong); }
</style>
@endpush

@section('content')

<div x-data="dashboardApp({{ Illuminate\Support\Js::from($companies ?? []) }})" x-init="init()" x-cloak>

    {{-- Loading overlay --}}
    <div class="dash-loading" x-show="loading" x-transition.opacity>
        <div class="dash-spinner"></div>
    </div>

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="page-head" style="margin-bottom:18px;">
        <div>
            <h1 style="display:flex;align-items:center;gap:8px;">
                <i class="ti ti-layout-dashboard" style="color:var(--accent);font-size:22px;"></i>
                Dashboard
            </h1>
            <p>Métricas de envío y desempeño de campañas SMS</p>
        </div>
        <div class="page-head-actions">
            <button class="btn btn-ghost" @click="loadData()" title="Actualizar">
                <i class="ti ti-refresh" :class="loading ? 'ti-spin' : ''"></i>
                Actualizar
            </button>
        </div>
    </div>

    {{-- ── Filtros ─────────────────────────────────────────────────────── --}}
    <div class="dash-filters">
        <div class="dash-period-group">
            <button :class="period === 'today' ? 'active' : ''" @click="setPeriod('today')">Hoy</button>
            <button :class="period === '7d'    ? 'active' : ''" @click="setPeriod('7d')">7 días</button>
            <button :class="period === '30d'   ? 'active' : ''" @click="setPeriod('30d')">30 días</button>
            <button :class="period === 'month' ? 'active' : ''" @click="setPeriod('month')">Este mes</button>
        </div>

        @unless(auth()->user()->company_id)
        <div class="field dash-company-select" style="margin:0;min-width:220px;">
            <select class="select"
                x-model="companyId"
                @change="loadData()"
            >
                <option value="">— Todas las empresas —</option>
                <template x-for="c in companies" :key="c.id">
                    <option :value="c.id" x-text="c.name"></option>
                </template>
            </select>
        </div>
        @endunless
    </div>

    {{-- ── KPIs ────────────────────────────────────────────────────────── --}}
    <div class="dash-kpis">

        <div class="kpi-card kpi-sent">
            <div class="kpi-label"><i class="ti ti-send" style="font-size:13px;"></i> Enviados</div>
            <div class="kpi-value" x-text="kpis.sent ?? '—'"></div>
            <div class="kpi-sub">
                mensajes en el período
                <span class="kpi-delta" x-show="kpis.sent_delta != null"
                      :class="kpis.sent_delta >= 0 ? 'kpi-delta-good' : 'kpi-delta-bad'">
                    <i :class="kpis.sent_delta >= 0 ? 'ti ti-arrow-up-right' : 'ti ti-arrow-down-right'"></i>
                    <span x-text="(kpis.sent_delta > 0 ? '+' : '') + kpis.sent_delta + '%'"></span>
                </span>
            </div>
        </div>

        <div class="kpi-card kpi-failed">
            <div class="kpi-label"><i class="ti ti-alert-triangle" style="font-size:13px;"></i> Fallidos</div>
            <div class="kpi-value" x-text="kpis.failed ?? '—'"></div>
            <div class="kpi-sub">
                mensajes con error
                <span class="kpi-delta" x-show="kpis.failed_delta != null"
                      :class="kpis.failed_delta <= 0 ? 'kpi-delta-good' : 'kpi-delta-bad'">
                    <i :class="kpis.failed_delta <= 0 ? 'ti ti-arrow-down-right' : 'ti ti-arrow-up-right'"></i>
                    <span x-text="(kpis.failed_delta > 0 ? '+' : '') + kpis.failed_delta + '%'"></span>
                </span>
            </div>
        </div>

        <div class="kpi-card kpi-rate">
            <div class="kpi-label"><i class="ti ti-percentage" style="font-size:13px;"></i> Tasa entrega</div>
            <div class="kpi-value" x-text="kpis.rate != null ? kpis.rate + '%' : '—'"></div>
            <div class="kpi-sub">
                sobre mensajes procesados
                <span class="kpi-delta" x-show="kpis.rate_delta != null"
                      :class="kpis.rate_delta >= 0 ? 'kpi-delta-good' : 'kpi-delta-bad'">
                    <i :class="kpis.rate_delta >= 0 ? 'ti ti-arrow-up-right' : 'ti ti-arrow-down-right'"></i>
                    <span x-text="(kpis.rate_delta > 0 ? '+' : '') + kpis.rate_delta + 'pp'"></span>
                </span>
            </div>
        </div>

        <div class="kpi-card kpi-active">
            <div class="kpi-label"><i class="ti ti-player-play" style="font-size:13px;"></i> Activas ahora</div>
            <div class="kpi-value" x-text="kpis.active_campaigns ?? '—'"></div>
            <div class="kpi-sub">campañas en proceso</div>
        </div>

        <div class="kpi-card kpi-spend">
            <div class="kpi-label"><i class="ti ti-receipt" style="font-size:13px;"></i> Gasto período</div>
            <div class="kpi-value" x-text="kpis.spend ?? '—'"></div>
            <div class="kpi-sub">
                créditos consumidos
                <span class="kpi-delta kpi-delta-neutral" x-show="kpis.spend_delta != null">
                    <i :class="kpis.spend_delta >= 0 ? 'ti ti-arrow-up-right' : 'ti ti-arrow-down-right'"></i>
                    <span x-text="(kpis.spend_delta > 0 ? '+' : '') + kpis.spend_delta + '%'"></span>
                </span>
            </div>
        </div>

        <div class="kpi-card kpi-balance">
            <div class="kpi-label"><i class="ti ti-wallet" style="font-size:13px;"></i> Saldo disponible</div>
            <div class="kpi-value" x-text="kpis.balance ?? '—'"></div>
            <div class="kpi-sub">balance actual</div>
        </div>

    </div>

    {{-- ── Gráficas row 1: Timeline + Donut ───────────────────────────── --}}
    <div class="dash-charts-row">

        <div class="card">
            <div class="card-h">
                <div>
                    <div class="chart-title">
                        <i class="ti ti-chart-area"></i>
                        Mensajes por hora / día
                    </div>
                    <div class="chart-sub">Enviados vs fallidos en el período seleccionado</div>
                </div>
            </div>
            <div class="card-b" style="padding-top:4px;">
                <div id="chart-timeline"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-h">
                <div>
                    <div class="chart-title">
                        <i class="ti ti-chart-donut"></i>
                        Distribución de estados
                    </div>
                    <div class="chart-sub">Todos los mensajes del período</div>
                </div>
            </div>
            <div class="card-b" style="padding-top:4px;">
                <div id="chart-donut"></div>
            </div>
        </div>

    </div>

    {{-- ── Gráfica row 2: Top campañas ─────────────────────────────────── --}}
    <div class="dash-charts-bottom">
        <div class="card">
            <div class="card-h">
                <div>
                    <div class="chart-title">
                        <i class="ti ti-chart-bar"></i>
                        Top campañas del período
                    </div>
                    <div class="chart-sub">Las 7 campañas con mayor volumen de envío</div>
                </div>
            </div>
            <div class="card-b" style="padding-top:4px;">
                <div id="chart-top"></div>
                <div class="dash-empty" x-show="!topHasData" style="padding:20px 0 10px;">
                    <i class="ti ti-chart-bar-off"></i>
                    Sin campañas en este período
                </div>
            </div>
        </div>
    </div>

    {{-- ── Gráficas row 3: Gasto/Recargas + Top Empresas (admin) ────────── --}}
    <div class="dash-charts-row" :class="isAdmin ? '' : 'dash-single-col'" style="margin-bottom:14px;">

        <div class="card">
            <div class="card-h">
                <div>
                    <div class="chart-title">
                        <i class="ti ti-arrows-exchange-2"></i>
                        Gasto vs Recargas
                    </div>
                    <div class="chart-sub">Créditos consumidos y recargados en el período</div>
                </div>
            </div>
            <div class="card-b" style="padding-top:4px;">
                <div id="chart-spend"></div>
            </div>
        </div>

        <div class="card" x-show="isAdmin" style="display:none;">
            <div class="card-h">
                <div>
                    <div class="chart-title">
                        <i class="ti ti-building-community"></i>
                        Top empresas por volumen
                    </div>
                    <div class="chart-sub">Mensajes enviados por empresa en el período</div>
                </div>
            </div>
            <div class="card-b" style="padding-top:4px;">
                <div id="chart-companies"></div>
                <div class="dash-empty" x-show="!companiesHasData" style="padding:20px 0 10px;">
                    <i class="ti ti-building-off"></i>
                    Sin datos de empresas en este período
                </div>
            </div>
        </div>

    </div>

    {{-- ── Tabla campañas recientes ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-h">
            <div>
                <div class="chart-title">
                    <i class="ti ti-list-details"></i>
                    Campañas recientes
                </div>
                <div class="chart-sub">Últimas 8 campañas — click en el nombre para ver detalle</div>
            </div>
        </div>
        <div class="card-b" style="padding:0;">

            <template x-if="campaigns.length > 0">
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Campaña</th>
                                <th>Estado</th>
                                <th style="text-align:right;">Total</th>
                                <th style="text-align:right;">Enviados</th>
                                <th style="text-align:right;">Fallidos</th>
                                <th style="min-width:140px;">Tasa entrega</th>
                                <th style="text-align:right;">Costo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="c in campaigns" :key="c.id">
                                <tr>
                                    <td>
                                        <a :href="c.url" class="link-primary" x-text="c.name"
                                            style="font-weight:500;color:var(--ink);text-decoration:none;"
                                            @mouseover="$el.style.color='var(--accent)'"
                                            @mouseout="$el.style.color='var(--ink)'"
                                        ></a>
                                    </td>
                                    <td>
                                        <span class="pill" :class="c.status_color" x-text="c.status_label"></span>
                                    </td>
                                    <td style="text-align:right;font-family:var(--font-mono);font-size:13px;" x-text="c.total"></td>
                                    <td style="text-align:right;font-family:var(--font-mono);font-size:13px;color:#16a34a;" x-text="c.sent"></td>
                                    <td style="text-align:right;font-family:var(--font-mono);font-size:13px;" :style="parseInt(c.failed.replace(/,/g,'')) > 0 ? 'color:#dc2626' : 'color:var(--ink-3)'" x-text="c.failed"></td>
                                    <td>
                                        <div class="rate-bar-wrap">
                                            <div class="rate-bar">
                                                <div class="rate-bar-fill" :style="'width:' + Math.min(100, c.rate) + '%'"></div>
                                            </div>
                                            <span class="rate-num" x-text="c.rate + '%'"></span>
                                        </div>
                                    </td>
                                    <td style="text-align:right;font-family:var(--font-mono);font-size:13px;" x-text="c.cost"></td>
                                    <td style="color:var(--ink-3);font-size:12.5px;" x-text="c.date"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>

            <template x-if="campaigns.length === 0 && !loading">
                <div class="dash-empty">
                    <i class="ti ti-table-off"></i>
                    No hay campañas en este período
                </div>
            </template>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardApp(companies) {
    return {
        period: 'today',
        companyId: null,
        companies: companies || [],
        loading: true,
        topHasData: false,
        companiesHasData: false,
        isAdmin: {{ auth()->user()->company_id ? 'false' : 'true' }},
        kpis: {
            sent: '—', failed: '—', rate: null,
            active_campaigns: '—', spend: '—', balance: null,
        },
        campaigns: [],

        _timeline: null,
        _donut:    null,
        _top:      null,
        _spend:    null,
        _companies: null,

        init() {
            this.$nextTick(() => {
                this._timeline = new window.ApexCharts(
                    document.getElementById('chart-timeline'),
                    this._timelineOpts()
                );
                this._donut = new window.ApexCharts(
                    document.getElementById('chart-donut'),
                    this._donutOpts()
                );
                this._top = new window.ApexCharts(
                    document.getElementById('chart-top'),
                    this._topOpts()
                );
                this._spend = new window.ApexCharts(
                    document.getElementById('chart-spend'),
                    this._spendOpts()
                );
                this._companies = new window.ApexCharts(
                    document.getElementById('chart-companies'),
                    this._companiesOpts()
                );
                this._timeline.render();
                this._donut.render();
                this._top.render();
                this._spend.render();
                this._companies.render();
                this.loadData();
            });
        },

        setPeriod(p) {
            this.period = p;
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ period: this.period });
                if (this.companyId) params.set('company_id', this.companyId);

                const res  = await fetch('{{ route('dashboard.data') }}?' + params, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();

                this.kpis      = data.kpis;
                this.campaigns = data.recent_campaigns;

                // Timeline
                this._timeline.updateOptions({ xaxis: { categories: data.timeline.labels } }, false, false);
                this._timeline.updateSeries([
                    { name: 'Enviados', data: data.timeline.sent },
                    { name: 'Fallidos', data: data.timeline.failed },
                ], false);

                // Donut
                this._donut.updateSeries(data.donut.series, false);

                // Top campaigns
                this.topHasData = data.top_campaigns.names.length > 0;
                if (this.topHasData) {
                    this._top.updateOptions({ xaxis: { categories: data.top_campaigns.names } }, false, false);
                    this._top.updateSeries([
                        { name: 'Enviados', data: data.top_campaigns.sent },
                        { name: 'Fallidos', data: data.top_campaigns.failed },
                    ], false);
                }

                // Gasto vs Recargas
                this._spend.updateOptions({ xaxis: { categories: data.spend_recharges.labels } }, false, false);
                this._spend.updateSeries([
                    { name: 'Recargas', data: data.spend_recharges.recharges },
                    { name: 'Gasto',    data: data.spend_recharges.spend },
                ], false);

                // Top Empresas (solo admin)
                if (this.isAdmin && data.top_companies) {
                    this.companiesHasData = data.top_companies.names.length > 0;
                    if (this.companiesHasData) {
                        this._companies.updateOptions({ xaxis: { categories: data.top_companies.names } }, false, false);
                        this._companies.updateSeries([
                            { name: 'Enviados', data: data.top_companies.sent },
                            { name: 'Fallidos', data: data.top_companies.failed },
                        ], false);
                    }
                }
            } finally {
                this.loading = false;
            }
        },

        // ── Chart configs ────────────────────────────────────────────────

        _baseTheme() {
            return {
                fontFamily: 'Inter Tight, Inter, sans-serif',
                foreColor:  '#6b7280',
            };
        },

        _timelineOpts() {
            return {
                chart: {
                    type: 'area', height: 260,
                    ...this._baseTheme(),
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 350, animateGradually: { enabled: false } },
                    zoom: { enabled: false },
                },
                series: [
                    { name: 'Enviados', data: [] },
                    { name: 'Fallidos', data: [] },
                ],
                colors: ['#4ade80', '#f87171'],
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 90, 100] },
                },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: [],
                    axisBorder: { show: false },
                    axisTicks:  { show: false },
                    labels: { style: { fontSize: '11px' }, rotate: 0 },
                    tickAmount: 8,
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '11px' },
                        formatter: v => Number.isInteger(v) ? v.toLocaleString('es-MX') : '',
                    },
                },
                grid: { borderColor: '#e5e7eb', strokeDashArray: 4, padding: { left: 0, right: 10 } },
                dataLabels: { enabled: false },
                legend: {
                    position: 'top', horizontalAlign: 'right',
                    fontSize: '12px', fontFamily: 'Inter Tight, sans-serif',
                    markers: { size: 6 },
                },
                tooltip: {
                    theme: 'light',
                    y: { formatter: v => v.toLocaleString('es-MX') + ' msgs' },
                },
            };
        },

        _donutOpts() {
            return {
                chart: {
                    type: 'donut', height: 260,
                    ...this._baseTheme(),
                    animations: { enabled: true, speed: 350 },
                },
                series: [0, 0, 0, 0],
                labels: ['Enviado', 'Fallido', 'Pendiente', 'Bloqueado'],
                colors: ['#4ade80', '#f87171', '#94a3b8', '#fb923c'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                name:  { show: true, fontSize: '13px', fontFamily: 'Inter Tight, sans-serif', color: '#6b7280' },
                                value: { show: true, fontSize: '22px', fontFamily: 'JetBrains Mono, monospace', fontWeight: 700, color: '#111827', formatter: v => Number(v).toLocaleString('es-MX') },
                                total: {
                                    show: true, label: 'Total',
                                    fontSize: '12px', fontFamily: 'Inter Tight, sans-serif', color: '#6b7280',
                                    formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('es-MX'),
                                },
                            },
                        },
                    },
                },
                dataLabels: { enabled: false },
                legend: {
                    position: 'bottom', fontSize: '12px',
                    fontFamily: 'Inter Tight, sans-serif', markers: { size: 6 },
                },
                tooltip: {
                    theme: 'light',
                    y: { formatter: v => v.toLocaleString('es-MX') + ' msgs' },
                },
            };
        },

        _topOpts() {
            return {
                chart: {
                    type: 'bar', height: 260,
                    ...this._baseTheme(),
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 350 },
                },
                plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '60%' } },
                series: [
                    { name: 'Enviados', data: [] },
                    { name: 'Fallidos', data: [] },
                ],
                colors: ['#4ade80', '#f87171'],
                xaxis: {
                    categories: [],
                    axisBorder: { show: false },
                    axisTicks:  { show: false },
                    labels: { style: { fontSize: '11px' }, formatter: v => v.toLocaleString('es-MX') },
                },
                yaxis: { labels: { style: { fontSize: '11.5px', colors: '#374151' }, maxWidth: 180 } },
                grid: {
                    borderColor: '#e5e7eb', strokeDashArray: 4,
                    xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } },
                },
                dataLabels: { enabled: false },
                legend: {
                    position: 'top', horizontalAlign: 'right',
                    fontSize: '12px', fontFamily: 'Inter Tight, sans-serif', markers: { size: 6 },
                },
                tooltip: { theme: 'light', y: { formatter: v => v.toLocaleString('es-MX') + ' msgs' } },
            };
        },

        _spendOpts() {
            return {
                chart: {
                    type: 'bar', height: 240,
                    ...this._baseTheme(),
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 350 },
                },
                plotOptions: { bar: { horizontal: false, borderRadius: 3, columnWidth: '55%' } },
                series: [
                    { name: 'Recargas', data: [] },
                    { name: 'Gasto',    data: [] },
                ],
                colors: ['#60a5fa', '#a78bfa'],
                xaxis: {
                    categories: [],
                    axisBorder: { show: false },
                    axisTicks:  { show: false },
                    labels: { style: { fontSize: '11px' }, rotate: 0 },
                    tickAmount: 8,
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '11px' },
                        formatter: v => '$' + Number(v).toLocaleString('es-MX'),
                    },
                },
                grid: { borderColor: '#e5e7eb', strokeDashArray: 4, padding: { left: 0, right: 10 } },
                dataLabels: { enabled: false },
                legend: {
                    position: 'top', horizontalAlign: 'right',
                    fontSize: '12px', fontFamily: 'Inter Tight, sans-serif', markers: { size: 6 },
                },
                tooltip: {
                    theme: 'light',
                    y: { formatter: v => '$' + Number(v).toLocaleString('es-MX', { minimumFractionDigits: 2 }) },
                },
            };
        },

        _companiesOpts() {
            return {
                chart: {
                    type: 'bar', height: 240,
                    ...this._baseTheme(),
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 350 },
                },
                plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '60%' } },
                series: [
                    { name: 'Enviados', data: [] },
                    { name: 'Fallidos', data: [] },
                ],
                colors: ['#4ade80', '#f87171'],
                xaxis: {
                    categories: [],
                    axisBorder: { show: false },
                    axisTicks:  { show: false },
                    labels: { style: { fontSize: '11px' }, formatter: v => v.toLocaleString('es-MX') },
                },
                yaxis: { labels: { style: { fontSize: '11.5px', colors: '#374151' }, maxWidth: 160 } },
                grid: {
                    borderColor: '#e5e7eb', strokeDashArray: 4,
                    xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } },
                },
                dataLabels: { enabled: false },
                legend: {
                    position: 'top', horizontalAlign: 'right',
                    fontSize: '12px', fontFamily: 'Inter Tight, sans-serif', markers: { size: 6 },
                },
                tooltip: { theme: 'light', y: { formatter: v => v.toLocaleString('es-MX') + ' msgs' } },
            };
        },
    };
}
</script>
@endpush
