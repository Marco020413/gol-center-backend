<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LigaController extends Controller
{
    private $database;

    public function __construct()
    {
        $this->database = app('firebase')->createDatabase();
    }

    public function listar()
    {
        try {
            $ligas = $this->database->getReference('ligas')->getValue();
            return response()->json($ligas ?? []);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function obtenerNombre($id)
    {
        try {
            $liga = $this->database->getReference('ligas/' . $id)->getValue();
            
            if (!$liga || !isset($liga['nombre'])) {
                return response()->json(['error' => 'Liga no encontrada'], 404);
            }
            
            return response()->json(['nombre' => $liga['nombre']]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
