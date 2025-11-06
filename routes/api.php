<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




// login
Route::post('/login', [AuthController::class, 'login'])->name('login');
// roles 
Route::middleware(['auth:sanctum', 'role:superAdmin'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:create roles');
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/assign-role', [RoleController::class, 'assignRoleToUser']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
});

// role-permision-check
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getPermission', [RoleController::class, 'getAllPermissions']);
    Route::put('/update-roles-permission/{id}', [RoleController::class, 'update']);
    Route::get('/check-permission/{roleId}/', [RoleController::class, 'getRolePermissions']);
    Route::get('/check-role',[RoleController::class,'getAllRoles']);
});

// excel import and data fetch
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/invoices/import', [InvoiceController::class, 'import']);
    Route::get('/getExcelData', [InvoiceController::class, 'getExcelData']);
});


