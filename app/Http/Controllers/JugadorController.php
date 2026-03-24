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
            'equipo'   => 'required|string',
            'numero'   => 'required|numeric', 
            'edad'     => 'required|numeric',
            'direccion'=> 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            // --- VALIDACIÓN DE DORSAL ÚNICO POR EQUIPO ---
            $jugadores = $this->database->getReference('jugadores')->getValue() ?? [];
            
            foreach ($jugadores as $j) {
                if (isset($j['equipo']) && $j['equipo'] === $request->equipo && 
                    isset($j['numero']) && (int)$j['numero'] === (int)$request->numero) {
                    return response()->json([
                        'error' => "El número {$request->numero} ya está ocupado en el equipo {$request->equipo}."
                    ], 422);
                }
            }
            // ---------------------------------------------

            $path = 'jugadores/' . $request->telefono;
            $this->database->getReference($path)->set([
                'nombre'           => $request->nombre,
                'numero'           => $request->numero,
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
            // Validar duplicados al editar (ignorando al jugador actual)
            $jugadores = $this->database->getReference('jugadores')->getValue() ?? [];
            foreach ($jugadores as $id => $j) {
                if ($id != $telefono && 
                    isset($j['equipo']) && $j['equipo'] === $request->equipo && 
                    isset($j['numero']) && (int)$j['numero'] === (int)$request->numero) {
                    return response()->json([
                        'error' => "El número {$request->numero} ya lo tiene otro jugador en este equipo."
                    ], 422);
                }
            }

            $this->database->getReference('jugadores/' . $telefono)->update([
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

    public function listarTodos() {
        $jugadores = $this->database->getReference('jugadores')->getValue();
        return response()->json($jugadores);
    }

    public function eliminar($telefono) {
        $this->database->getReference('jugadores/' . $telefono)->remove();
        return response()->json(['message' => 'Jugador eliminado correctamente']);
    }
    
}