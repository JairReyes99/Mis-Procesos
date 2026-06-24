@extends('layout.default')

@section('title', 'Nueva Campaña SMS')

@push('styles')
<style>
    .file-drop {
        border: 2px dashed var(--line-strong);
        border-radius: var(--radius);
        padding: 28px 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        background: var(--bg-muted);
    }
    .file-drop:hover, .file-drop.drag-over {
        border-color: var(--accent);
        background: var(--accent-soft);
    }
    .file-drop input[type="file"] { display: none; }

    .rule-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 8px;
        align-items: end;
    }

    .seg-bar {
        height: 4px;
        background: var(--bg-sunk);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 8px;
    }
    .seg-bar-fill {
        height: 4px;
        background: var(--accent);
        border-radius: 3px;
        transition: width .2s;
    }

    .preview-phone {
        font-family: var(--font-mono);
        font-size: 12px;
        color: var(--ink-2);
    }
    .preview-msg {
        font-size: 12.5px;
        color: var(--ink);
        max-width: 480px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .section-title {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--ink-3);
        margin: 0 0 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .spinner {
        width: 20px; height: 20px;
        border: 2px solid var(--line-strong);
        border-top-color: var(--accent);
        border-radius: 50%;
        animation: spin .6s linear infinite;
        display: inline-block;
        flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

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
    }
    .char-counter .cc-item { display: flex; align-items: center; gap: 4px; }
    .char-counter .cc-val { font-family: var(--font-mono); color: var(--ink); font-weight: 600; }
    .char-counter .cc-sep { color: var(--line-strong); }

    .form-col { display: flex; flex-direction: column; gap: 18px; }

    .msg-tabs { display:flex; border:1px solid var(--line-strong); border-radius:var(--radius-sm); overflow:hidden; }
    .msg-tab { flex:1; padding:8px 10px; font-size:12px; font-weight:500; cursor:pointer; border:none; background:transparent; color:var(--ink-2); transition:background .12s, color .12s; display:flex; align-items:center; justify-content:center; gap:5px; }
    .msg-tab:not(:last-child) { border-right:1px solid var(--line-strong); }
    .msg-tab.active { background:var(--accent-soft); color:var(--accent); }
    .msg-tab:hover:not(.active) { background:var(--bg-sunk); }
    .concat-step { border:1px solid var(--line); border-radius:var(--radius-sm); padding:12px 14px; background:var(--bg); }
    .concat-step + .concat-step { margin-top:6px; }
    .sep-row { display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-top:8px; padding:7px 10px; background:var(--bg-muted); border-radius:var(--radius-sm); }
    .sep-btn { padding:3px 9px; font-size:11.5px; border:1px solid var(--line-strong); border-radius:var(--radius-sm); background:transparent; cursor:pointer; color:var(--ink-2); transition:all .1s; white-space:nowrap; }
    .sep-btn.active { background:var(--accent-soft); color:var(--accent); border-color:var(--accent); font-weight:600; }
    .sep-btn:hover:not(.active) { background:var(--bg-sunk); }

    [x-cloak] { display: none !important; }
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
    <b>Nueva Campaña</b>
</div>

<div class="page-head">
    <div>
        <h1 style="display:flex;align-items:center;gap:10px;">
            <i class="ti ti-send" style="color:var(--accent);font-size:26px;"></i>
            Nueva Campaña SMS
        </h1>
        <p>Configura los parámetros de tu campaña y sube el archivo de destinatarios</p>
    </div>
    <div class="page-head-actions">
        <a href="{{ route('sms.campaigns.index') }}" class="btn">
            <i class="ti ti-arrow-left"></i>
            Volver
        </a>
    </div>
</div>

@php
    $oldSendTypeSlug = '';
    if (old('send_type_id') && isset($sendTypes)) {
        $st = $sendTypes->firstWhere('id', old('send_type_id'));
        $oldSendTypeSlug = $st ? $st->slug : '';
    }
    $oldMsgCol   = old('message_col', '');
    $oldMsgMode  = $oldMsgCol === '__fixed__' ? 'fixed' : ($oldMsgCol === '__concat__' ? 'concat' : 'single');
    $hasTempFile = (bool) old('temp_path');
    $oldConcatCols  = old('message_concat_cols', []);
    $oldConcatSeps  = old('message_concat_seps', []);
    $oldConcatItems = [];
    if (count($oldConcatCols) >= 2) {
        foreach ($oldConcatCols as $i => $col) {
            $oldConcatItems[] = ['col' => $col, 'sep' => $oldConcatSeps[$i] ?? ' '];
        }
    } else {
        $oldConcatItems = [['col' => '', 'sep' => ' '], ['col' => '', 'sep' => ' ']];
    }
    $oldNoSendRules = old('no_send_rules', []);
@endphp

<div x-data="campaignForm()" x-cloak>
    <form
        id="campaign-form"
        action="{{ route('sms.campaigns.store') }}"
        method="POST"
        enctype="multipart/form-data"
        @submit.prevent="submitForm"
    >
        @csrf

        @if($errors->any())
        <div class="alert-danger" style="margin-bottom:20px;border-radius:var(--radius-sm);padding:12px 14px;font-size:13px;">
            <p style="margin:0 0 6px;font-weight:600;display:flex;align-items:center;gap:6px;">
                <i class="ti ti-alert-circle" style="font-size:16px;"></i>
                Corrige los siguientes errores antes de continuar:
            </p>
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Hidden fields --}}
        <input type="hidden" name="temp_path" x-model="tempPath" />
        <input type="hidden" name="send_type_id" x-bind:value="sendTypeId" />
        <input type="hidden" name="message_col" :value="messageCol" />
        {{-- Solo enviar cols/seps de concatenación cuando el modo es concat --}}
        <template x-if="messageMode === 'concat'">
            <div>
                <template x-for="(item, idx) in concatItems" :key="'cc_' + idx">
                    <input type="hidden" name="message_concat_cols[]" :value="item.col" />
                </template>
                <template x-for="(item, idx) in concatItems" :key="'cs_' + idx">
                    <input type="hidden" name="message_concat_seps[]" :value="item.sep || ''" />
                </template>
            </div>
        </template>
        <template x-for="(rule, i) in noSendRules" :key="i">
            <span>
                <input type="hidden" :name="'no_send_rules[' + i + '][from]'" :value="rule.from" />
                <input type="hidden" :name="'no_send_rules[' + i + '][to]'" :value="rule.to" />
            </span>
        </template>

        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- ═══ FILA 1: Información (izq) + Notificación y Restricciones (der) ═══ --}}
            <div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start;">

                {{-- SECCIÓN: Información de la campaña --}}
                <div class="card">
                    <div class="card-h">
                        <i class="ti ti-info-circle" style="color:var(--ink-3);font-size:18px;"></i>
                        <div>
                            <h3>Información de la campaña</h3>
                            <p>Datos básicos para identificar y programar el envío</p>
                        </div>
                    </div>
                    <div class="card-b" style="display:flex;flex-direction:column;gap:16px;">

                        {{-- Nombre --}}
                        <div class="field">
                            <label for="name">Nombre de la campaña <span class="req">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                value="{{ old('name') }}"
                                placeholder="Ej: Promoción Mayo 2026"
                                required
                            />
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Tipo de Envío --}}
                        <div class="field">
                            <label for="send_type">Tipo de envío <span class="req">*</span></label>
                            <select
                                id="send_type"
                                class="select {{ $errors->has('send_type_id') ? 'is-invalid' : '' }}"
                                x-model="sendTypeId"
                                @change="onSendTypeChange"
                                required
                            >
                                <option value="">Selecciona el tipo de envío...</option>
                                @foreach($sendTypes as $st)
                                    <option
                                        value="{{ $st->id }}"
                                        data-slug="{{ $st->slug }}"
                                        {{ old('send_type_id') == $st->id ? 'selected' : '' }}
                                    >{{ $st->name }}</option>
                                @endforeach
                            </select>
                            @error('send_type_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Aviso envío inmediato --}}
                        <div
                            x-show="sendTypeSlug === 'immediate'"
                            x-transition
                            style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:var(--accent-soft);border:1px solid var(--accent);border-radius:var(--radius-sm);"
                        >
                            <i class="ti ti-bolt" style="color:var(--accent);font-size:18px;flex-shrink:0;margin-top:1px;"></i>
                            <div>
                                <p style="margin:0;font-weight:600;font-size:13px;color:var(--ink);">Envío inmediato</p>
                                <p style="margin:4px 0 0;font-size:12.5px;color:var(--ink-2);">Al crear esta campaña, los mensajes comenzarán a enviarse de forma inmediata a todos los destinatarios.</p>
                            </div>
                        </div>

                        {{-- Fecha programada (solo si scheduled) --}}
                        <div class="field" x-show="sendTypeSlug === 'scheduled'" x-transition>
                            <label for="scheduled_at">
                                <i class="ti ti-calendar-time" style="font-size:12px;"></i>
                                Fecha y hora de envío <span class="req">*</span>
                            </label>
                            <input
                                type="datetime-local"
                                id="scheduled_at"
                                name="scheduled_at"
                                class="input {{ $errors->has('scheduled_at') ? 'is-invalid' : '' }}"
                                :min="minDatetime"
                                :required="sendTypeSlug === 'scheduled'"
                                value="{{ old('scheduled_at') }}"
                            />
                            <p class="field-help">La campaña se enviará automáticamente en la fecha y hora indicadas.</p>
                            @error('scheduled_at')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>

                {{-- Columna derecha: Notificación + Restricciones apiladas --}}
                <div style="display:flex;flex-direction:column;gap:20px;">

                    {{-- SECCIÓN: Notificación --}}
                    <div class="card">
                        <div class="card-h">
                            <i class="ti ti-mail" style="color:var(--ink-3);font-size:18px;"></i>
                            <div>
                                <h3>Notificación</h3>
                                <p>Resumen por correo al terminar el envío</p>
                            </div>
                        </div>
                        <div class="card-b" style="display:flex;flex-direction:column;gap:16px;">
                            <div class="field">
                                <label for="notification_email">
                                    <i class="ti ti-at" style="font-size:11px;"></i>
                                    Correo de notificación
                                    <span style="font-size:11px;color:var(--ink-4);font-weight:400;">(opcional)</span>
                                </label>
                                <input
                                    type="email"
                                    id="notification_email"
                                    name="notification_email"
                                    class="input {{ $errors->has('notification_email') ? 'is-invalid' : '' }}"
                                    value="{{ old('notification_email', $companyEmail ?? '') }}"
                                    placeholder="notificacion@empresa.com"
                                />
                                <p class="field-help">
                                    @if($companyEmail ?? false)
                                        Se usará tu correo por defecto. Puedes cambiarlo si lo necesitas.
                                    @else
                                        Ingresa el correo donde quieres recibir el resumen de la campaña.
                                    @endif
                                </p>
                                @error('notification_email')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN: Restricciones horarias --}}
                    <div class="card">
                        <div class="card-h">
                            <i class="ti ti-ban" style="color:var(--ink-3);font-size:18px;"></i>
                            <div>
                                <h3>Restricciones horarias</h3>
                                <p>No enviar en estos rangos</p>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm"
                                style="margin-left:auto;"
                                @click="addRule"
                            >
                                <i class="ti ti-plus"></i>
                                Agregar
                            </button>
                        </div>
                        <div class="card-b">
                            <div
                                x-show="noSendRules.length === 0"
                                style="text-align:center;padding:12px 0;color:var(--ink-4);"
                            >
                                <i class="ti ti-clock-off" style="font-size:20px;margin-right:6px;vertical-align:middle;"></i>
                                <span style="font-size:13px;">Sin restricciones configuradas</span>
                            </div>

                            <div x-show="noSendRules.length > 0" style="display:flex;flex-direction:column;gap:10px;">
                                <template x-for="(rule, i) in noSendRules" :key="i">
                                    <div class="rule-row">
                                        <div class="field">
                                            <label :for="'rule_from_' + i" style="font-size:11px;">
                                                <i class="ti ti-clock" style="font-size:10px;"></i> De
                                            </label>
                                            <input type="time" :id="'rule_from_' + i" class="input" x-model="rule.from" />
                                        </div>
                                        <div class="field">
                                            <label :for="'rule_to_' + i" style="font-size:11px;">
                                                <i class="ti ti-clock" style="font-size:10px;"></i> A
                                            </label>
                                            <input type="time" :id="'rule_to_' + i" class="input" x-model="rule.to" />
                                        </div>
                                        <button
                                            type="button"
                                            class="btn-icon"
                                            style="color:var(--err);margin-bottom:1px;"
                                            title="Quitar"
                                            @click="removeRule(i)"
                                        >
                                            <i class="ti ti-x" style="font-size:14px;"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>
                {{-- Fin columna derecha --}}

            </div>
            {{-- Fin fila 1 --}}

            {{-- ═══ FILA 2: Archivo de destinatarios (ancho completo) ═══ --}}
            <div class="card">
                <div class="card-h">
                    <i class="ti ti-table-import" style="color:var(--ink-3);font-size:18px;"></i>
                    <div>
                        <h3>Archivo de destinatarios</h3>
                        <p>Sube un archivo Excel con los teléfonos y mensajes</p>
                    </div>
                </div>
                <div class="card-b" style="display:flex;flex-direction:column;gap:16px;">

                    {{-- File drop --}}
                    <div>
                        <label class="section-title">
                            <i class="ti ti-file-spreadsheet"></i>
                            Archivo Excel (.xlsx, .xls) <span class="req">*</span>
                        </label>
                        <div
                            class="file-drop"
                            :class="{ 'drag-over': isDragging }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="onDrop($event)"
                            @click="$refs.fileInput.click()"
                        >
                            <input
                                type="file"
                                name="excel_file"
                                accept=".xlsx,.xls"
                                x-ref="fileInput"
                                @change="onFileChange($event)"
                            />
                            <div x-show="!parsing && Object.keys(headers).length === 0">
                                <i class="ti ti-cloud-upload" style="font-size:32px;color:var(--ink-4);display:block;margin-bottom:8px;"></i>
                                <p style="margin:0;font-weight:500;color:var(--ink-2);">Haz clic o arrastra tu archivo aquí</p>
                                <p style="margin:4px 0 0;font-size:12px;color:var(--ink-4);">Formatos soportados: .xlsx, .xls — Máx. 10 MB</p>
                            </div>
                            <div x-show="parsing" style="display:flex;align-items:center;justify-content:center;gap:10px;">
                                <span class="spinner"></span>
                                <span style="font-size:13px;color:var(--ink-3);">Leyendo archivo...</span>
                            </div>
                            <div x-show="!parsing && tempPath && Object.keys(headers).length === 0" style="display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap;padding:4px 0;">
                                <i class="ti ti-files" style="font-size:20px;color:var(--accent);flex-shrink:0;"></i>
                                <span style="font-weight:500;color:var(--ink-2);">Archivo conservado del envío anterior</span>
                                <span style="font-size:12px;color:var(--ink-4);">— Sube de nuevo para cambiar columnas</span>
                            </div>
                            <div x-show="!parsing && Object.keys(headers).length > 0" style="display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap;">
                                <i class="ti ti-circle-check" style="font-size:22px;color:var(--ok);flex-shrink:0;"></i>
                                <span style="font-weight:500;color:var(--ink-2);" x-text="fileName"></span>
                                <span x-show="uploading" style="font-size:12px;color:var(--ink-4);display:flex;align-items:center;gap:4px;">
                                    <span class="spinner" style="width:12px;height:12px;border-width:2px;"></span>
                                    Guardando...
                                </span>
                                <span x-show="!uploading && totalRecords > 0" style="font-size:12px;color:var(--ink-4);" x-text="'(' + totalRecords.toLocaleString('es-MX') + ' registros)'"></span>
                            </div>
                        </div>
                        <div x-show="uploadError" class="alert-danger" style="margin-top:8px;border-radius:var(--radius-sm);padding:10px 12px;font-size:13px;display:flex;align-items:center;gap:6px;">
                            <i class="ti ti-alert-circle"></i>
                            <span x-text="uploadError"></span>
                        </div>
                    </div>

                    {{-- Config preservada (sin parseo en memoria tras error de validación) --}}
                    <div x-show="tempPath && Object.keys(headers).length === 0 && !parsing" x-transition style="margin-top:12px;padding:14px 16px;background:var(--accent-soft, #eff6ff);border:1px solid var(--accent);border-radius:var(--radius-sm);">
                        <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:var(--ink);display:flex;align-items:center;gap:6px;">
                            <i class="ti ti-info-circle" style="font-size:15px;color:var(--accent);"></i>
                            El archivo fue conservado. Vuelve a subirlo si quieres cambiar las columnas.
                        </p>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12.5px;">
                            <div style="padding:8px 10px;background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-sm);">
                                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-3);margin-bottom:3px;">
                                    <i class="ti ti-phone" style="font-size:10px;"></i> Columna teléfono
                                </div>
                                <span style="font-family:var(--font-mono);font-size:13px;font-weight:600;color:var(--ink);" x-text="phoneCol || '—'"></span>
                            </div>
                            <div style="padding:8px 10px;background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-sm);">
                                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-3);margin-bottom:3px;">
                                    <i class="ti ti-message" style="font-size:10px;"></i> Modo mensaje
                                </div>
                                <span style="font-size:13px;font-weight:600;color:var(--ink);"
                                    x-text="messageMode === 'fixed' ? 'Mensaje fijo' : (messageMode === 'concat' ? 'Columnas concatenadas' : ('Columna ' + (messageCol || '—')))">
                                </span>
                            </div>
                        </div>
                        @error('phone_col')<p class="field-error" style="margin-top:8px;">{{ $message }}</p>@enderror
                        @error('message_col')<p class="field-error" style="margin-top:4px;">{{ $message }}</p>@enderror
                        @error('fixed_message')<p class="field-error" style="margin-top:4px;">{{ $message }}</p>@enderror
                        @error('message_concat_cols')<p class="field-error" style="margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    @error('temp_path')
                    <p class="field-error" style="margin-top:8px;display:flex;align-items:center;gap:5px;">
                        <i class="ti ti-alert-circle" style="font-size:13px;"></i> {{ $message }}
                    </p>
                    @enderror

                    {{-- Columnas — visibles tras parseo SheetJS; layout 3 cols en pantallas grandes --}}
                    <div x-show="Object.keys(headers).length > 0 && !parsing" x-transition>
                        <hr class="divider" style="margin-bottom:16px;" />
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

                            {{-- Columna de Teléfono --}}
                            <div class="field">
                                <label for="phone_col">
                                    <i class="ti ti-phone" style="font-size:11px;"></i>
                                    Columna de teléfono <span class="req">*</span>
                                </label>
                                <select
                                    id="phone_col"
                                    name="phone_col"
                                    class="select"
                                    x-model="phoneCol"
                                    @change="onColumnChange"
                                    :required="!!tempPath"
                                >
                                    <option value="">Selecciona la columna de teléfonos...</option>
                                    <template x-for="(label, key) in headers" :key="key">
                                        <option :value="key" x-text="label + ' (' + key + ')'"></option>
                                    </template>
                                </select>
                                @error('phone_col')<p class="field-error">{{ $message }}</p>@enderror
                            </div>

                            {{-- Modo mensaje --}}
                            <div class="field">
                                <label>
                                    <i class="ti ti-message" style="font-size:11px;"></i>
                                    Mensaje <span class="req">*</span>
                                </label>
                                <div class="msg-tabs" style="margin-bottom:8px;">
                                    <button type="button" class="msg-tab" :class="{active: messageMode==='single'}" @click="setMessageMode('single')">
                                        <i class="ti ti-column"></i>
                                        Columna única
                                    </button>
                                    <button type="button" class="msg-tab" :class="{active: messageMode==='concat'}" @click="setMessageMode('concat')">
                                        <i class="ti ti-columns"></i>
                                        Varias
                                    </button>
                                    <button type="button" class="msg-tab" :class="{active: messageMode==='fixed'}" @click="setMessageMode('fixed')">
                                        <i class="ti ti-pencil"></i>
                                        Fijo
                                    </button>
                                </div>
                                <select
                                    id="message_col_select"
                                    class="select"
                                    x-show="messageMode === 'single'"
                                    x-model="messageCol"
                                    @change="onColumnChange"
                                    :required="messageMode === 'single' && !!tempPath"
                                >
                                    <option value="">Selecciona la columna del mensaje...</option>
                                    <template x-for="(label, key) in headers" :key="key">
                                        <option :value="key" x-text="label + ' (' + key + ')'"></option>
                                    </template>
                                </select>
                                <p x-show="messageMode === 'concat'" style="margin:4px 0 0;font-size:12px;color:var(--ink-3);">
                                    Configura las columnas y el separador en la sección de abajo.
                                </p>
                                <p x-show="messageMode === 'fixed'" style="margin:4px 0 0;font-size:12px;color:var(--ink-3);">
                                    Escribe el mensaje que recibirán todos los destinatarios.
                                </p>
                            </div>

                        </div>

                        {{-- ─── Modo: Varias columnas ─── --}}
                        <div x-show="messageMode === 'concat'" x-transition style="margin-top:14px;">

                            <template x-for="(item, idx) in concatItems" :key="idx">
                                <div>
                                    {{-- Paso: columna --}}
                                    <div class="concat-step">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="font-size:11px;font-weight:600;color:var(--ink-3);min-width:56px;" x-text="'Columna ' + (idx + 1)"></span>
                                            <select
                                                class="select"
                                                style="flex:1;"
                                                x-model="item.col"
                                                @change="_buildPreview()"
                                            >
                                                <option value="">Selecciona una columna...</option>
                                                <template x-for="(label, key) in headers" :key="key">
                                                    <template x-if="key !== phoneCol">
                                                        <option :value="key" x-text="label + ' (' + key + ')'"></option>
                                                    </template>
                                                </template>
                                            </select>
                                            <button
                                                type="button"
                                                class="btn-icon"
                                                style="color:var(--err);flex-shrink:0;"
                                                x-show="concatItems.length > 2"
                                                title="Quitar"
                                                @click="removeConcatItem(idx)"
                                            ><i class="ti ti-x" style="font-size:14px;"></i></button>
                                        </div>

                                        {{-- Separador (solo si NO es el último) --}}
                                        <div class="sep-row" x-show="idx < concatItems.length - 1">
                                            <span style="font-size:11px;color:var(--ink-3);margin-right:2px;">Sep:</span>
                                            <button type="button" class="sep-btn" :class="{active: item.sep===''}"    @click="item.sep='';    _buildPreview();">∅</button>
                                            <button type="button" class="sep-btn" :class="{active: item.sep===' '}"   @click="item.sep=' ';   _buildPreview();">espacio</button>
                                            <button type="button" class="sep-btn" :class="{active: item.sep===', '}"  @click="item.sep=', ';  _buildPreview();">, coma</button>
                                            <button type="button" class="sep-btn" :class="{active: item.sep==='. '}"  @click="item.sep='. ';  _buildPreview();">. punto</button>
                                            <button type="button" class="sep-btn" :class="{active: item.sep===' - '}" @click="item.sep=' - '; _buildPreview();">— guión</button>
                                            <button type="button" class="sep-btn" :class="{active: item.sep==='\n'}"  @click="item.sep='\n';  _buildPreview();">↵ salto</button>
                                            <input
                                                type="text"
                                                class="input"
                                                style="width:60px;padding:3px 7px;font-family:var(--font-mono);font-size:12px;"
                                                x-model="item.sep"
                                                @input="_buildPreview()"
                                                placeholder="otro"
                                            />
                                        </div>
                                    </div>

                                    {{-- Flecha visual entre pasos --}}
                                    <div x-show="idx < concatItems.length - 1" style="text-align:center;color:var(--ink-4);font-size:11px;padding:2px 0;line-height:1;">↓</div>
                                </div>
                            </template>

                            <div style="display:flex;align-items:center;gap:12px;margin-top:10px;">
                                <button type="button" class="btn btn-sm" @click="addConcatItem()">
                                    <i class="ti ti-plus"></i>
                                    Agregar columna
                                </button>
                                <div x-show="previewData.length > 0" style="flex:1;padding:8px 12px;background:var(--bg-sunk);border:1px solid var(--line);border-radius:var(--radius-sm);">
                                    <span style="font-size:10.5px;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em;">Vista previa (fila 1): </span>
                                    <span style="font-size:12.5px;font-family:var(--font-mono);word-break:break-all;white-space:pre-wrap;" x-text="previewData[0]?.message || '—'"></span>
                                </div>
                            </div>

                            <p x-show="concatItems.some(i => !i.col)" style="font-size:12px;color:var(--err);margin:6px 0 0;">
                                Selecciona la columna en cada paso.
                            </p>
                        </div>

                        {{-- ─── Modo: Mensaje fijo ─── --}}
                        <div
                            class="field"
                            style="margin-top:14px;"
                            x-show="messageMode === 'fixed'"
                            x-transition
                        >
                            <label for="fixed_message">
                                <i class="ti ti-pencil" style="font-size:11px;"></i>
                                Mensaje fijo <span class="req">*</span>
                            </label>
                            <textarea
                                id="fixed_message"
                                name="fixed_message"
                                class="input"
                                style="resize:vertical;min-height:88px;font-family:var(--font-mono);font-size:13px;line-height:1.5;"
                                x-model="fixedMessage"
                                @input="updateSegments"
                                placeholder="Escribe el mensaje que se enviará a todos los destinatarios..."
                                :required="messageCol === '__fixed__'"
                            ></textarea>
                            @error('fixed_message')<p class="field-error">{{ $message }}</p>@enderror

                            {{-- Contador de caracteres --}}
                            <div class="char-counter" style="margin-top:6px;">
                                <div class="cc-item">
                                    <i class="ti ti-binary" style="font-size:11px;"></i>
                                    Encoding:
                                    <span class="cc-val" x-text="segmentInfo.encoding"></span>
                                </div>
                                <span class="cc-sep">|</span>
                                <div class="cc-item">
                                    Caracteres:
                                    <span
                                        class="cc-val"
                                        x-text="segmentInfo.length + ' / ' + segmentInfo.limit"
                                        :style="segmentInfo.length > segmentInfo.limit ? 'color:var(--err)' : ''"
                                    ></span>
                                </div>
                                <span class="cc-sep">|</span>
                                <div class="cc-item">
                                    <i class="ti ti-stack" style="font-size:11px;"></i>
                                    Segmentos:
                                    <span class="cc-val" x-text="segmentInfo.segments + ' SMS'"></span>
                                </div>
                            </div>
                            <div class="seg-bar">
                                <div
                                    class="seg-bar-fill"
                                    :style="'width:' + Math.min(100, Math.round((segmentInfo.length / segmentInfo.limit) * 100)) + '%'"
                                ></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            {{-- Fin fila 2 --}}

            {{-- Botones de acción --}}
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <div x-show="submitBlockReason" style="font-size:12.5px;color:var(--err);display:flex;align-items:center;gap:6px;">
                    <i class="ti ti-alert-circle" style="font-size:14px;flex-shrink:0;"></i>
                    <span x-text="submitBlockReason"></span>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" class="btn" @click="resetForm">
                        <i class="ti ti-refresh"></i>
                        Limpiar
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="submitting || !canSubmit"
                        :style="(!canSubmit) ? 'opacity:0.5;cursor:not-allowed;' : ''"
                    >
                        <span x-show="submitting" class="spinner" style="width:14px;height:14px;border-width:2px;"></span>
                        <i class="ti ti-send" x-show="!submitting"></i>
                        <span x-text="submitMsg"></span>
                    </button>
                </div>
            </div>

            {{-- ═══════════════════════════════ SECCIÓN INFERIOR — VISTA PREVIA ═══════════════════════════════ --}}
            <div>
                <div class="card">
                    <div class="card-h">
                        <i class="ti ti-device-mobile" style="color:var(--ink-3);font-size:18px;"></i>
                        <div>
                            <h3>
                                Vista previa
                                <span
                                    x-show="totalRecords > 0"
                                    style="font-family:var(--font-mono);font-size:11px;font-weight:400;color:var(--ink-3);margin-left:6px;"
                                    x-text="'(' + totalRecords.toLocaleString('es-MX') + ' registros)'"
                                ></span>
                            </h3>
                            <p>Muestra los primeros resultados parseados</p>
                        </div>
                    </div>

                    {{-- Estado vacío --}}
                    <div x-show="previewData.length === 0" style="padding:32px 18px;text-align:center;color:var(--ink-4);">
                        <i class="ti ti-file-search" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                        <p style="margin:0;font-size:13px;">Sube un archivo y selecciona las columnas para ver la vista previa</p>
                    </div>

                    {{-- Tabla de preview --}}
                    <div x-show="previewData.length > 0" style="overflow-x:auto;">
                        <table class="tbl" style="font-size:12.5px;">
                            <thead>
                                <tr>
                                    <th>Teléfono</th>
                                    <th>Mensaje</th>
                                    <th style="width:40px;">SMS</th>
                                    <th style="width:70px;">Encoding</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, idx) in previewPageData" :key="idx">
                                    <tr>
                                        <td>
                                            <template x-if="!row.valid">
                                                <i class="ti ti-alert-circle" style="color:var(--err);font-size:11px;margin-right:3px;"></i>
                                            </template>
                                            <span class="preview-phone" :style="!row.valid ? 'color:var(--err);' : ''" x-text="row.phone || '—'"></span>
                                        </td>
                                        <td>
                                            <span class="preview-msg" :title="row.message" x-text="row.message ? row.message.substring(0, 50) + (row.message.length > 50 ? '…' : '') : '—'"></span>
                                        </td>
                                        <td>
                                            <span class="mono" style="font-size:11px;" x-text="row.segments || '—'"></span>
                                        </td>
                                        <td>
                                            <span
                                                class="pill"
                                                :class="row.encoding === 'GSM-7' ? 'pill-ok' : 'pill-info'"
                                                style="font-size:10.5px;"
                                                x-text="row.encoding || '—'"
                                            ></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Banner teléfonos inválidos --}}
                    <div x-show="countingInvalid" style="padding:10px 18px;background:var(--bg-muted);border-top:1px solid var(--line);font-size:13px;display:flex;align-items:center;gap:8px;color:var(--ink-3);">
                        <span class="spinner" style="width:14px;height:14px;border-width:2px;flex-shrink:0;"></span>
                        <span>Verificando formato de teléfonos en todo el archivo...</span>
                    </div>
                    <div x-show="!countingInvalid && invalidPhones > 0" style="padding:10px 18px;background:var(--warn-soft, #fffbeb);border-top:1px solid var(--warn, #f59e0b);font-size:13px;display:flex;align-items:center;gap:8px;color:var(--ink-2);">
                        <i class="ti ti-alert-triangle" style="color:var(--warn,#f59e0b);font-size:16px;flex-shrink:0;"></i>
                        <span>
                            <strong x-text="invalidPhones.toLocaleString('es-MX')"></strong> número(s) no tienen formato válido (se requieren 12 dígitos con código de país, ej: 521234567890).
                            <span x-show="serverInvalidCount !== null"> Serán excluidos al crear la campaña.</span>
                            <span x-show="serverInvalidCount === null" style="color:var(--ink-3);font-size:12px;"> (muestra de los primeros registros)</span>
                        </span>
                    </div>

                    {{-- Paginación preview --}}
                    <div x-show="totalPages > 1" class="pag">
                        <span x-text="'Página ' + previewPage + ' de ' + totalPages + ' | ' + totalRecords.toLocaleString('es-MX') + ' registros'"></span>
                        <div class="pag-ctrl">
                            <button
                                type="button"
                                class="pag-btn"
                                :disabled="previewPage <= 1"
                                @click="previewPage = Math.max(1, previewPage - 1)"
                            ><i class="ti ti-chevron-left" style="font-size:12px;"></i></button>
                            <button
                                type="button"
                                class="pag-btn"
                                :disabled="previewPage >= totalPages"
                                @click="previewPage = Math.min(totalPages, previewPage + 1)"
                            ><i class="ti ti-chevron-right" style="font-size:12px;"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Info SMS cost estimate --}}
                <div
                    x-show="totalRecords > 0 && segmentInfo.segments > 0"
                    class="card"
                    style="margin-top:14px;"
                >
                    <div class="card-b" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="stat" style="background:var(--bg-muted);">
                            <div class="stat-label"><i class="ti ti-users"></i> Destinatarios</div>
                            <div class="stat-value" style="font-size:20px;" x-text="totalRecords.toLocaleString('es-MX')"></div>
                        </div>
                        <div class="stat" style="background:var(--bg-muted);">
                            <div class="stat-label"><i class="ti ti-stack"></i> SMS por envío</div>
                            <div class="stat-value" style="font-size:20px;" x-text="segmentInfo.segments"></div>
                        </div>
                    </div>
                </div>

            </div>
            {{-- Fin columna derecha --}}

        </div>
    </form>
