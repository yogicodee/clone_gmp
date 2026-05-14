<?php

namespace App\Http\Controllers\Api\UserManagement\Permission;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::query()
            ->orderBy('group_name')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'group_name', 'description']);

        $grouped = $permissions
            ->groupBy('group_name')
            ->map(fn ($items, $groupName) => [
                'group_name' => $groupName,
                'permissions' => $items->map(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'code' => $permission->code,
                    'name' => $permission->name,
                    'description' => $permission->description,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'message' => 'Daftar permission berhasil diambil.',
            'data' => $grouped,
        ]);
    }
}
