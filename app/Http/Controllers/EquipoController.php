<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EquipoController extends Controller
{
    private $database;

    public function __construct()
    {
        $this->database = app('firebase')->createDatabase();
    }

    public function registrar(Request $request)
    {
        $escudoUrl = $request->escudo_url; 

        if ($request->hasFile('escudo_file')) {
            $file = $request->file('escudo_file');
            $name = time() . '_' . $file->getClientOriginalName();
            $path = public_path('img/escudos');
            if (!file_exists($path)) { mkdir($path, 0777, true); }
            $file->move($path, $name);
            $escudoUrl = '/img/escudos/' . $name;
        }

        // Usamos el nombre como ID (limpiando caracteres especiales)
        $equipoId = str_replace(['.', '#', '$', '[', ']'], '-', $request->nombre);
        
        $this->database->getReference('equipos/' . $equipoId)->set([
            'nombre' => $request->nombre,
            'escudo' => $escudoUrl ?? 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png'
        ]);

        return response()->json(['message' => 'Equipo guardado']);
    }

    public function listar()
    {
        $database = app('firebase')->createDatabase();
        $equipos = $database->getReference('equipos')->getValue();
    
        // Si no hay equipos, devolvemos un array vacío para que el JS no rompa
        return response()->json($equipos ?? []);
    }

    public function listarEscudos()
    {
        // Ruta física para buscar los archivos
        $path = public_path('img/escudos');

        // Si la carpeta no existe, la creamos para que no de error
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
            return response()->json([]);
        }

        $archivos = array_diff(scandir($path), array('.', '..'));
        
        $escudos = [];
        foreach ($archivos as $archivo) {
            // La URL debe empezar con /img/... para que el navegador la encuentre
            $escudos[] = '/img/escudos/' . $archivo;
        }
        
        return response()->json($escudos);
    }

    public function eliminar($id)
    {
        $this->database->getReference('equipos/' . $id)->remove();
        return response()->json(['message' => 'Equipo eliminado']);
    }

}