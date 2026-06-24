<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'rfc'                   => ['nullable', 'string', 'max:13'],
            'email'                 => ['nullable', 'email', 'max:255'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'status_id'             => ['required', 'integer', 'in:1,2'],
            'sms_price_per_segment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre de la empresa es obligatorio.',
            'name.max'           => 'El nombre no puede superar 255 caracteres.',
            'rfc.max'            => 'El RFC no puede superar 13 caracteres.',
            'email.email'        => 'Ingresa un correo electrónico válido.',
            'email.max'          => 'El correo no puede superar 255 caracteres.',
            'phone.max'          => 'El teléfono no puede superar 20 caracteres.',
            'status_id.required' => 'El estatus es obligatorio.',
            'status_id.in'       => 'El estatus debe ser Activo o Inactivo.',
        ];
    }
}
