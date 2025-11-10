<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
           
            $requestUser = $request->user();
            if (!$requestUser || !$requestUser->roles->contains('id' , 1)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only SuperAdmin can register users with roles'
                ], 403);
            }
    
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed|min:6',
                'role_id' => 'required|integer|exists:roles,id',
            ]);
    
            
            $role = Role::find($validated['role_id']);
            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Role not found'
                ], 400);
            }
    
           
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id
            ]);
    
            
            $newUser->assignRole($role->name);
    
          
            $token = $newUser->createToken('myapptoken')->plainTextToken;
    
            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $newUser->load('roles'),
                    'token' => $token
                ]
            ], 201);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
    
        } catch (QueryException $e) {
            Log::error('DB Error during register: '.$e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Database error while creating user',
                'error' => $e->getMessage()
            ], 500);
    
        } catch (Exception $e) {
            Log::error('Register Error: '.$e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('myapptoken')->plainTextToken;
            $roles = $user->getRoleNames(); 
            $permissions = $user->getAllPermissions()->pluck('name');
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user->only('id','name'),
                    'token' => $token,
                    'roles' => $roles,
                    'permissions' => $permissions
                ]
            ], 200);

        } catch (QueryException $e) {
            Log::error('DB Error during login: '.$e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Database error during login',
                'error' => $e->getMessage()
            ], 500);

        } catch (Exception $e) {
            Log::error('Login Error: '.$e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getAllUsers()
    {
        try {
            $users = User::join('roles', 'users.role_id', '=', 'roles.id')
            ->whereNull('roles.deleted_at') 
                ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name' , 'roles.id as role_id')
                ->get();
    
            return response()->json([
                'status' => true,
                'data' => $users,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error occurred',
            ], 500);
        }
    }
    
    public function editUser($id){
        try{
            $users = User::findOrFail($id);
            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        }catch(Exception $e){
            Log::error('Error edit Fetching user: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    public function updateUser(Request $request , $id){
        $users = User::findOrFail($id);

        try{
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,'.$users->id,
                'password' => 'sometimes|min:6',
                'role_id' =>'sometimes|integer|exists:roles,id'
            ]);
            if(isset($validated['name'])){
                $users->name = $validated['name'];
            }
            if(isset($validated['email'])){
                $users->email = $validated['email'];
            }
            if(isset($validated['password'])){
                $users->password = Hash::make($validated['password']);
            }
            if(isset($validated['role_id'])){
                $users->role_id = $validated['role_id'];
            }

            $users->save();

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully',
                'data' => $users
            ], 200);
    }catch(Exception $e){
        Log::error('Error updating user: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
    }

    public function Userdestroy($id)
{
    try {
        $users = User::findOrFail($id);
        $users->delete(); 

        return response()->json([
            'status'  => true,
            'message' => 'User deleted successfully'
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status'  => false,
            'message' => 'User not found'
        ], 404);
    } catch (\Exception $e) {
        Log::error('User delete failed | Error: ' . $e->getMessage());
        return response()->json([
            'status'  => false,
            'message' => 'An error occurred while deleting the User'
        ], 500);
    }
}
}
