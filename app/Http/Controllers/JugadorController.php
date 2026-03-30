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
            'nombre'    => 'required|string|max:55',
            'telefono'  => 'required|digits:10',   
            'equipo'    => 'required|string|not_in:Selecciona un equipo',
            'numero'    => 'required|integer|between:1,99',
            'edad'      => 'required|integer|between:5,99',
            'direccion' => 'required|string|max:255',
        ], [
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'numero.between'  => 'El dorsal debe ser un número entre 1 y 99.',
            'nombre.max'      => 'El nombre no puede exceder los 55 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
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
                'goles'            => 0,
                'estatus'            => 'activo', // Valor por defecto
                'partidos_suspension'=> 0       
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

                // Actualizamos los datos del jugador (incluyendo los partidos de castigo que pusiste en el modal)
                $jugadorRef->update([
                    'nombre'              => $request->nombre,
                    'numero'              => (int)$request->numero,
                    'equipo'              => $request->equipo,
                    'edad'                => $request->edad,
                    'direccion'           => $request->direccion,
                    'estatus'             => $request->estatus ?? 'activo',
                    'partidos_suspension' => (int)($request->partidos_suspension ?? 0)
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

    public function finalizarPartido(Request $request, $id) 
    {
        try {
            $partidoRef = $this->database->getReference('partidos/' . $id);
            $partidoData = $partidoRef->getValue();

            // Convertimos a booleano real por si llega como string
            $confirmarFinal = filter_var($request->confirmar_final, FILTER_VALIDATE_BOOLEAN);

            if ($confirmarFinal) {
                // Obtenemos equipos y limpiamos espacios
                $equipoLocal = trim($partidoData['equipo_local']);
                $equipoVis   = trim($partidoData['equipo_visitante']);
                $equiposEnJuego = [$equipoLocal, $equipoVis];

                $jugadoresRef = $this->database->getReference('jugadores');
                $todosLosJugadores = $jugadoresRef->getValue() ?? [];

                foreach ($todosLosJugadores as $telefono => $j) {
                    // Verificamos que el jugador tenga equipo asignado
                    $equipoJugador = trim($j['equipo'] ?? '');
                    
                    if (in_array($equipoJugador, $equiposEnJuego)) {
                        if (($j['estatus'] ?? '') === 'suspendido') {
                            
                            $restantes = (int)($j['partidos_suspension'] ?? 0);

                            if ($restantes > 0) {
                                $nuevosRestantes = $restantes - 1;
                                
                                $updateData = [
                                    'partidos_suspension' => $nuevosRestantes
                                ];

                                // REGLA DE ORO: Si llegó a 0, activar.
                                if ($nuevosRestantes <= 0) {
                                    $updateData['estatus'] = 'activo';
                                    $updateData['partidos_suspension'] = 0;
                                }

                                // Actualizar en Firebase
                                $this->database->getReference('jugadores/' . $telefono)->update($updateData);
                            }
                        }
                    }
                }
            }

            // Guardar estatus del partido
            $partidoRef->update([
                'estatus' => 'confirmado', 
                'resultado_confirmado' => true
            ]);

            return response()->json(['message' => 'Acta cerrada y sanciones procesadas']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}


