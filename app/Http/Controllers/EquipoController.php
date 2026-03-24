<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function registrar(Request $request)
    {
        $database = app('firebase')->createDatabase();
        $id = str_replace(' ', '_', strtolower($request->nombre)); // ID amigable
        
        $database->getReference('equipos/' . $id)->set([
            'nombre' => $request->nombre,
            'escudo_url' => $request->escudo_url,
            'puntos' => 0,
            'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0 // Estadísticas iniciales
        ]);

        return response()->json(['message' => 'Equipo creado con éxito']);
    }

    public function listar()
    {
        $database = app('firebase')->createDatabase();
        $equipos = $database->getReference('equipos')->getValue();
    
        // Si no hay equipos, devolvemos un array vacío para que el JS no rompa
        return response()->json($equipos ?? []);
    }
}