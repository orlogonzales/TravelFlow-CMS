<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Solicitud de Actualización de Preferencias Visuales de Usuario.
 *
 * Exclusivamente permite opciones de presentación visual validadas.
 * Queda estrictamente prohibida la inyección de datos de identidad, roles o seguridad.
 */
class UpdateUiPreferencesRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     * Cualquier usuario autenticado puede actualizar sus propias preferencias.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación para preferencias de interfaz.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'string', Rule::in(['light', 'dark', 'system'])],
            'semi_dark' => ['sometimes', 'boolean'],
            'sidebar_collapsed' => ['sometimes', 'boolean'],
            'content_layout' => ['sometimes', 'string', Rule::in(['compact', 'wide'])],
            'navbar_type' => ['sometimes', 'string', Rule::in(['sticky', 'static'])],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'theme.in' => 'El tema debe ser uno de los siguientes: light, dark o system.',
            'content_layout.in' => 'El layout de contenido debe ser compact o wide.',
            'navbar_type.in' => 'El tipo de navbar debe ser sticky o static.',
        ];
    }
}
