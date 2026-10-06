<?php

namespace App\Http\Requests\Admin;

use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use App\Domains\User\Services\UserSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUsuarioRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.roles') ?? false;
    }

    public function rules(): array
    {
        return [
            'roles' => [
                'required',
                'array',
            ],
            'roles.*' => [
                'integer',
                'exists:roles,id',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $actor = $this->user();
            /** @var User|null $targetUser */
            $targetUser = $this->route('usuario');

            if (! $actor || ! $targetUser) {
                return;
            }

            $newRoleIds = $this->input('roles', []);
            $securityService = app(UserSecurityService::class);

            // 1. Regla Anti-Escalada: permissions(role) ⊆ permissions(actor)
            if (! $securityService->canAssignRoles($actor, $newRoleIds)) {
                $validator->errors()->add(
                    'roles',
                    'Intento de escalada de privilegios denegado: no puede asignar roles que contienen permisos superiores a sus propios privilegios.'
                );
                return;
            }

            // 2. Comprobar si los nuevos roles conservan capacidad administrativa crítica
            $proposedRoles = Role::with('permissions')->whereIn('id', $newRoleIds)->get();
            $retainsAdminAcceder = $proposedRoles->flatMap->permissions->contains('slug', 'admin.acceder');
            $retainsUsuariosRoles = $proposedRoles->flatMap->permissions->contains('slug', 'usuarios.roles');

            // 3. Prevención de Self-Lockout (Auto-Bloqueo de capacidades)
            if ($actor->id === $targetUser->id && (! $retainsAdminAcceder || ! $retainsUsuariosRoles)) {
                $validator->errors()->add(
                    'roles',
                    'Protección de auto-bloqueo: no puede retirar de su propia cuenta los roles que le confieren acceso y gestión administrativa.'
                );
                return;
            }

            // 4. Protección de Último Administrador Efectivo
            if ((! $retainsAdminAcceder || ! $retainsUsuariosRoles) && $securityService->wouldLeaveZeroEffectiveAdmins($targetUser)) {
                $validator->errors()->add(
                    'roles',
                    'Operación denegada: no es posible despojar de facultades administrativas a la última cuenta administradora efectiva activa del sistema.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Debe especificar al menos un rol para el usuario.',
            'roles.array' => 'Los roles deben proporcionarse como un arreglo.',
            'roles.*.exists' => 'Uno o más roles seleccionados no existen en el sistema.',
        ];
    }
}
