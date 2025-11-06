<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
           
            $requestUser = $request->user();
            if (!$requestUser || !$requestUser->hasRole('superAdmin')) {
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

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
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
}
