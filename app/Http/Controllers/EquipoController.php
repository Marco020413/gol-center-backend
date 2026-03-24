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
        // 1. Prioridad: Si hay un archivo, usamos ese. Si no, usamos el radio seleccionado.
        $escudoUrl = $request->escudo_url; 

        if ($request->hasFile('escudo_file')) {
            $file = $request->file('escudo_file');
            
            // Validar que sea imagen
            if (strpos($file->getMimeType(), 'image') !== false) {
                $name = time() . '_' . $file->getClientOriginalName();
                
                // Asegurar que la carpeta exista
                $path = public_path('img/escudos');
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }

                $file->move($path, $name);
                $escudoUrl = '/img/escudos/' . $name;
            }
        }

        // 2. Guardar en Firebase
        // Usamos un ID más limpio que base64 para evitar problemas de rutas
        $equipoId = str_replace(['.', '#', '$', '[', ']'], '-', $request->nombre);
        
        $this->database->getReference('equipos/' . $equipoId)->set([
            'nombre' => $request->nombre,
            'escudo' => $escudoUrl
        ]);

        return response()->json(['message' => 'Equipo registrado correctamente', 'url' => $escudoUrl]);
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
}