<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Entidad de Rol en RBAC de TravelFlow CMS.
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * Usuarios que poseen este rol asignado.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Permisos asignados a este rol.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * Asigna un permiso al rol (por modelo o slug).
     */
    public function givePermission(Permission|string $permission): void
    {
        $perm = is_string($permission)
            ? Permission::where('slug', $permission)->firstOrFail()
            : $permission;

        $this->permissions()->syncWithoutDetaching([$perm->id]);
    }

    /**
     * Revoca un permiso del rol.
     */
    public function revokePermission(Permission|string $permission): void
    {
        $perm = is_string($permission)
            ? Permission::where('slug', $permission)->first()
            : $permission;

        if ($perm) {
            $this->permissions()->detach($perm->id);
        }
    }

    /**
     * Comprueba si el rol tiene un permiso asignado.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions->contains('slug', $permissionSlug);
    }
}
