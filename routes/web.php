<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JugadorController;

Route::get('/', function () {
    $jugadores = app(JugadorController::class)->listarTodos()->getData(true);
    return view('welcome', ['jugadores' => $jugadores]);
});
