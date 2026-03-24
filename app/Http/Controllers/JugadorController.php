<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JugadorController extends Controller
{
    private $database;

    public function __construct()
    {
        $this->database = app('firebase')->createDatabase();
    }

    public function registrar(Request $request) {
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string',
            'telefono' => 'required|numeric',
            'equipo'   => 'required|string|not_in:Libre',
            'numero'   => 'required|numeric', 
            'edad'     => 'required|numeric',
            'direccion'=> 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            // --- VALIDACIÓN DE DORSAL ÚNICO SOLO EN EL MISMO EQUIPO ---
            $jugadores = $this->database->getReference('jugadores')->getValue() ?? [];
            
            foreach ($jugadores as $j) {
                // Verificamos que el jugador iterado tenga equipo y número para evitar errores de null
                if (isset($j['equipo']) && isset($j['numero'])) {
                    // REGLA: Si el equipo es el mismo Y el número es el mismo -> ERROR
                    if ($j['equipo'] === $request->equipo && (int)$j['numero'] === (int)$request->numero) {
                        return response()->json([
                            'error' => "El número {$request->numero} ya está ocupado en el equipo {$request->equipo}."
                        ], 422);
                    }
                }
            }

            $path = 'jugadores/' . $request->telefono;
            $this->database->getReference($path)->set([
                'nombre'           => $request->nombre,
                'numero'           => (int)$request->numero,
                'edad'             => $request->edad,
                'direccion'        => $request->direccion,
                'equipo'           => $request->equipo,
                'partidos_jugados' => 0,
                'goles'            => 0
            ]);

            return response()->json(['message' => '¡Registrado con éxito!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizar(Request $request, $telefono)
    {
        try {
            $jugadorRef = $this->database->getReference('jugadores/' . $telefono);
            $datosActuales = $jugadorRef->getValue();

            if (!$datosActuales) return response()->json(['error' => 'No existe el jugador'], 404);

            // --- REGLA DE LOS 5 PARTIDOS (Tu lógica invertida corregida) ---
            if (isset($request->equipo) && 
                $datosActuales['equipo'] !== 'Libre' && 
                $datosActuales['equipo'] !== $request->equipo) {
                
                $pj = (int)($datosActuales['partidos_jugados'] ?? 0);
                if ($pj >= 5) {
                    return response()->json([
                        'error' => "Transferencia Bloqueada: Este jugador ya es veterano en {$datosActuales['equipo']} ({$pj} partidos). No puede salir."
                    ], 422);
                }
            }

            // --- VALIDACIÓN DE DORSAL ÚNICO EN EL MISMO EQUIPO AL EDITAR ---
            $jugadores = $this->database->getReference('jugadores')->getValue() ?? [];
            foreach ($jugadores as $id => $j) {
                // 1. Ignoramos al jugador que estamos editando actualmente
                // 2. Comparamos equipo y número
                if ((string)$id !== (string)$telefono) {
                    if (isset($j['equipo']) && isset($j['numero'])) {
                        if ($j['equipo'] === $request->equipo && (int)$j['numero'] === (int)$request->numero) {
                            return response()->json([
                                'error' => "El número {$request->numero} ya está ocupado por otro jugador en {$request->equipo}."
                            ], 422);
                        }
                    }
                }
            }

            $jugadorRef->update([
                'nombre'    => $request->nombre,
                'numero'    => (int)$request->numero,
                'equipo'    => $request->equipo,
                'edad'      => $request->edad,
                'direccion' => $request->direccion
            ]);

            return response()->json(['message' => '¡Jugador actualizado!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarTodos() {
        $jugadores = $this->database->getReference('jugadores')->getValue();
        return response()->json($jugadores);
    }

    public function eliminar($telefono) {
        $this->database->getReference('jugadores/' . $telefono)->remove();
        return response()->json(['message' => 'Jugador eliminado correctamente']);
    }
}
