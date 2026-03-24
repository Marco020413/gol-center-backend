<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\EquipoController;

// Rutas Públicas (El JS las usa para el select)
Route::get('/equipos', [EquipoController::class, 'listar']);
Route::get('/jugadores', [JugadorController::class, 'listarTodos']);
Route::delete('/admin/jugadores/eliminar/{telefono}', [App\Http\Controllers\JugadorController::class, 'eliminar']);
Route::put('/admin/jugadores/actualizar/{telefono}', [App\Http\Controllers\JugadorController::class, 'actualizar']);
Route::get('/equipos/escudos', [App\Http\Controllers\EquipoController::class, 'listarEscudos']);
Route::delete('/admin/equipos/eliminar/{id}', [App\Http\Controllers\EquipoController::class, 'eliminar']);

// Rutas de Administración
Route::prefix('admin')->group(function () {
    Route::post('/jugadores/registrar', [JugadorController::class, 'registrar']);
    Route::post('/equipos/registrar', [EquipoController::class, 'registrar']);
});