<?php

namespace App\Http\Requests\Admin;

use App\Domains\User\Enums\PersonaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para modificar datos base de cuentas.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.editar') ?? false;
    }

    /**
     * Normalización de correo a minúsculas.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->email)) {
            $this->merge([
                'email' => Str::lower(trim($this->email)),
            ]);
        }
    }

    /**
     * Reglas de validación para modificación de datos base.
     * REGLA VINCULANTE: Únicamente el correo electrónico de acceso es modificable en datos base.
     * La persona vinculada es inmutable en edición normal y users.name se deriva de la persona.
     * NO procesa contraseñas, roles ni estado (segregación estricta de privilegios).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('usuario') instanceof \App\Domains\User\Models\User
            ? $this->route('usuario')->id
            : $this->route('usuario');

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico de acceso es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'El correo electrónico ya se encuentra registrado por otra cuenta.',
        ];
    }
}
