<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUsuarioPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.password') ?? false;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La nueva contraseña de acceso es obligatoria.',
            'password.min' => 'La nueva contraseña debe contener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
