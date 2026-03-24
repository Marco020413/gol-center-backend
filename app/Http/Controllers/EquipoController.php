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
        // 1. Definimos el ID: Si es edición usamos el que viene, si no, generamos uno nuevo.
        $equipoId = $request->equipo_id_edit ?? str_replace(['.', '#', '$', '[', ']'], '-', $request->nombre);
        
        $escudoUrl = $request->escudo_url; 

        if ($request->hasFile('escudo_file')) {
            $file = $request->file('escudo_file');
            $name = time() . '_' . $file->getClientOriginalName();
            $path = public_path('img/escudos');
            if (!file_exists($path)) { mkdir($path, 0777, true); }
            $file->move($path, $name);
            $escudoUrl = '/img/escudos/' . $name;
        }

        $this->database->getReference('equipos/' . $equipoId)->update([
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

    public function actualizar(Request $request, $telefono)
    {
        try {
            $jugadorRef = $this->database->getReference('jugadores/' . $telefono);
            $datosActuales = $jugadorRef->getValue();

            // VALIDACIÓN: Mínimo 5 partidos para cambiar de equipo
            if (isset($request->equipo) && $datosActuales['equipo'] !== $request->equipo) {
                $pj = (int)($datosActuales['partidos_jugados'] ?? 0);
                if ($pj < 5) {
                    return response()->json([
                        'error' => "El jugador solo tiene {$pj} partidos jugados. Necesita mínimo 5 para ser transferido."
                    ], 422);
                }
            }

            // Proceder con la actualización normal
            $jugadorRef->update([
                'nombre'    => $request->nombre,
                'numero'    => $request->numero,
                'equipo'    => $request->equipo,
                'edad'      => $request->edad,
                'direccion' => $request->direccion
            ]);

            return response()->json(['message' => '¡Jugador actualizado!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}