<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartidoController extends Controller
{
    private $database;

    public function __construct()
    {
        $this->database = app('firebase')->createDatabase();
    }

    // Regla #4: Solo Admin debería crear (por ahora lo metemos al grupo protegido)
    public function crear(Request $request)
    {
        $id_partido = uniqid('partido_');
        $this->database->getReference('partidos/' . $id_partido)->set([
            'equipo_local' => $request->local,
            'equipo_visitante' => $request->visitante,
            'goles_local' => 0,
            'goles_visitante' => 0,
            'fecha' => $request->fecha,
            'estatus' => 'programado' // Valores: programado, en_curso, finalizado
        ]);

        return response()->json(['message' => 'Partido creado', 'id' => $id_partido]);
    }

    // Regla #3 y #5: El Árbitro actualiza, pero validamos el estatus
    public function actualizarMarcador(Request $request, $id)
    {
        $referencia = $this->database->getReference('partidos/' . $id);
        $partido = $referencia->getSnapshot()->getValue();

        if (!$partido) {
            return response()->json(['error' => 'Partido no encontrado'], 404);
        }

        // --- VALIDACIÓN DE SEGURIDAD (Regla #5) ---
        if ($partido['estatus'] === 'finalizado') {
            return response()->json([
                'error' => 'Acción denegada. El partido ya ha finalizado y no puede ser modificado.'
            ], 403);
        }

        // Si el partido está programado o en curso, permitimos actualizar
        $referencia->update([
            'goles_local' => $request->goles_local,
            'goles_visitante' => $request->goles_visitante,
            'estatus' => $request->estatus // El árbitro decide cuándo pasar a 'finalizado'
        ]);

        return response()->json(['message' => 'Marcador actualizado correctamente']);
    }
}