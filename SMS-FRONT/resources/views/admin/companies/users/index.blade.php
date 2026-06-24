@extends('layout.default')

@section('title', 'Usuarios — ' . $company->name)

@section('content')
<div x-data="usersPage()" style="display:flex;flex-direction:column;gap:20px;">

    {{-- Header --}}
    <div class="card">
        <div class="card-h">
            <div>
                <h3>{{ $company->name }}</h3>
                <p>Usuarios asignados a esta empresa</p>
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;gap:12px;">
                @if($p_editar)
                    <button type="button" class="btn btn-primary" @click="openModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                        Agregar usuario
                    </button>
                @endif
                <a href="{{ route('management.companies.index') }}" class="btn btn-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5m7-7l-7 7 7 7"/></svg>
                    Regresar
                </a>
            </div>
        </div>
        <div class="card-b">
            <table class="tbl" id="tbl-users" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Correo electrónico</th>
                        <th>Rol</th>
                        <th>Estatus</th>
                        @if($p_editar)
                            <th>Acciones</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal agregar usuario --}}
    <div x-show="modalOpen"
         x-cloak
         class="user-modal-overlay"
         @click.self="closeModal()">
        <div class="card" style="width:100%;max-width:480px;" @click.stop>
            <div class="card-h" style="position:sticky;top:0;background:var(--bg-elev);z-index:10;">
                <h3>Agregar usuario</h3>
                <button type="button" class="btn-icon" style="margin-left:auto;" @click="closeModal()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="card-b">
                <form id="form-user" style="display:flex;flex-direction:column;gap:14px;">
                    @csrf

                    <div class="field">
                        <label>Empresa</label>
                        <input type="text" class="input" value="{{ $company->name }}" disabled />
                    </div>

                    <div class="field">
                        <label for="u_name">Nombre <span class="req">*</span></label>
                        <input type="text" name="name" id="u_name" class="input"
                               placeholder="Nombre completo" maxlength="255" />
                        <p id="err_name" class="field-error" style="display:none;margin-top:4px;"></p>
                    </div>

                    <div class="field">
                        <label for="u_email">Correo electrónico <span class="req">*</span></label>
                        <input type="email" name="email" id="u_email" class="input"
                               placeholder="usuario@empresa.com" maxlength="255" />
                        <p id="err_email" class="field-error" style="display:none;margin-top:4px;"></p>
                    </div>

                    <div class="field">
                        <label>Rol</label>
                        <input type="text" class="input" value="Empresa" disabled />
                    </div>

                    <div class="field">
                        <label for="u_password">Contraseña <span class="req">*</span></label>
                        <input type="password" name="password" id="u_password" class="input"
                               placeholder="Mínimo 8 caracteres" />
                        <p id="err_password" class="field-error" style="display:none;margin-top:4px;"></p>
                    </div>

                    <div class="field">
                        <label for="u_password_confirmation">Confirmar contraseña <span class="req">*</span></label>
                        <input type="password" name="password_confirmation" id="u_password_confirmation" class="input"
                               placeholder="Repite la contraseña" />
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
.user-modal-overlay {
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

var storeUrl  = "{{ route('management.companies.users.store', $company->id) }}";
var indexUrl  = "{{ route('management.companies.users.index', $company->id) }}";
var toggleBase = "{{ url('management/companies/' . $company->id . '/users') }}";
var csrfToken = "{{ csrf_token() }}";
var canEdit   = {{ $p_editar ? 'true' : 'false' }};

var table = $('#tbl-users').DataTable({
    responsive: true,
    searchDelay: 500,
    processing: true,
    serverSide: true,
    order: [[0, 'desc']],
    language: window.DT_ES,
    ajax: { url: indexUrl, type: 'GET' },
    columns: [
        { data: 'id',        name: 'id',        width: '60px' },
        { data: 'name',      name: 'name' },
        { data: 'email',     name: 'email' },
        { data: 'role_name', name: 'role_name', orderable: false },
        {
            data: 'status_id', name: 'status_id', orderable: false,
            render: function (d) {
                return d == 1
                    ? '<span class="pill pill-ok"><span class="dot"></span>Activo</span>'
                    : '<span class="pill pill-err"><span class="dot"></span>Inactivo</span>';
            }
        },
        @if($p_editar)
        {
            data: 'id', name: 'action', orderable: false, searchable: false, width: '80px',
            render: function (data, type, row) {
                var icon  = row.status_id == 1
                    ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>'
                    : '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                var title = row.status_id == 1 ? 'Desactivar' : 'Activar';
                return '<button type="button" data-id="' + data + '" class="btn-icon btn-toggle-user" title="' + title + '">' + icon + '</button>';
            }
        },
        @endif
    ],
});

$(document).on('click', '.btn-toggle-user', function () {
    var id = $(this).data('id');
    $.ajax({
        url:  toggleBase + '/' + id + '/toggle',
        type: 'POST',
        data: { _token: csrfToken, _method: 'PATCH' },
        success: function (res) {
            toastr.success(res.message);
            table.ajax.reload(null, false);
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al procesar.');
        }
    });
});

function usersPage() {
    return {
        modalOpen: false,
        saving:    false,

        openModal() {
            document.getElementById('form-user').reset();
            this.clearErrors();
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.modalOpen = false;
            document.body.style.overflow = '';
        },

        clearErrors() {
            document.querySelectorAll('#form-user .field-error').forEach(function (el) {
                el.textContent = '';
                el.style.display = 'none';
            });
            document.querySelectorAll('#form-user .input, #form-user .select').forEach(function (el) {
                el.classList.remove('is-invalid');
            });
        },

        save() {
            this.clearErrors();
            this.saving = true;

            var self = this;

            $.ajax({
                url:  storeUrl,
                type: 'POST',
                data: {
                    _token:                csrfToken,
                    name:                  document.getElementById('u_name').value,
                    email:                 document.getElementById('u_email').value,
                    password:              document.getElementById('u_password').value,
                    password_confirmation: document.getElementById('u_password_confirmation').value,
                },
                success: function (res) {
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
                                var inputId = field === 'role_id' ? 'u_role' : 'u_' + field;
                                $('#' + inputId).addClass('is-invalid');
                            });
                        }
                    } else {
                        toastr.error('Error al crear el usuario.');
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
