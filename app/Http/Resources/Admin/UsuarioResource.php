<?php

namespace App\Http\Resources\Admin;

use App\Domains\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UsuarioResource extends JsonResource
{
    /**
     * Transforma la entidad User a representación JSON explícita.
     * Cero exposición de hashes, remember_token, cookies o secretos técnicos.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $humanName = $this->relationLoaded('persona') && $this->persona
            ? $this->persona->nombre_completo
            : $this->name;

        return [
            'id' => $this->id,
            'name' => $humanName,
            'email' => $this->email,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'persona_id' => $this->persona_id,
            'persona' => $this->whenLoaded('persona', function () {
                if (! $this->persona) {
                    return null;
                }
                return [
                    'id' => $this->persona->id,
                    'nombres' => $this->persona->nombres,
                    'apellidos' => $this->persona->apellidos,
                    'nombre_completo' => $this->persona->nombre_completo,
                    'tipo_documento' => $this->persona->tipo_documento,
                    'numero_documento' => $this->persona->numero_documento,
                    'email' => $this->persona->email,
                    'telefono' => $this->persona->telefono,
                    'estado' => $this->persona->estado instanceof \BackedEnum ? $this->persona->estado->value : (string) $this->persona->estado,
                ];
            }),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                    'is_system' => (bool) $r->is_system,
                ])->all();
            }),
            // Resumen de permisos efectivos solo para vistas de detalle individual cuando se soliciten
            'permissions' => $this->when($request->routeIs('*show*') || $request->has('include_permissions'), function () {
                return $this->allPermissions()->pluck('slug')->values()->all();
            }),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
