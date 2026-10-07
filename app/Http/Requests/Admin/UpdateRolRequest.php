<?php

namespace App\Http\Requests\Admin;

use App\Domains\User\Models\Permission;
use App\Domains\User\Models\Role;
use App\Domains\User\Services\UserSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRolRequest extends FormRequest
{
    /**
     * Determina si el usuario está facultado para editar roles.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('roles.editar') ?? false;
    }

    /**
     * Normalización previa del slug si se envía.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && is_string($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->slug),
            ]);
        }
    }

    /**
     * Reglas de validación para actualización de roles.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roleId = $this->route('role') instanceof Role
            ? $this->route('role')->id
            : $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles', 'slug')->ignore($roleId),
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
     * Validación posterior de integridad de sistema y regla anti-escalada.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            /** @var Role|null $role */
            $role = $this->route('role');
            if (is_numeric($role)) {
                $role = Role::find($role);
            }

            if (! $role) {
                return;
            }

            // 1. Si es rol de sistema, no se permite modificar el slug
            if ($role->is_system && $this->has('slug') && $this->input('slug') !== $role->slug) {
                $validator->errors()->add(
                    'slug',
                    'El identificador técnico (slug) de un rol del sistema no puede ser modificado.'
                );
            }

            // 2. Si el rol es el administrador de sistema ('admin'), garantizar permisos vitales
            if ($role->slug === 'admin' && $this->has('permissions')) {
                $criticalSlugs = ['admin.acceder', 'usuarios.roles', 'roles.editar', 'roles.ver'];
                $sentPermissionIds = $this->input('permissions', []);

                $missingSlugs = Permission::whereIn('slug', $criticalSlugs)
                    ->whereNotIn('id', $sentPermissionIds)
                    ->pluck('slug')
                    ->all();

                if (! empty($missingSlugs)) {
                    $validator->errors()->add(
                        'permissions',
                        'No es posible remover permisos estructurales del rol Administrador: ' . implode(', ', $missingSlugs) . '.'
                    );
                }
            }

            // 3. Regla anti-escalada: permissions(given) ⊆ permissions(actor)
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
            'name.unique' => 'Ya existe otro rol registrado con este nombre.',
            'slug.unique' => 'El slug ingresado ya se encuentra en uso por otro rol.',
            'slug.alpha_dash' => 'El slug solo puede contener letras, números, guiones y guiones bajos.',
            'permissions.array' => 'El listado de permisos debe ser un arreglo.',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no son válidos.',
        ];
    }
}
