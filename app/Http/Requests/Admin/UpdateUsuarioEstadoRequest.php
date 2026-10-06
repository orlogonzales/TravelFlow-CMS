<?php

namespace App\Http\Requests\Admin;

use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\User;
use App\Domains\User\Services\UserSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUsuarioEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.estado') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive', 'blocked']),
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

            $newStatus = $this->input('status');

            if ($newStatus !== UserStatus::ACTIVE->value) {
                // 1. Prevención de Self-Lockout (Auto-Bloqueo)
                if ($actor->id === $targetUser->id) {
                    $validator->errors()->add(
                        'status',
                        'Protección de auto-bloqueo: no puede inactivar ni bloquear su propia cuenta de acceso.'
                    );
                    return;
                }

                // 2. Protección de Último Administrador Efectivo
                $securityService = app(UserSecurityService::class);
                if ($securityService->wouldLeaveZeroEffectiveAdmins($targetUser)) {
                    $validator->errors()->add(
                        'status',
                        'Operación denegada: no es posible inactivar ni bloquear a la última cuenta administradora efectiva activa del sistema.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado de la cuenta es obligatorio.',
            'status.in' => 'El estado debe ser active, inactive o blocked.',
        ];
    }
}
