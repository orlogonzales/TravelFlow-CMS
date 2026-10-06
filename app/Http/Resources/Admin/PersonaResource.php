<?php

namespace App\Http\Resources\Admin;

use App\Domains\User\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Persona
 */
class PersonaResource extends JsonResource
{
    /**
     * Transforma la entidad Persona a representación JSON explícita.
     * Cero exposición indiscriminada de campos internos o relaciones no deseadas.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasUser = isset($this->user_exists)
            ? (bool) $this->user_exists
            : ($this->relationLoaded('user') ? $this->user !== null : false);

        return [
            'id' => $this->id,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => $this->nombre_completo,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'estado' => $this->estado instanceof \BackedEnum ? $this->estado->value : (string) $this->estado,
            'has_user' => $hasUser,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
