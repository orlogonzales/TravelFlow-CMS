<?php

namespace App\Domains\User\Models;

use App\Domains\User\Enums\PersonaStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Entidad de Identidad Humana (Persona).
 *
 * Representa la identidad real de un individuo (guía, redactor, administrador, chofer, etc.).
 * No contiene contraseñas ni credenciales de acceso técnico.
 */
class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';

    protected $fillable = [
        'nombres',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'email',
        'telefono',
        'estado',
    ];

    /**
     * Casts nativos de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => PersonaStatus::class,
        ];
    }

    /**
     * Retorna el nombre completo concatenado.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    /**
     * Cuenta de usuario opcional asociada a esta persona.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'persona_id');
    }
}
