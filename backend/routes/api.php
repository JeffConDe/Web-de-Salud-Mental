<?php

use App\Http\Controllers\Api\AdminDoctorController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SpecialtyController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'salud-mental-api',
    ]);
});

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/chat', [ChatController::class, 'index']);
    Route::post('/chat/messages', [ChatController::class, 'store'])->middleware('throttle:30,1');

    Route::middleware('role:administrator,receptionist,doctor')->group(function () {
        Route::get('/specialties', [SpecialtyController::class, 'index']);
        Route::get('/doctors', [DoctorController::class, 'index']);
        Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);
        Route::get('/doctors/{doctor}/schedules', [DoctorScheduleController::class, 'index']);
    });

    Route::middleware('role:administrator,receptionist')->group(function () {
        Route::apiResource('patients', PatientController::class);
    });

    Route::middleware('role:administrator')->prefix('admin')->group(function () {
        Route::post('/specialties', [SpecialtyController::class, 'store']);
        Route::put('/specialties/{specialty}', [SpecialtyController::class, 'update']);
        Route::delete('/specialties/{specialty}', [SpecialtyController::class, 'destroy']);
        Route::post('/doctors', [AdminDoctorController::class, 'store']);
        Route::put('/doctors/{doctor}', [AdminDoctorController::class, 'update']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    });

    Route::middleware('role:administrator,doctor')->group(function () {
        Route::post('/doctors/{doctor}/schedules', [DoctorScheduleController::class, 'store']);
        Route::put('/doctors/{doctor}/schedules/{schedule}', [DoctorScheduleController::class, 'update']);
        Route::delete('/doctors/{doctor}/schedules/{schedule}', [DoctorScheduleController::class, 'destroy']);
    });
});
