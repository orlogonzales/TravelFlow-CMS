<?php

namespace App\Domains\User\Models;

use App\Domains\User\Contracts\ActorInterface;
use App\Domains\User\Enums\ActorType;
use App\Domains\User\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * Entidad de Cuenta de Usuario Autenticable.
 *
 * Gestiona el acceso al sistema, credenciales seguras, roles asignados
 * y vínculo opcional con la entidad Persona.
 */
class User extends Authenticatable implements ActorInterface
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'persona_id',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts nativos de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Identidad biográfica humana asociada (opcional).
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /**
     * Roles asignados al usuario (N:M).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * Asigna un rol al usuario.
     */
    public function assignRole(Role|string $role): void
    {
        $roleModel = is_string($role)
            ? Role::where('slug', $role)->firstOrFail()
            : $role;

        $this->roles()->syncWithoutDetaching([$roleModel->id]);
        $this->unsetRelation('roles');
    }

    /**
     * Remueve un rol del usuario.
     */
    public function removeRole(Role|string $role): void
    {
        $roleModel = is_string($role)
            ? Role::where('slug', $role)->first()
            : $role;

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
            $this->unsetRelation('roles');
        }
    }

    /**
     * Comprueba si el usuario tiene un rol determinado.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('slug', $roleSlug);
    }

    /**
     * Obtiene la colección completa de permisos consolidados a través de sus roles.
     *
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        return $this->roles->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id');
    }

    /**
     * Comprueba si el usuario posee un permiso a través de alguno de sus roles.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // El rol 'admin' posee todas las facultades
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->allPermissions()->contains('slug', $permissionSlug);
    }

    /**
     * Comprueba si la cuenta está activa.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Comprueba si la cuenta está bloqueada.
     */
    public function isBlocked(): bool
    {
        return $this->status === UserStatus::BLOCKED;
    }

    // --- Implementación de ActorInterface ---

    public function getActorType(): ActorType
    {
        return ActorType::HUMAN_USER;
    }

    public function getActorId(): ?string
    {
        return (string) $this->id;
    }

    public function getActorName(): string
    {
        return $this->persona ? $this->persona->nombre_completo : $this->name;
    }
}
