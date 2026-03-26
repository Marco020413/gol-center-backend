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

   public function listar(Request $request)
    {
        try {
            $partidos = $this->database->getReference('partidos')->getValue() ?? [];
            $equipos = $this->database->getReference('equipos')->getValue() ?? [];
            
            // FORZAMOS LA HORA DE MÉXICO PARA COMPARAR CORRECTAMENTE
            $ahora = \Carbon\Carbon::now('America/Mexico_City'); 

            $listadoFormateado = [];
            $escudoDefault = 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png';

            foreach ($partidos as $id => $p) {
                $idLocal = str_replace(['.', '#', '$', '[', ']', ' '], '-', $p['equipo_local'] ?? '');
                $idVisitante = str_replace(['.', '#', '$', '[', ']', ' '], '-', $p['equipo_visitante'] ?? '');

                // Convertimos la fecha y hora del partido a un objeto Carbon (Hora México)
                $fechaPartido = \Carbon\Carbon::parse($p['fecha'] . ' ' . ($p['hora'] ?? '00:00'), 'America/Mexico_City');
                
                // Definimos el fin del partido (Hora inicio + 100 minutos)
                $finPartido = $fechaPartido->copy()->addMinutes(100);

                // LÓGICA DE ESTADOS AUTOMÁTICOS
                if (!($p['resultado_confirmado'] ?? false)) {
                    if ($ahora->lt($fechaPartido)) {
                        // Si la hora actual es MENOR a la del partido
                        $p['estatus'] = 'programado';
                    } elseif ($ahora->between($fechaPartido, $finPartido)) {
                        // Si estamos entre el inicio y el fin
                        $p['estatus'] = 'en_curso';
                    } else {
                        // Si ya pasaron los 100 minutos
                        $p['estatus'] = 'finalizado';
                    }
                } else {
                    $p['estatus'] = 'confirmado';
                }

                $p['escudo_local'] = $equipos[$idLocal]['escudo'] ?? $escudoDefault;
                $p['escudo_visitante'] = $equipos[$idVisitante]['escudo'] ?? $escudoDefault;
                $p['fecha_formateada'] = $fechaPartido->format('d/m') . ' - ' . ($p['hora'] ?? '00:00');
                
                $listadoFormateado[$id] = $p;
            }
            return response()->json($listadoFormateado);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    public function actualizarMarcador(Request $request, $id)
    {
        try {
            $referencia = $this->database->getReference('partidos/' . $id);
            $partido = $referencia->getValue();

            if (!$partido) {
                return response()->json(['error' => 'Partido no encontrado.'], 404);
            }

            // Regla de Oro: Si ya está confirmado, nadie toca nada
            if ($partido['resultado_confirmado'] ?? false) {
                return response()->json(['error' => 'El acta de este partido ya ha sido cerrada.'], 403);
            }

            // 1. Guardar datos actuales para persistencia visual
            $updateData = [
                'goles_local' => (int)$request->goles_local,
                'goles_visitante' => (int)$request->goles_visitante,
                'detalle_jugadores' => $request->detalle_jugadores 
            ];

            // 2. Si se marca como FINALIZADO, procesamos estadísticas permanentes
            if ($request->confirmar_final) {
                $updateData['resultado_confirmado'] = true;
                $updateData['estatus'] = 'confirmado';

                $detalleJugadores = $request->detalle_jugadores ?? [];

                foreach ($detalleJugadores as $telefono => $stats) {
                    // Si el admin marcó asistencia, procesamos aunque esté suspendido/lesionado
                    if (isset($stats['asistio']) && $stats['asistio']) {
                        $jugadorRef = $this->database->getReference('jugadores/' . $telefono);
                        $datosJugador = $jugadorRef->getValue();

                        if ($datosJugador) {
                            // Sumamos goles y PJ (Partido Jugado)
                            $nuevosGoles = (int)($datosJugador['goles'] ?? 0) + (int)$stats['goles'];
                            $nuevosPJ = (int)($datosJugador['partidos_jugados'] ?? 0) + 1;

                            $jugadorRef->update([
                                'goles' => $nuevosGoles,
                                'partidos_jugados' => $nuevosPJ
                            ]);
                        }
                    }
                }
            }

            // 3. Aplicamos todos los cambios al partido
            $referencia->update($updateData);

            return response()->json([
                'message' => $request->confirmar_final ? 'Acta cerrada y estadísticas procesadas' : 'Marcador actualizado'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    // Regla #4: Solo Admin debería crear (por ahora lo metemos al grupo protegido)
    public function crear(Request $request)
    {
        try {
            // 1. Validar que vengan todos los campos necesarios
            if (!$request->campo_id || !$request->local || !$request->visitante || !$request->fecha || !$request->hora) {
                return response()->json(['error' => 'Faltan datos obligatorios para programar el partido.'], 422);
            }

            // 2. Obtener datos de los equipos para los escudos
            $equiposRef = $this->database->getReference('equipos')->getValue() ?? [];
            $escudoLocal = '';
            $escudoVisitante = '';

            foreach ($equiposRef as $eq) {
                if ($eq['nombre'] === $request->local) $escudoLocal = $eq['escudo'] ?? '';
                if ($eq['nombre'] === $request->visitante) $escudoVisitante = $eq['escudo'] ?? '';
            }

            // 3. Lógica de tiempos para validación
            $nuevoInicio = \Carbon\Carbon::parse($request->fecha . ' ' . $request->hora);
            $nuevoFin = $nuevoInicio->copy()->addMinutes(100);
            
            $partidosExistentes = $this->database->getReference('partidos')->getValue() ?? [];

            foreach ($partidosExistentes as $p) {
                // Solo validamos partidos de la misma fecha
                if ($p['fecha'] === $request->fecha) {
                    $pInicio = \Carbon\Carbon::parse($p['fecha'] . ' ' . $p['hora']);
                    $pFin = $pInicio->copy()->addMinutes(100);

                    // Verificar si el horario se traslapa
                    $hayTraslape = ($nuevoInicio->lt($pFin) && $nuevoFin->gt($pInicio));

                    if ($hayTraslape) {
                        // REGLA A: Validación de Cancha (La que ya tenías)
                        if ($p['campo_id'] === $request->campo_id) {
                            return response()->json(['error' => 'La cancha ya está ocupada en ese rango de tiempo.'], 422);
                        }

                        // REGLA B: Validación de Equipos (La nueva)
                        $equiposEnConflicto = [$p['equipo_local'], $p['equipo_visitante']];
                        
                        if (in_array($request->local, $equiposEnConflicto)) {
                            return response()->json(['error' => "El equipo {$request->local} ya tiene un partido programado a esta hora."], 422);
                        }

                        if (in_array($request->visitante, $equiposEnConflicto)) {
                            return response()->json(['error' => "El equipo {$request->visitante} ya tiene un partido programado a esta hora."], 422);
                        }
                    }
                }
            }

            // 4. Registrar en Firebase si todo está bien
            $id_partido = uniqid('partido_');
            $this->database->getReference('partidos/' . $id_partido)->set([
                'equipo_local'       => $request->local,
                'equipo_visitante'   => $request->visitante,
                'escudo_local'     => $escudoLocal,
                'escudo_visitante' => $escudoVisitante,
                'campo_id'           => $request->campo_id,
                'goles_local'        => 0,
                'goles_visitante'    => 0,
                'fecha'              => $request->fecha,
                'hora'               => $request->hora,
                'estatus'            => 'programado',
                'resultado_confirmado' => false
            ]);

            return response()->json(['message' => 'Partido programado exitosamente']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function eliminar($id)
    {
        $this->database->getReference('partidos/' . $id)->remove();
        return response()->json(['message' => 'Partido borrado']);
    }
}