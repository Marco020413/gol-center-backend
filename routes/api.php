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
Route::delete('/admin/escudos/eliminar', [App\Http\Controllers\EquipoController::class, 'eliminarEscudoArchivo']);

// ENDPOINT ÚNICO PARA CLIENTE PÚBLICO - Todo en una llamada
Route::get('/publico', [App\Http\Controllers\PartidoController::class, 'datosPublicos']);

// Rutas para Partidos
Route::get('/partidos', [App\Http\Controllers\PartidoController::class, 'listar']);
Route::post('/admin/partidos/crear', [App\Http\Controllers\PartidoController::class, 'crear']);
Route::put('/admin/partidos/actualizar/{id}', [App\Http\Controllers\PartidoController::class, 'actualizarMarcador']);
Route::delete('/admin/partidos/eliminar/{id}', [App\Http\Controllers\PartidoController::class, 'eliminar']);
Route::delete('/admin/partidos/limpiar-todo', [App\Http\Controllers\PartidoController::class, 'limpiarTodo']);
Route::put('/admin/partidos/actualizar-datos/{id}', [App\Http\Controllers\PartidoController::class, 'actualizarDatosSorteo']);
Route::post('/admin/partidos/generar-liguilla', [App\Http\Controllers\PartidoController::class, 'generarTorneo']);
Route::post('/admin/guardar-podio', [App\Http\Controllers\PartidoController::class, 'guardarPodio']);

Route::get('/historial', function() {
    $database = app('firebase.database');
    return $database->getReference('historial_torneos')->getValue();
});

// Rutas para Campos
Route::get('/campos', [App\Http\Controllers\CampoController::class, 'index']);
Route::post('/admin/campos/registrar', [App\Http\Controllers\CampoController::class, 'crear']);
Route::delete('/admin/campos/eliminar/{id}', [App\Http\Controllers\CampoController::class, 'eliminar']);
Route::put('/admin/campos/actualizar/{id}', [App\Http\Controllers\CampoController::class, 'actualizar']);
Route::post('/admin/partidos/generar-torneo', [App\Http\Controllers\PartidoController::class, 'generarTorneo']);

// Rutas de Administración
Route::prefix('admin')->group(function () {
    Route::post('/jugadores/registrar', [JugadorController::class, 'registrar']);
    Route::post('/equipos/registrar', [EquipoController::class, 'registrar']);
    Route::put('/equipos/actualizar/{id}', [EquipoController::class, 'registrar']);
});