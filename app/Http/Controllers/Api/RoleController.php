<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:roles,name',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string'
            ]);
    
            
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web'
            ]);
    
            
            if (!empty($validated['permissions'])) {
                $permissions = Permission::whereIn('name', $validated['permissions'])
                    ->where('guard_name', 'web')
                    ->get();
    
                if ($permissions->count() !== count($validated['permissions'])) {
                    throw new PermissionDoesNotExist("One or more permissions do not exist for web guard");
                }
    
                $role->syncPermissions($permissions);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Role created successfully',
                'role' => $role->load('permissions')
            ], 201);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
    
        } catch (PermissionDoesNotExist $e) {
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

   
 
    public function getRolePermissions($roleId)
    {
        $role = Role::where('id', $roleId)->where('guard_name', 'web')->first();
    
        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'role' => $role->only('id', 'name', 'guard_name'),
            'permissions' => $role->permissions->pluck('name')
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


    if ($user->roles->contains('id' , 1)) {
     
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
public function update(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        $role = Role::findOrFail($id);
        if (isset($validated['name'])) {
            $role->update(['name' => $validated['name']]);
        }

        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', 'web')
                ->get();

            $role->syncPermissions($permissions);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully',
            'role' => $role->load('permissions')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Update failed',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function destroy($id)
{
    $role = Role::findOrFail($id);

    DB::table('model_has_roles')->where('role_id', $role->id)->delete();
    DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
    $role->delete();

    return response()->json([
        'status' => true,
        'message' => 'Role deleted Successfully'
    ]);
}

public function getAllRoles(){
    $roles = Role::all();
    return response()->json([
        'status' => true,
        'roles' => $roles
    ]);
}

}