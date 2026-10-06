<?php

namespace App\Http\Requests\Admin;

use App\Domains\User\Enums\PersonaStatus;
use App\Domains\User\Services\UserSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para crear cuentas.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.crear') ?? false;
    }

    /**
     * Normalización previa de datos de entrada.
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
     * Reglas de validación para registro de cuenta User.
     *
     * REGLAS VINCULANTES:
     * - Persona obligatoria, activa y sin cuenta User previa (0..1 <-> 0..1).
     * - Email único y normalizado.
     * - Contraseña mínima de 12 caracteres con confirmación.
     * - Asignación de roles sujeta a permiso 'usuarios.roles' y regla anti-escalada.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'persona_id' => [
                'required',
                'integer',
                Rule::exists('personas', 'id')->where(function ($query) {
                    $query->where('estado', PersonaStatus::ACTIVO->value);
                }),
                'unique:users,persona_id',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['active', 'inactive', 'blocked']),
            ],
            'roles' => [
                'nullable',
                'array',
            ],
            'roles.*' => [
                'integer',
                'exists:roles,id',
            ],
        ];
    }

    /**
     * Validación posterior para segregación de roles y regla anti-escalada.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $roles = $this->input('roles');
            $actor = $this->user();

            if (! empty($roles) && is_array($roles) && $actor) {
                // 1. Verificar si el actor posee el permiso 'usuarios.roles'
                if (! $actor->hasPermission('usuarios.roles')) {
                    $validator->errors()->add(
                        'roles',
                        'No posee el permiso usuarios.roles requerido para asignar roles durante la creación de cuentas.'
                    );
                    return;
                }

                // 2. Aplicar regla anti-escalada: permissions(role) ⊆ permissions(actor)
                $securityService = app(UserSecurityService::class);
                if (! $securityService->canAssignRoles($actor, $roles)) {
                    $validator->errors()->add(
                        'roles',
                        'Intento de escalada de privilegios denegado: no puede asignar roles que contienen permisos superiores a sus propios privilegios.'
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error en español formal.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'persona_id.required' => 'La selección de una persona física activa es obligatoria.',
            'persona_id.exists' => 'La persona seleccionada no existe o no se encuentra en estado activo.',
            'persona_id.unique' => 'La persona seleccionada ya cuenta con una cuenta de usuario vinculada (cardinalidad 0..1 <-> 0..1).',
            'email.required' => 'El correo electrónico de acceso es obligatorio.',
            'email.email' => 'El correo electrónico ingresado no tiene un formato válido.',
            'email.unique' => 'El correo electrónico ya se encuentra registrado en el sistema.',
            'password.required' => 'La contraseña de acceso es obligatoria.',
            'password.min' => 'La contraseña debe contener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
