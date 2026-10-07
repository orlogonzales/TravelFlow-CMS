<?php

namespace App\Http\Controllers\Admin;

use App\Domains\User\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRolRequest;
use App\Http\Requests\Admin\UpdateRolRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    /**
     * Listado paginado y filtrable de roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::query()
            ->withCount(['users', 'permissions'])
            ->with('permissions:id,name,slug,domain');

        // Búsqueda por término (nombre o slug)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordenamiento seguro
        $allowedSorts = ['id', 'name', 'slug', 'is_system', 'users_count', 'permissions_count', 'created_at'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'name';
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $direction);

        // Paginación
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);
        $roles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'from' => $roles->firstItem(),
                'to' => $roles->lastItem(),
            ],
        ]);
    }

    /**
     * Ficha de detalle de un rol específico.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load([
            'permissions:id,name,slug,domain,description',
            'users' => function ($q) {
                $q->select('users.id', 'users.persona_id', 'users.name', 'users.email', 'users.status')
                    ->with('persona:id,nombres,apellidos,tipo_documento,numero_documento');
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'permissions' => $role->permissions,
                'users' => $role->users,
                'users_count' => $role->users->count(),
                'permissions_count' => $role->permissions->count(),
                'created_at' => $role->created_at?->toISOString(),
                'updated_at' => $role->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Registra un nuevo rol con su matriz inicial de permisos.
     */
    public function store(StoreRolRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = DB::transaction(function () use ($data) {
            $newRole = Role::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'is_system' => false,
            ]);

            if (! empty($data['permissions'])) {
                $newRole->permissions()->sync($data['permissions']);
            }

            return $newRole;
        });

        $role->load(['permissions:id,name,slug,domain']);

        return response()->json([
            'success' => true,
            'message' => 'Rol registrado exitosamente.',
            'data' => $role,
        ], 201);
    }

    /**
     * Modifica los datos y matriz de permisos de un rol existente.
     */
    public function update(UpdateRolRequest $request, Role $role): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($role, $data) {
            $fillable = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ];

            // Solo actualizar slug si no es de sistema
            if (! $role->is_system && isset($data['slug'])) {
                $fillable['slug'] = $data['slug'];
            }

            $role->update($fillable);

            if (array_key_exists('permissions', $data)) {
                $role->permissions()->sync($data['permissions'] ?? []);
            }
        });

        $role->load(['permissions:id,name,slug,domain']);

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente.',
            'data' => $role,
        ]);
    }

    /**
     * Elimina un rol del sistema sujeto a reglas de integridad.
     */
    public function destroy(Role $role): JsonResponse
    {
        // 1. Prohibir eliminación de roles de sistema
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Los roles del sistema son estructurales y no pueden ser eliminados.',
            ], 422);
        }

        // 2. Prohibir eliminación si tiene usuarios vinculados
        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No es posible eliminar el rol porque tiene usuarios asignados. Reasigne los usuarios antes de proceder.',
            ], 422);
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "El rol '{$role->name}' ha sido eliminado exitosamente.",
        ]);
    }
}
