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

// Rutas para Partidos
Route::get('/partidos', [App\Http\Controllers\PartidoController::class, 'listar']);
Route::post('/admin/partidos/crear', [App\Http\Controllers\PartidoController::class, 'crear']);
Route::put('/admin/partidos/actualizar/{id}', [App\Http\Controllers\PartidoController::class, 'actualizarMarcador']);
Route::delete('/admin/partidos/eliminar/{id}', [App\Http\Controllers\PartidoController::class, 'eliminar']);

// Rutas para Campos
Route::get('/campos', [App\Http\Controllers\CampoController::class, 'index']);
Route::post('/admin/campos/registrar', [App\Http\Controllers\CampoController::class, 'crear']);
Route::delete('/admin/campos/eliminar/{id}', [App\Http\Controllers\CampoController::class, 'eliminar']);
Route::put('/admin/campos/actualizar/{id}', [App\Http\Controllers\CampoController::class, 'actualizar']);

// Rutas de Administración
Route::prefix('admin')->group(function () {
    Route::post('/jugadores/registrar', [JugadorController::class, 'registrar']);
    Route::post('/equipos/registrar', [EquipoController::class, 'registrar']);
});