<?php

namespace App\Http\Controllers\Admin;

use App\Domains\User\Models\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    /**
     * Retorna el catálogo completo de permisos agrupados por dominio funcional.
     */
    public function index(Request $request): JsonResponse
    {
        $permissions = Permission::query()
            ->orderBy('domain')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'domain', 'description']);

        $grouped = $permissions->groupBy('domain');

        return response()->json([
            'success' => true,
            'data' => $permissions,
            'grouped' => $grouped,
        ]);
    }
}
