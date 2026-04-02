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
        try {
            // 1. Obtenemos el nombre antes de borrar
            $equipoParaBorrar = $this->database->getReference('equipos/' . $id)->getValue();
            $nombreEquipo = $equipoParaBorrar['nombre'] ?? null;

            if (!$nombreEquipo) {
                return response()->json(['error' => 'Equipo no encontrado'], 404);
            }

            // 2. Buscamos a todos los jugadores que pertenecen a este equipo para liberarlos
            $jugadores = $this->database->getReference('jugadores')->getValue() ?? [];
            
            foreach ($jugadores as $telefono => $datos) {
                if (isset($datos['equipo']) && $datos['equipo'] === $nombreEquipo) {
                    // Los ponemos como 'Libre' o simplemente quitamos el equipo
                    $this->database->getReference('jugadores/' . $telefono)->update([
                        'equipo' => 'Libre' 
                    ]);
                }
            }

            // 3. Ahora sí borramos el equipo
            $this->database->getReference('equipos/' . $id)->remove();

            return response()->json(['message' => 'Equipo eliminado y jugadores liberados con éxito']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

    // En tu controlador de Laravel:
    public function listarEquipos() {
        try {
            $equipos = $this->database->getReference('equipos')->getValue() ?? [];
            return response()->json($equipos);
        } catch (\Exception $e) {
            // Esto evita que Laravel mande la pantalla naranja de error y mande un JSON limpio
            return response()->json(['error' => 'Error de Firebase'], 500);
        }
    }
    
    public function eliminarEscudoArchivo(Request $request)
    {
        try {
            $nombreArchivo = basename($request->archivo); // Seguridad: solo el nombre del archivo
            $rutaFisica = public_path('img/escudos/' . $nombreArchivo);

            if (file_exists($rutaFisica)) {
                unlink($rutaFisica);
                return response()->json(['message' => 'Archivo eliminado']);
            }
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}