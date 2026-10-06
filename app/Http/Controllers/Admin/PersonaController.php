<?php

namespace App\Http\Controllers\Admin;

use App\Domains\User\Models\Persona;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePersonaRequest;
use App\Http\Requests\Admin\UpdatePersonaRequest;
use App\Http\Resources\Admin\PersonaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonaController extends Controller
{
    /**
     * Columnas permitidas para ordenamiento seguro (Whitelist).
     * Previene cualquier inyección de SQL por interpolación de parámetros.
     */
    protected const ALLOWED_SORTS = [
        'id',
        'nombres',
        'apellidos',
        'numero_documento',
        'email',
        'estado',
        'created_at',
    ];

    /**
     * Listado paginado y filtrable de personas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Persona::query()->withExists('user');

        // Búsqueda multicampo razonable
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        // Ordenamiento seguro mediante whitelist
        $sort = (string) $request->input('sort', 'id');
        $direction = strtolower((string) $request->input('direction', 'desc'));

        if (!in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'id';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        // Paginación con límite estricto en servidor
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $personas = $query->paginate($perPage);

        return PersonaResource::collection($personas);
    }

    /**
     * Registra una nueva entidad de Persona humana.
     * REGLA VINCULANTE: Persona != User. Esta operación crea exclusivamente la identidad biográfica.
     */
    public function store(StorePersonaRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['estado'])) {
            $data['estado'] = 'activo';
        }

        $persona = Persona::create($data);

        return (new PersonaResource($persona->loadExists('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consulta los detalles de una Persona específica.
     */
    public function show(Request $request, Persona $persona): PersonaResource
    {
        return new PersonaResource($persona->loadExists('user'));
    }

    /**
     * Actualiza los datos o el estado de una Persona.
     * REGLA VINCULANTE: No altera ni propaga mutaciones involuntarias hacia cuentas User.
     */
    public function update(UpdatePersonaRequest $request, Persona $persona): PersonaResource
    {
        $persona->update($request->validated());

        return new PersonaResource($persona->loadExists('user'));
    }
}