</div>

@endsection

@push('scripts')
{{-- SheetJS: parseo de Excel en el navegador, sin round-trip al servidor --}}
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script>
"use strict";

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   SMS SEGMENT CALCULATOR — 100% local, sin AJAX
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
const GSM7_CHARS = new Set(
    '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ !"#¤%&\'()*+,-./' +
    '0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿' +
    'abcdefghijklmnopqrstuvwxyzäöñüàÆæßÉ'
);
const GSM7_EXT = new Set('^{}[]\\~|€');

function calcSegments(text) {
    if (!text || text.length === 0) {
        return { segments: 0, encoding: 'GSM-7', length: 0, remaining: 160, limit: 160 };
    }
    const isGsm7 = [...text].every(c => GSM7_CHARS.has(c) || GSM7_EXT.has(c));
    const length = isGsm7
        ? [...text].reduce((n, c) => n + (GSM7_EXT.has(c) ? 2 : 1), 0)
        : text.length;
    const singleLimit = isGsm7 ? 160 : 70;
    const multiLimit  = isGsm7 ? 153 : 67;
    const segments    = length === 0 ? 0 : (length <= singleLimit ? 1 : Math.ceil(length / multiLimit));
    const limit       = segments <= 1 ? singleLimit : multiLimit;
    const used        = segments <= 1 ? length : length - (segments - 1) * multiLimit;
    return { segments, encoding: isGsm7 ? 'GSM-7' : 'Unicode', length, remaining: limit - used, limit };
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ALPINE.JS COMPONENT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function campaignForm() {
    return {
        /* Tipo de envío */
        sendTypeId:   {!! json_encode(old('send_type_id', '')) !!},
        sendTypeSlug: {!! json_encode($oldSendTypeSlug) !!},
        minDatetime: (function () {
            var d = new Date();
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            return d.toISOString().slice(0, 16);
        })(),

        /* Restricciones horarias */
        noSendRules: {!! json_encode($oldNoSendRules ?: []) !!},

        /* Archivo */
        tempPath:    {!! json_encode(old('temp_path', '')) !!},
        fileName:    {!! $hasTempFile ? json_encode('Archivo conservado del envío anterior') : "''" !!},
        parsing:     false,
        uploading:   false,
        uploadDone:  {{ $hasTempFile ? 'true' : 'false' }},
        isDragging:  false,
        uploadError: '',
        _uploadResolve: null,

        /* Cabeceras y filas locales (SheetJS, max 50 filas de datos) */
        headers:   {},
        localRows: [],

        /* Selección de columnas */
        phoneCol:     {!! json_encode(old('phone_col', '')) !!},
        messageCol:   {!! json_encode($oldMsgCol) !!},
        fixedMessage: {!! json_encode(old('fixed_message', '')) !!},

        /* Modo de mensaje */
        messageMode:  {!! json_encode($oldMsgMode) !!},
        concatItems:  {!! json_encode($oldConcatItems) !!},

        /* Calculadora SMS */
        segmentInfo: { segments: 0, encoding: 'GSM-7', length: 0, remaining: 160, limit: 160 },

        /* Preview */
        previewData:       [],
        previewPage:       1,
        previewPageSize:   10,
        totalRecords:      0,
        serverInvalidCount: null,
        serverValidCount:   null,
        countingInvalid:    false,

        /* Submit */
        submitting: false,
        submitMsg:  'Crear Campaña',

        /* ─── Computed ─── */
        get previewPageData() {
            var s = (this.previewPage - 1) * this.previewPageSize;
            return this.previewData.slice(s, s + this.previewPageSize);
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.previewData.length / this.previewPageSize));
        },
        get canSubmit() {
            if (!this.tempPath) return false;
            if (!this.phoneCol) return false;
            if (this.messageMode === 'single' && !this.messageCol) return false;
            if (this.messageMode === 'fixed' && !this.fixedMessage.trim()) return false;
            if (this.messageMode === 'concat') {
                if (this.concatItems.length < 2) return false;
                if (this.concatItems.some(i => !i.col)) return false;
            }
            // Solo aplicar checks de conteo cuando el archivo fue parseado en esta sesión
            if (Object.keys(this.headers).length > 0) {
                if (this.countingInvalid) return false;
                if (this.serverValidCount === 0) return false;
            }
            return true;
        },
        get submitBlockReason() {
            if (this.countingInvalid) return 'Verificando formato de teléfonos, espera un momento...';
            if (this.serverValidCount === 0) return 'No hay teléfonos con formato válido en el archivo. Corrige el archivo antes de continuar.';
            if (this.messageMode === 'concat' && this.concatItems.some(i => !i.col)) return 'Selecciona la columna en cada paso del mensaje.';
            return null;
        },
        get invalidPhones() {
            if (this.serverInvalidCount !== null) return this.serverInvalidCount;
            return this.previewData.filter(r => !r.valid).length;
        },

        /* ─── Métodos ─── */
        addRule()     { this.noSendRules.push({ from: '', to: '' }); },
        removeRule(i) { this.noSendRules.splice(i, 1); },

        onSendTypeChange(e) {
            var opt = e.target.options[e.target.selectedIndex];
            this.sendTypeSlug = opt ? (opt.getAttribute('data-slug') || '') : '';
        },

        onDrop(e) {
            this.isDragging = false;
            var file = e.dataTransfer.files[0];
            if (file) this.handleFile(file);
        },
        onFileChange(e) {
            var file = e.target.files[0];
            if (file) this.handleFile(file);
        },

        handleFile(file) {
            this.uploadError        = '';
            this.tempPath           = '';
            this.uploadDone         = false;
            this.headers            = {};
            this.localRows          = [];
            this.previewData        = [];
            this.totalRecords       = 0;
            this.serverInvalidCount = null;
            this.serverValidCount   = null;
            this.countingInvalid    = false;
            this.phoneCol           = '';
            this.messageCol         = '';
            this.messageMode        = 'single';
            this.concatItems        = [{ col: '', sep: ' ' }, { col: '', sep: ' ' }];
            this.fileName           = file.name;
            this._uploadResolve     = null;

            this.parsing = true;
            this._parseLocally(file).then(() => {
                this.parsing = false;
                var keys = Object.keys(this.headers);
                if (keys.length === 1) {
                    this.phoneCol    = keys[0];
                    this.messageCol  = '__fixed__';
                    this.messageMode = 'fixed';
                    this._buildPreview();
                }
            });

            this.uploading = true;
            this._uploadToServer(file)
                .then(data => {
                    this.tempPath     = data.temp_path;
                    this.totalRecords = data.total_records || 0;
                    this.uploadDone   = true;
                    if (this._uploadResolve) {
                        this._uploadResolve();
                        this._uploadResolve = null;
                    }
                    if (this.phoneCol) this._fetchInvalidCount();
                })
                .catch(msg => {
                    this.uploadError = msg || 'Error al subir el archivo.';
                    this.uploadDone  = false;
                    this.tempPath    = '';
                })
                .finally(() => { this.uploading = false; });
        },

        _parseLocally(file) {
            return new Promise(resolve => {
                var reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        var wb = XLSX.read(e.target.result, {
                            type: 'array', sheetRows: 51, raw: true,
                        });
                        var ws   = wb.Sheets[wb.SheetNames[0]];
                        var rows = XLSX.utils.sheet_to_json(ws, {
                            header: 'A', raw: true, defval: '',
                        });
                        if (rows.length === 0) {
                            this.uploadError = 'El archivo está vacío o no tiene datos.';
                            return resolve();
                        }
                        var hdrRow = rows[0];
                        var hdrs   = {};
                        Object.keys(hdrRow).forEach(col => {
                            if (hdrRow[col] !== '' && hdrRow[col] !== null) {
                                hdrs[col] = String(hdrRow[col]);
                            }
                        });
                        this.headers   = hdrs;
                        this.localRows = rows.slice(1);
                    } catch (_) {
                        this.uploadError = 'No se pudo leer el archivo. Verifica que sea un Excel válido.';
                    }
                    resolve();
                };
                reader.onerror = () => {
                    this.uploadError = 'Error al leer el archivo.';
                    resolve();
                };
                reader.readAsArrayBuffer(file);
            });
        },

        _uploadToServer(file) {
            var fd = new FormData();
            fd.append('excel_file', file);
            fd.append('_token', '{{ csrf_token() }}');
            return fetch('{{ route("sms.campaigns.upload_file") }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || data.status === 'error') {
                    return Promise.reject(data.message || 'Error al subir el archivo.');
                }
                return data;
            });
        },

        /* ─── Modo de mensaje ─── */
        setMessageMode(mode) {
            this.messageMode = mode;
            if (mode === 'fixed') {
                this.messageCol = '__fixed__';
            } else if (mode === 'concat') {
                this.messageCol = '__concat__';
                this._initConcatItems();
            } else {
                this.messageCol = '';
            }
            this.previewPage = 1;
            this._buildPreview();
        },

        _initConcatItems() {
            // Limpiar selecciones que ahora son phoneCol
            this.concatItems = this.concatItems.map(item => ({
                col: item.col === this.phoneCol ? '' : item.col,
                sep: item.sep !== undefined ? item.sep : ' ',
            }));
            // Si los primeros dos están vacíos, pre-seleccionar primeras columnas disponibles
            var available = Object.keys(this.headers).filter(k => k !== this.phoneCol);
            if (!this.concatItems[0].col && available[0]) this.concatItems[0].col = available[0];
            if (!this.concatItems[1].col && available[1]) this.concatItems[1].col = available[1];
        },

        addConcatItem() {
            this.concatItems.push({ col: '', sep: ' ' });
        },

        removeConcatItem(i) {
            if (this.concatItems.length <= 2) return;
            this.concatItems.splice(i, 1);
            this._buildPreview();
        },

        onColumnChange() {
            this.previewPage = 1;
            if (this.messageMode === 'concat') {
                this._initConcatItems();
            }
            var canPreview = this.phoneCol && (
                (this.messageMode === 'single' && this.messageCol) ||
                this.messageMode === 'fixed' ||
                (this.messageMode === 'concat' && this.concatCols.filter(c => c.enabled).length > 0)
            );
            if (canPreview) this._buildPreview();
            this.serverInvalidCount = null;
            this.serverValidCount   = null;
            if (this.phoneCol && this.uploadDone) this._fetchInvalidCount();
        },

        _buildPreview() {
            if (!this.phoneCol || this.localRows.length === 0) return;
            if (this.messageMode === 'single' && !this.messageCol) return;
            if (this.messageMode === 'concat' && (this.concatItems.length < 2 || this.concatItems.some(i => !i.col))) return;

            var useFixed  = this.messageMode === 'fixed';
            var useConcat = this.messageMode === 'concat';
            var fixedMsg  = this.fixedMessage;
            var pCol      = this.phoneCol;
            var mCol      = this.messageCol;
            var cItems    = this.concatItems;

            var rows = [];
            for (var i = 0; i < this.localRows.length; i++) {
                var row   = this.localRows[i];
                var phone = this._normalizePhone(String(row[pCol] != null ? row[pCol] : ''));
                if (!phone) continue;
                var valid = this._isValidPhone(phone);
                var msg;
                if (useFixed) {
                    msg = fixedMsg;
                } else if (useConcat) {
                    var parts = [];
                    for (var ci = 0; ci < cItems.length; ci++) {
                        parts.push(String(row[cItems[ci].col] != null ? row[cItems[ci].col] : ''));
                        if (ci < cItems.length - 1) parts.push(cItems[ci].sep || '');
                    }
                    msg = parts.join('');
                } else {
                    msg = String(row[mCol] != null ? row[mCol] : '');
                }
                var seg = calcSegments(msg);
                rows.push({ phone: phone, message: msg, segments: seg.segments, encoding: seg.encoding, valid: valid });
            }
            this.previewData = rows;
            this.previewPage = 1;
        },

        _fetchInvalidCount() {
            if (!this.tempPath || !this.phoneCol) return;
            this.countingInvalid    = true;
            this.serverInvalidCount = null;
            this.serverValidCount   = null;
            var fd = new FormData();
            fd.append('temp_path', this.tempPath);
            fd.append('phone_col', this.phoneCol);
            fd.append('_token', '{{ csrf_token() }}');
            fetch('{{ route("sms.campaigns.count_phones") }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    this.serverInvalidCount = data.invalid;
                    this.serverValidCount   = data.valid;
                }
            })
            .catch(() => {})
            .finally(() => { this.countingInvalid = false; });
        },

        _normalizePhone(raw) {
            var d = raw.replace(/\D/g, '');
            if (d.length === 10) return '52' + d;
            if (d.length === 12 && d.startsWith('52')) return d;
            return d;
        },
        _isValidPhone(phone) {
            return /^\d{12}$/.test(phone) && phone.startsWith('52');
        },

        updateSegments() {
            this.segmentInfo = calcSegments(this.fixedMessage);
            if (this.messageMode === 'fixed' && this.phoneCol && this.localRows.length > 0) {
                clearTimeout(this._previewTimer);
                this._previewTimer = setTimeout(() => this._buildPreview(), 300);
            }
        },

        resetForm() {
            Object.assign(this, {
                sendTypeId: '', sendTypeSlug: '',
                noSendRules: [],
                tempPath: '', fileName: '',
                parsing: false, uploading: false, uploadDone: false,
                uploadError: '', _uploadResolve: null,
                headers: {}, localRows: [],
                phoneCol: '', messageCol: '', fixedMessage: '',
                messageMode: 'single', concatItems: [{ col: '', sep: ' ' }, { col: '', sep: ' ' }],
                segmentInfo: { segments: 0, encoding: 'GSM-7', length: 0, remaining: 160, limit: 160 },
                previewData: [], previewPage: 1, totalRecords: 0,
                serverInvalidCount: null, serverValidCount: null, countingInvalid: false,
                submitting: false, submitMsg: 'Crear Campaña',
            });
            document.getElementById('campaign-form').reset();
        },

        async submitForm() {
            if (!this.canSubmit) {
                toastr.warning('Completa todos los campos requeridos antes de continuar.');
                return;
            }
            this.submitting = true;
            this.submitMsg  = 'Creando campaña...';

            if (!this.uploadDone) {
                this.submitMsg = 'Finalizando subida del archivo...';
                await new Promise(resolve => { this._uploadResolve = resolve; });
            }

            if (!this.tempPath) {
                this.submitting = false;
                this.submitMsg  = 'Crear Campaña';
                toastr.error('No se pudo subir el archivo. Por favor intenta de nuevo.');
                return;
            }

            document.getElementById('campaign-form').submit();
        },
    };
}
</script>
@endpush
