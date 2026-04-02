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
            
            $ahora = \Carbon\Carbon::now('America/Mexico_City'); 
            $listadoFormateado = [];
            $escudoDefault = 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png';

            foreach ($partidos as $id => $p) {
                // Limpieza de IDs para buscar escudos
                $idLocal = str_replace(['.', '#', '$', '[', ']', ' '], '-', $p['equipo_local'] ?? '');
                $idVisitante = str_replace(['.', '#', '$', '[', ']', ' '], '-', $p['equipo_visitante'] ?? '');

                // --- LÓGICA PARA MANEJAR PARTIDOS GENERADOS (PENDIENTES) ---
                if (($p['fecha'] ?? '') === 'PENDIENTE') {
                    $p['estatus'] = 'programado';
                    $p['fecha_formateada'] = 'POR ASIGNAR';
                } else {
                    try {
                        $fechaPartido = \Carbon\Carbon::parse($p['fecha'] . ' ' . ($p['hora'] ?? '00:00'), 'America/Mexico_City');
                        $finPartido = $fechaPartido->copy()->addMinutes(100);

                        if (!($p['resultado_confirmado'] ?? false)) {
                            if ($ahora->lt($fechaPartido)) {
                                $p['estatus'] = 'programado';
                            } elseif ($ahora->between($fechaPartido, $finPartido)) {
                                $p['estatus'] = 'en_curso';
                            } else {
                                $p['estatus'] = 'finalizado';
                            }
                        } else {
                            $p['estatus'] = 'confirmado';
                        }
                        $p['fecha_formateada'] = $fechaPartido->format('d/m') . ' - ' . ($p['hora'] ?? '00:00');
                    } catch (\Exception $e) {
                        // Si falla el parseo por alguna razón, fallback seguro
                        $p['estatus'] = 'programado';
                        $p['fecha_formateada'] = $p['fecha'] ?? 'S/F';
                    }
                }

                $p['escudo_local'] = $equipos[$idLocal]['escudo'] ?? $escudoDefault;
                $p['escudo_visitante'] = $equipos[$idVisitante]['escudo'] ?? $escudoDefault;
                
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

            // 1. Guardar datos actuales para persistencia visual (lo que el admin ve en el modal)
            $updateData = [
                'goles_local' => (int)$request->goles_local,
                'goles_visitante' => (int)$request->goles_visitante,
                'detalle_jugadores' => $request->detalle_jugadores 
            ];

            // 2. Si se marca como FINALIZADO, procesamos la "magia" automática
            $confirmarFinal = filter_var($request->confirmar_final, FILTER_VALIDATE_BOOLEAN);

            if ($confirmarFinal) {
                $updateData['resultado_confirmado'] = true;
                $updateData['estatus'] = 'confirmado';

                // --- A. PROCESAR ESTADÍSTICAS DE JUGADORES QUE ASISTIERON ---
                $detalleJugadores = $request->detalle_jugadores ?? [];
                foreach ($detalleJugadores as $telefono => $stats) {
                    if (isset($stats['asistio']) && $stats['asistio']) {
                        $jugadorRef = $this->database->getReference('jugadores/' . $telefono);
                        $datosJugador = $jugadorRef->getValue();

                        if ($datosJugador) {
                            $nuevosGoles = (int)($datosJugador['goles'] ?? 0) + (int)($stats['goles'] ?? 0);
                            $nuevosPJ = (int)($datosJugador['partidos_jugados'] ?? 0) + 1;

                            $jugadorRef->update([
                                'goles' => $nuevosGoles,
                                'partidos_jugados' => $nuevosPJ
                            ]);
                        }
                    }
                }

                // --- B. PROCESAR DESCUENTO DE SANCIONES (Lógica Automática) ---
                $equipoLocal = trim($partido['equipo_local']);
                $equipoVis   = trim($partido['equipo_visitante']);
                $equiposEnJuego = [$equipoLocal, $equipoVis];

                $jugadoresRef = $this->database->getReference('jugadores');
                $todosLosJugadores = $jugadoresRef->getValue() ?? [];

                foreach ($todosLosJugadores as $tel => $j) {
                    $equipoJugador = trim($j['equipo'] ?? '');
                    
                    // Si el jugador es de uno de los equipos que jugó Y está suspendido
                    if (in_array($equipoJugador, $equiposEnJuego) && ($j['estatus'] ?? '') === 'suspendido') {
                        $restantes = (int)($j['partidos_suspension'] ?? 0);

                        if ($restantes > 0) {
                            $nuevosRestantes = $restantes - 1;
                            $updSuspension = ['partidos_suspension' => $nuevosRestantes];

                            // Si llegó a 0, se activa automáticamente
                            if ($nuevosRestantes <= 0) {
                                $updSuspension['estatus'] = 'activo';
                                $updSuspension['partidos_suspension'] = 0;
                            }
                            
                            $this->database->getReference('jugadores/' . $tel)->update($updSuspension);
                        }
                    }
                }
            }

            // 3. Aplicamos todos los cambios al partido en Firebase
            $referencia->update($updateData);

            return response()->json([
                'message' => $confirmarFinal ? 'Acta cerrada, estadísticas y sanciones procesadas' : 'Marcador actualizado'
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
                'updated_at' => now()->timestamp,
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

    public function generarTorneo(Request $request) {
        try {
            $partidos = $request->partidos; // Recibimos el array completo
            $equiposRef = $this->database->getReference('equipos')->getValue() ?? [];

            foreach ($partidos as $p) {
                $escudoLocal = '';
                $escudoVisitante = '';

                // Buscamos los escudos para que el rol ya aparezca con logos
                foreach ($equiposRef as $eq) {
                    if ($eq['nombre'] === $p['equipo_local']) $escudoLocal = $eq['escudo'] ?? '';
                    if ($eq['nombre'] === $p['equipo_visitante']) $escudoVisitante = $eq['escudo'] ?? '';
                }

                $id_partido = uniqid('partido_');
                $this->database->getReference('partidos/' . $id_partido)->set([
                    'equipo_local'     => $p['equipo_local'],
                    'equipo_visitante' => $p['equipo_visitante'],
                    'escudo_local'     => $escudoLocal,
                    'escudo_visitante' => $escudoVisitante,
                    'jornada'          => $p['jornada'],
                    'campo_id'         => 'sin_asignar',
                    'fecha'            => 'PENDIENTE',
                    'hora'             => '00:00',
                    'goles_local'      => 0,
                    'goles_visitante'  => 0,
                    'estatus'          => 'programado',
                    'resultado_confirmado' => false
                ]);
            }

            return response()->json(['message' => 'Torneo generado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function limpiarTodo() {
        $this->database->getReference('partidos')->remove();
        return response()->json(['message' => 'DB Limpia']);
    }

    public function actualizarDatosSorteo(Request $request, $id) {
        try {
            $this->database->getReference('partidos/' . $id)->update([
                'campo_id' => $request->campo_id,
                'fecha'    => $request->fecha,
                'hora'     => $request->hora,
                'estatus'  => 'programado'
            ]);

            return response()->json(['message' => 'Partido programado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error en servidor: ' . $e->getMessage()], 500);
        }
    }

    public function guardarPodio(Request $request) {
        try {
            $idHistorial = 'torneo_' . date('Y_m_d_His');
            $datos = $request->all();

            // 1. OBTENEMOS LA LISTA DE JUGADORES ACTUALES PARA CRUZAR NOMBRES
            // Ajusta la ruta 'jugadores' según como esté en tu Firebase
            $jugadoresRepo = $this->database->getReference('jugadores')->getValue();

            // 2. RECORREMOS LOS PARTIDOS PARA INYECTAR NOMBRES EN EL DETALLE
            if (isset($datos['resumen_partidos'])) {
                foreach ($datos['resumen_partidos'] as $key => $partido) {
                    if (isset($partido['detalle_jugadores'])) {
                        foreach ($partido['detalle_jugadores'] as $tel => $info) {
                            // Si el jugador existe en nuestra base actual, grabamos su nombre "en piedra"
                            if (isset($jugadoresRepo[$tel])) {
                                $datos['resumen_partidos'][$key]['detalle_jugadores'][$tel]['nombre'] = $jugadoresRepo[$tel]['nombre'];
                            } else {
                                $datos['resumen_partidos'][$key]['detalle_jugadores'][$tel]['nombre'] = "Jugador Retirado";
                            }
                        }
                    }
                }
            }

            // 3. GUARDAMOS EL OBJETO YA "ENRIQUECIDO" CON NOMBRES
            $this->database->getReference('historial_torneos/' . $idHistorial)->set($datos);

            return response()->json(['message' => 'Torneo archivado con nombres estáticos correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}