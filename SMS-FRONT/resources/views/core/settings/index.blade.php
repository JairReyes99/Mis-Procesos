@extends('layout.default')

@section('title', 'Configuración del sistema')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;max-width:640px;">

    <div class="card">
        <div class="card-h">
            <div>
                <h3>Configuración del sistema</h3>
                <p>Parámetros globales aplicables a todas las empresas</p>
            </div>
        </div>

        {{-- SMS --}}
        <div class="card-b" style="display:flex;flex-direction:column;gap:24px;">

            <div style="display:flex;flex-direction:column;gap:4px;">
                <h4 style="font-size:13px;font-weight:600;color:var(--ink);">Tarifas SMS</h4>
                <p style="font-size:12px;color:var(--ink-4);">
                    Precio base por segmento. Las empresas pueden tener una tarifa especial que lo sobrescribe.<br>
                    Un mensaje GSM7 ≤160 caracteres = 1 segmento. Mensajes más largos o Unicode usan más segmentos.
                </p>
            </div>

            <form id="form-settings" style="display:flex;flex-direction:column;gap:14px;">
                @csrf

                <div class="field" style="max-width:280px;">
                    <label for="sms_price">Precio global por segmento (MXN) <span class="req">*</span></label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="number" name="sms_price_per_segment" id="sms_price"
                               class="input" value="{{ $smsPriceGlobal }}"
                               min="0" step="0.0001" style="max-width:160px;" />
                        <span style="font-size:12px;color:var(--ink-4);">MXN / segmento</span>
                    </div>
                    <p id="err_sms_price_per_segment" class="field-error" style="display:none;margin-top:4px;"></p>
                </div>

                <div>
                    <button type="button" id="btn-save-settings" class="btn btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Guardar cambios
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
"use strict";

var settingsUrl = "{{ route('management.settings.update') }}";
var csrfToken   = "{{ csrf_token() }}";

$('#btn-save-settings').on('click', function () {
    var btn = $(this);
    btn.prop('disabled', true).text('Guardando…');

    $('#form-settings .field-error').text('').hide();
    $('#form-settings .input').removeClass('is-invalid');

    $.ajax({
        url:  settingsUrl,
        type: 'POST',
        data: {
            _token:                csrfToken,
            sms_price_per_segment: $('#sms_price').val(),
        },
        success: function (res) {
            toastr.success(res.message);
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                $.each(xhr.responseJSON.errors || {}, function (field, msgs) {
                    $('#err_' + field).text(msgs[0]).show();
                    $('#sms_price').addClass('is-invalid');
                });
            } else {
                toastr.error('Error al guardar la configuración.');
            }
        },
        complete: function () {
            btn.prop('disabled', false).html(
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Guardar cambios'
            );
        }
    });
});
</script>
@endpush
