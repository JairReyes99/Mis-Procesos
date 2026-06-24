<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'send_type_id'            => ['required', 'integer', 'exists:campaign_send_types,id'],
            'scheduled_at'            => ['required_if:send_type_slug,scheduled', 'nullable', 'date', 'after:now'],
            'no_send_rules'           => ['nullable', 'array'],
            'no_send_rules.*.from'    => ['required_with:no_send_rules.*', 'date_format:H:i'],
            'no_send_rules.*.to'      => ['required_with:no_send_rules.*', 'date_format:H:i'],
            'notification_email'      => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                  => 'El nombre de la campaña es obligatorio.',
            'name.string'                    => 'El nombre de la campaña debe ser texto.',
            'name.max'                       => 'El nombre de la campaña no puede superar 255 caracteres.',
            'send_type_id.required'          => 'El tipo de envío es obligatorio.',
            'send_type_id.integer'           => 'El tipo de envío debe ser un valor válido.',
            'send_type_id.exists'            => 'El tipo de envío seleccionado no existe.',
            'scheduled_at.required_if'       => 'La fecha de inicio es obligatoria para envíos programados.',
            'scheduled_at.date'              => 'La fecha de inicio debe ser una fecha válida.',
            'scheduled_at.after'             => 'La fecha de inicio debe ser en el futuro.',
            'no_send_rules.array'            => 'Las reglas de no envío deben ser un arreglo.',
            'no_send_rules.*.from.required_with' => 'La hora de inicio de la regla es obligatoria.',
            'no_send_rules.*.from.date_format'   => 'El formato de hora debe ser HH:MM.',
            'no_send_rules.*.to.required_with'   => 'La hora de fin de la regla es obligatoria.',
            'no_send_rules.*.to.date_format'     => 'El formato de hora debe ser HH:MM.',
            'notification_email.email'           => 'El correo de notificación debe ser una dirección válida.',
            'notification_email.max'             => 'El correo de notificación no puede superar 255 caracteres.',
        ];
    }
}
