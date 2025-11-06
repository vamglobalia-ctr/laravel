<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');





Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum', 'role:superAdmin'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/roles', [RoleController::class, 'store']) ->middleware('permission:create roles');
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/assign-role', [RoleController::class, 'assignRoleToUser']);
    Route::post('/assign-permissions', [RoleController::class, 'assignPermissionsToRole']);

});

Route::middleware(['auth:sanctum', 'permission:view dashboard'])
    ->get('/dashboard', [DashboardController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:insert dashboard'])
    ->post('/dashboard', [DashboardController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:edit dashboard'])
    ->put('/dashboard/{id}', [DashboardController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:delete dashboard'])
    ->delete('/dashboard/{id}', [DashboardController::class, 'destroy']);



Route::get('/check-permission/{userId}/', [RoleController::class, 'getUserPermissions']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getPermission', [RoleController::class, 'getAllPermissions']);
});
