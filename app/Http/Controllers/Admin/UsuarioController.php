<?php

namespace App\Http\Controllers\Admin;

use App\Domains\User\Enums\PersonaStatus;
use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\Persona;
use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use App\Domains\User\Services\UserSecurityService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioEstadoRequest;
use App\Http\Requests\Admin\UpdateUsuarioPasswordRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRolesRequest;
use App\Http\Resources\Admin\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Columnas permitidas para ordenamiento seguro (Whitelist).
     */
    protected const ALLOWED_SORTS = [
        'id',
        'name',
        'email',
        'status',
        'last_login_at',
        'created_at',
    ];

    /**
     * Listado paginado y filtrable de cuentas de usuario.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()->with([
            'persona:id,nombres,apellidos,numero_documento,tipo_documento,email,telefono,estado',
            'roles:id,name,slug,is_system',
        ]);

        // Búsqueda multicampo en User y Persona vinculada
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhereHas('persona', function ($pq) use ($search) {
                        $pq->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%");
                    });
            });
        }

        // Ordenamiento seguro mediante whitelist
        $sort = (string) $request->input('sort', 'id');
        $direction = strtolower((string) $request->input('direction', 'desc'));

        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'id';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        // Paginación con límites seguros en servidor
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $usuarios = $query->paginate($perPage);

        return UsuarioResource::collection($usuarios);
    }

    /**
     * Consulta asíncrona de personas elegibles para vinculación de cuenta.
     * Criterio: Persona activa y sin ninguna cuenta User vinculada.
     */
    public function personasElegibles(Request $request): JsonResponse
    {
        $query = Persona::query()
            ->where('estado', PersonaStatus::ACTIVO)
            ->whereDoesntHave('user');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%");
            });
        }

        $personas = $query->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit(30)
            ->get(['id', 'nombres', 'apellidos', 'tipo_documento', 'numero_documento', 'email']);

        return response()->json([
            'data' => $personas->map(fn ($p) => [
                'id' => $p->id,
                'nombre_completo' => $p->nombre_completo,
                'tipo_documento' => $p->tipo_documento,
                'numero_documento' => $p->numero_documento,
                'email' => $p->email,
            ]),
        ]);
    }

    /**
     * Retorna los roles asignables por el usuario autenticado según la regla anti-escalada.
     */
    public function rolesDisponibles(Request $request, UserSecurityService $securityService): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $roles = $securityService->getAssignableRoles($actor);

        return response()->json([
            'data' => $roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'description' => $r->description,
            ]),
        ]);
    }

    /**
     * Registra una nueva cuenta de usuario vinculada obligatoriamente a una persona física.
     */
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $persona = Persona::findOrFail($data['persona_id']);

            $user = User::create([
                'persona_id' => $persona->id,
                'name' => $persona->nombre_completo,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? UserStatus::ACTIVE->value,
            ]);

            if (! empty($data['roles'])) {
                $user->roles()->sync($data['roles']);
            }

            return $user;
        });

        return (new UsuarioResource($user->load(['persona', 'roles'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consulta la ficha detallada de una cuenta de usuario.
     */
    public function show(Request $request, User $usuario): UsuarioResource
    {
        return new UsuarioResource($usuario->load(['persona', 'roles.permissions']));
    }

    /**
     * Actualiza la información base de una cuenta de usuario.
     * REGLAS VINCULANTES (FASE 1C.2A):
     * - Únicamente el correo electrónico de acceso es modificable en edición normal.
     * - Persona es la única fuente soberana de identidad: users.name se sincroniza desde Persona.
     * - La persona vinculada es inmutable en esta operación (cero reasignación libre de Persona).
     * - NO modifica roles, estados ni contraseñas.
     */
    public function update(UpdateUsuarioRequest $request, User $usuario): UsuarioResource
    {
        $data = $request->validated();

        DB::transaction(function () use ($usuario, $data) {
            $updatePayload = [
                'email' => $data['email'],
            ];

            // Sincronizar users.name desde la persona vinculada para garantizar coherencia
            if ($usuario->persona) {
                $updatePayload['name'] = $usuario->persona->nombre_completo;
            }

            $usuario->update($updatePayload);
        });

        return new UsuarioResource($usuario->load(['persona', 'roles']));
    }

    /**
     * Actualiza el estado de una cuenta de usuario (activar, inactivar, bloquear).
     * Si pasa a inactivo o bloqueado, revoca inmediatamente sus sesiones activas.
     */
    public function updateEstado(UpdateUsuarioEstadoRequest $request, User $usuario, UserSecurityService $securityService): UsuarioResource
    {
        $newStatus = $request->validated()['status'];

        DB::transaction(function () use ($usuario, $newStatus, $securityService) {
            $usuario->update(['status' => $newStatus]);

            if ($newStatus !== UserStatus::ACTIVE->value) {
                $securityService->revokeActiveSessions($usuario->id);
            }
        });

        return new UsuarioResource($usuario->load(['persona', 'roles']));
    }

    /**
     * Administra los roles asignados a una cuenta de usuario con validación anti-escalada.
     */
    public function updateRoles(UpdateUsuarioRolesRequest $request, User $usuario): UsuarioResource
    {
        $roleIds = $request->validated()['roles'];

        DB::transaction(function () use ($usuario, $roleIds) {
            $usuario->roles()->sync($roleIds);
        });

        return new UsuarioResource($usuario->load(['persona', 'roles']));
    }

    /**
     * Restablece la contraseña de una cuenta de usuario.
     * Revoca inmediatamente todas las sesiones existentes del usuario afectado.
     */
    public function updatePassword(UpdateUsuarioPasswordRequest $request, User $usuario, UserSecurityService $securityService): JsonResponse
    {
        $newPassword = $request->validated()['password'];

        DB::transaction(function () use ($usuario, $newPassword, $securityService) {
            $usuario->update([
                'password' => Hash::make($newPassword),
            ]);

            // Revocación física de sesiones en base de datos
            $securityService->revokeActiveSessions($usuario->id);
        });

        // Si el usuario que modificó la contraseña es el propio actor autenticado,
        // regenerar la sesión local para evitar un cierre accidental de su interacción actual
        if ($request->user()?->id === $usuario->id) {
            $request->session()->put('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', $usuario->id);
            $request->session()->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida correctamente. Se han revocado las sesiones activas del usuario.',
            'data' => new UsuarioResource($usuario->load(['persona', 'roles'])),
        ]);
    }
}
