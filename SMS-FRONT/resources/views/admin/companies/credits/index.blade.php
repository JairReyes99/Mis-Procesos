@extends('layout.default')

@section('title', 'Créditos — ' . $company->name)

@section('content')
<div x-data="creditPage()" style="display:flex;flex-direction:column;gap:20px;">

    {{-- Header con saldo --}}
    <div class="card">
        <div class="card-h">
            <div>
                <h3>{{ $company->name }}</h3>
                <p>Historial de movimientos de crédito</p>
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;gap:12px;">
                <div class="stats" style="margin:0;">
                    <div class="stat">
                        <span class="stat-label">Saldo actual</span>
                        <span class="stat-value" x-text="balance"></span>
                    </div>
                </div>
                @if($p_editar)
                    <button type="button" class="btn btn-primary" @click="openModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                        Agregar movimiento
                    </button>
                @endif
                <a href="{{ route('management.companies.index') }}" class="btn btn-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5m7-7l-7 7 7 7"/></svg>
                    Regresar
                </a>
            </div>
        </div>
        <div class="card-b">
            <table class="tbl" id="tbl-credits" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Saldo anterior</th>
                        <th>Saldo resultante</th>
                        <th>Concepto</th>
                        <th>Registrado por</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal agregar movimiento (dentro del x-data para mantener scope) --}}
    <div x-show="modalOpen"
         x-cloak
         class="credit-modal-overlay"
         @click.self="closeModal()">
        <div class="card" style="width:100%;max-width:460px;" @click.stop>
            <div class="card-h" style="position:sticky;top:0;background:var(--bg-elev);z-index:10;">
                <h3>Registrar movimiento</h3>
                <button type="button" class="btn-icon" style="margin-left:auto;" @click="closeModal()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="card-b">
                <form id="form-credit" style="display:flex;flex-direction:column;gap:14px;">
                    @csrf

                    <div class="field">
                        <label>Empresa</label>
                        <input type="text" class="input" value="{{ $company->name }}" disabled />
                    </div>

                    <div class="field">
                        <label>Tipo <span class="req">*</span></label>
                        <div style="display:flex;gap:12px;margin-top:4px;">
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                <input type="radio" name="type" value="1" checked /> Recarga (+)
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                <input type="radio" name="type" value="2" /> Cargo (−)
                            </label>
                        </div>
                        <p id="err_type" class="field-error" style="display:none;margin-top:4px;"></p>
                    </div>

                    <div class="field">
                        <label for="cr_amount">Monto <span class="req">*</span></label>
                        <input type="number" name="amount" id="cr_amount" class="input"
                               placeholder="0.00" min="0.01" step="0.01" />
                        <p id="err_amount" class="field-error" style="display:none;margin-top:4px;"></p>
                    </div>

                    <div class="field">
                        <label for="cr_concept">Concepto <span class="req">*</span></label>
                        <input type="text" name="concept" id="cr_concept" class="input"
                               placeholder="Recarga manual, ajuste, etc." maxlength="255" />
                        <p id="err_concept" class="field-error" style="display:none;margin-top:4px;"></p>
                    </div>

                    <div class="field">
                        <label for="cr_notes">Notas</label>
                        <textarea name="notes" id="cr_notes" class="input" rows="3"
                                  placeholder="Información adicional (opcional)" maxlength="1000"
                                  style="resize:vertical;"></textarea>
                        <p id="err_notes" class="field-error" style="display:none;margin-top:4px;"></p>
                    </div>
                </form>
            </div>
            <div class="card-f" style="position:sticky;bottom:0;">
                <button type="button" class="btn" @click="closeModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" :disabled="saving" @click="save()">
                    <span x-show="!saving">Guardar</span>
                    <span x-show="saving">Guardando…</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
[x-cloak] { display: none !important; }
.credit-modal-overlay {
    display: flex;
    position: fixed;
    inset: 0;
    z-index: 50;
    background: oklch(20% 0.01 100 / 0.4);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 32px;
}
</style>
@endpush

@push('scripts')
<script>
"use strict";

var storeUrl  = "{{ route('management.companies.credits.store', $company->id) }}";
var indexUrl  = "{{ route('management.companies.credits.index', $company->id) }}";
var csrfToken = "{{ csrf_token() }}";

var table = $('#tbl-credits').DataTable({
    responsive: true,
    searchDelay: 500,
    processing: true,
    serverSide: true,
    order: [[0, 'desc']],
    language: window.DT_ES,
    ajax: { url: indexUrl, type: 'GET' },
    columns: [
        { data: 'id', name: 'id', width: '60px' },
        {
            data: 'type_label', name: 'type', orderable: false,
            render: function (d, t, row) {
                var cls  = row.type_color === 'ok' ? 'pill-ok' : 'pill-err';
                var sign = row.type === 1 ? '+' : '−';
                return '<span class="pill ' + cls + '">' + sign + ' ' + d + '</span>';
            }
        },
        {
            data: 'amount', name: 'amount',
            render: function (d) { return '$' + parseFloat(d).toFixed(2); }
        },
        {
            data: 'balance_before', name: 'balance_before',
            render: function (d) { return '$' + parseFloat(d).toFixed(2); }
        },
        {
            data: 'balance_after', name: 'balance_after',
            render: function (d) { return '<strong>$' + parseFloat(d).toFixed(2) + '</strong>'; }
        },
        { data: 'concept', name: 'concept' },
        { data: 'creator_name', name: 'creator_name', orderable: false },
        {
            data: 'created_at', name: 'created_at',
            render: function (d) { return d ? d.substring(0, 16).replace('T', ' ') : '—'; }
        },
    ],
});

function creditPage() {
    return {
        balance:   '${{ number_format($company->balance, 2) }}',
        modalOpen: false,
        saving:    false,

        openModal() {
            document.getElementById('form-credit').reset();
            this.clearErrors();
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.modalOpen = false;
            document.body.style.overflow = '';
        },

        clearErrors() {
            document.querySelectorAll('#form-credit .field-error').forEach(function (el) {
                el.textContent = '';
                el.style.display = 'none';
            });
            document.querySelectorAll('#form-credit .input').forEach(function (el) {
                el.classList.remove('is-invalid');
            });
        },

        save() {
            this.clearErrors();
            this.saving = true;

            var self   = this;
            var form   = document.getElementById('form-credit');
            var typeEl = form.querySelector('input[name="type"]:checked');

            $.ajax({
                url:  storeUrl,
                type: 'POST',
                data: {
                    _token:  csrfToken,
                    type:    typeEl ? typeEl.value : '',
                    amount:  document.getElementById('cr_amount').value,
                    concept: document.getElementById('cr_concept').value,
                    notes:   document.getElementById('cr_notes').value,
                },
                success: function (res) {
                    self.balance = res.balance_formatted;
                    self.closeModal();
                    table.ajax.reload(null, false);
                    toastr.success(res.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        var body = xhr.responseJSON;
                        if (body.message) toastr.error(body.message);
                        if (body.errors) {
                            $.each(body.errors, function (field, msgs) {
                                $('#err_' + field).text(msgs[0]).show();
                                $('#cr_' + field).addClass('is-invalid');
                            });
                        }
                    } else {
                        toastr.error('Error al registrar el movimiento.');
                    }
                },
                complete: function () {
                    self.saving = false;
                }
            });
        },
    };
}
</script>
@endpush
