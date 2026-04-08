<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        
        $escudoUrl = $request->escudo_url ?? null;

        if ($request->hasFile('escudo_file')) {
            $file = $request->file('escudo_file');
            
            // Validar que sea imagen
            if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                return response()->json(['error' => 'El archivo debe ser una imagen válida (JPG, PNG, GIF, WEBP)'], 422);
            }
            
            $originalName = $file->getClientOriginalName();
            // Sanitizar nombre de archivo
            $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $name = time() . '_' . $sanitizedName;
            $path = public_path('img/escudos');
            
            // Crear directorio con permisos correctos
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            
            // Mover archivo
            $moved = $file->move($path, $name);
            
            if ($moved) {
                $escudoUrl = '/img/escudos/' . $name;
                Log::info('Escudo guardado: ' . $escudoUrl);
            } else {
                Log::error('Error al mover archivo de escudo');
            }
        }

        // Si no hay escudo seleccionado ni subido, usar default
        if (empty($escudoUrl)) {
            $escudoUrl = 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png';
        }

        $this->database->getReference('equipos/' . $equipoId)->update([
            'nombre' => $request->nombre,
            'escudo' => $escudoUrl
        ]);

        return response()->json(['message' => 'Equipo guardado', 'escudo' => $escudoUrl]);
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
            $equipoParaBorrar = $this->database->getReference('equipos/' . $id)->getValue();
            $nombreEquipo = $equipoParaBorrar['nombre'] ?? null;

            if (!$nombreEquipo) {
                return response()->json(['error' => 'Equipo no encontrado'], 404);
            }

            $jugadores = $this->database->getReference('jugadores')->getValue() ?? [];
            $updates = [];

            foreach ($jugadores as $telefono => $datos) {
                if (isset($datos['equipo']) && $datos['equipo'] === $nombreEquipo) {
                    // Acumulamos updates en lugar de ejecutarlos uno por uno
                    $updates["jugadores/{$telefono}/equipo"] = 'Libre';
                }
            }

            // Añadimos el borrado del equipo al mismo paquete
            $updates["equipos/{$id}"] = null;

            // UNA SOLA PETICIÓN para todo
            $this->database->getReference()->update($updates);

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