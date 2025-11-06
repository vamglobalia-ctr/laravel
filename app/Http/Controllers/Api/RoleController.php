<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'roles' => Role::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        $role = Role::create(['name' => $validated['name']]);

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully',
            'role' => $role
        ], 201);
    }

    public function assignRoleToUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::find($validated['user_id']);
        $user->assignRole($validated['role']);

        return response()->json([
            'status' => true,
            'message' => 'Role assigned successfully'
        ]);
    }

   
    public function assignPermissionsToRole(Request $request)
    {
        try {
       
            $validated = $request->validate([
                'role' => 'required|string|exists:roles,name',
                'permissions' => 'required|array',
                'permissions.*' => 'string'
            ]);
    
           
            $role = Role::where('name', $validated['role'])->where('guard_name', 'web')->first();
            if (!$role) {
                throw new RoleDoesNotExist("Role {$validated['role']} does not exist for web guard");
            }
    
          
            $permissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', 'web')
                ->get();
    
            if ($permissions->count() !== count($validated['permissions'])) {
                throw new PermissionDoesNotExist("One or more permissions do not exist for web guard");
            }
    
           
            $role->syncPermissions($permissions);
    
            return response()->json([
                'status' => true,
                'message' => "Permissions assigned to role successfully",
                'role' => $role->load('permissions')
            ], 200);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
    
        } catch (RoleDoesNotExist | PermissionDoesNotExist $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getUserPermissions($userId)
{
    $user = Role::find($userId);
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found'
        ], 404);
    }

    return response()->json([
        'status' => true,
        'user' => $user->only('id','name','guard_name'),
        'permissions' => $user->getAllPermissions()->pluck('name')
    ], 200);
}


public function getAllPermissions(Request $request)
{
    $user = $request->user();

 
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 401);
    }


    if ($user->hasRole('superAdmin')) {
        $permissions = Permission::select('name')->get();
    } else {
       
        $permissions = Permission::select('name')
            ->where('name', '!=', 'create roles')
            ->get();
    }

    return response()->json([
        'status' => true,
        'permissions' => $permissions
    ], 200);
}


}