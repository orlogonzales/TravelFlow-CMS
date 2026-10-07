<?php

namespace App\Http\Requests\Admin;

use App\Domains\User\Services\UserSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreRolRequest extends FormRequest
{
    /**
     * Determina si el usuario está facultado para crear roles.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('roles.crear') ?? false;
    }

    /**
     * Normalización previa del slug y texto.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && is_string($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->slug),
            ]);
        } elseif ($this->has('name') && is_string($this->name) && empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }

    /**
     * Reglas de validación para registro de nuevos roles.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:roles,name',
            ],
            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:roles,slug',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ];
    }

    /**
     * Validación posterior de la regla anti-escalada.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $permissions = $this->input('permissions');
            $actor = $this->user();

            if (! empty($permissions) && is_array($permissions) && $actor) {
                $securityService = app(UserSecurityService::class);
                if (! $securityService->canAssignPermissions($actor, $permissions)) {
                    $validator->errors()->add(
                        'permissions',
                        'Intento de escalada de privilegios denegado: no puede asignar a un rol permisos que usted no posee.'
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
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Ya existe un rol registrado con este nombre.',
            'slug.required' => 'El identificador único (slug) es obligatorio.',
            'slug.unique' => 'El slug ingresado ya se encuentra en uso.',
            'slug.alpha_dash' => 'El slug solo puede contener letras, números, guiones y guiones bajos.',
            'permissions.array' => 'El listado de permisos debe ser un arreglo.',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no son válidos.',
        ];
    }
}
