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
            return response()->json($partidos);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    // Cache en memoria estático
    private static $cacheData = null;
    private static $cacheTime = 0;
    private const CACHE_DURATION = 0;

    // ENDPOINT PÚBLICO OPTIMIZADO
    public function datosPublicos()
    {
        try {
            $now = time();
            
            // Verificar cache
            if (self::$cacheData && ($now - self::$cacheTime) < self::CACHE_DURATION) {
                return response()->json(self::$cacheData);
            }
            
            $database = app('firebase')->createDatabase();
            
            // Obtener datos
            $jugadores = $database->getReference('jugadores')->getValue() ?? [];
            $equipos = $database->getReference('equipos')->getValue() ?? [];
            $partidos = $database->getReference('partidos')->getValue() ?? [];
            $campos = $database->getReference('campos')->getValue() ?? [];
            
            // Filtrar solo datos esenciales
            $jugadoresFiltrados = [];
            foreach ($jugadores as $key => $j) {
                $jugadoresFiltrados[$key] = [
                    'nombre' => $j['nombre'] ?? '',
                    'equipo' => $j['equipo'] ?? '',
                    'goles' => (int)($j['goles'] ?? 0),
                    'asistencias' => (int)($j['asistencias'] ?? 0),
                    'partidos_jugados' => (int)($j['partidos_jugados'] ?? 0),
                    'numero' => (int)($j['numero'] ?? 0),
                    'edad' => (int)($j['edad'] ?? 0),
                    'estatus' => $j['estatus'] ?? 'activo'
                ];
            }
            
            $equiposFiltrados = [];
            foreach ($equipos as $key => $e) {
                $equiposFiltrados[$key] = [
                    'nombre' => $e['nombre'] ?? '',
                    'escudo' => $e['escudo'] ?? '',
                    'portero_id' => $e['portero_id'] ?? '',
                    'portero_nombre' => $e['portero_nombre'] ?? ''
                ];
            }
            
            $partidosFiltrados = [];
            foreach ($partidos as $key => $p) {
                $partidoData = [
                    'id' => $key,
                    'equipo_local' => $p['equipo_local'] ?? '',
                    'equipo_visitante' => $p['equipo_visitante'] ?? '',
                    'fecha' => $p['fecha'] ?? '',
                    'hora' => $p['hora'] ?? '',
                    'jornada' => $p['jornada'] ?? '',
                    'tipo' => $p['tipo'] ?? '',
                    'fase' => $p['fase'] ?? '',
                    'resultado_confirmado' => $p['resultado_confirmado'] ?? false,
                    'estatus' => $p['estatus'] ?? '',
                    'goles_local' => (int)($p['goles_local'] ?? 0),
                    'goles_visitante' => (int)($p['goles_visitante'] ?? 0),
                    'campo_id' => $p['campo_id'] ?? '',
                    'detalle_jugadores' => $p['detalle_jugadores'] ?? null
                ];
                $partidosFiltrados[$key] = $partidoData;
            }
            
            $camposFiltrados = [];
            foreach ($campos as $key => $c) {
                $camposFiltrados[$key] = [
                    'id' => $key,
                    'nombre' => $c['nombre'] ?? $c['lugar'] ?? '',
                    'lugar' => $c['lugar'] ?? '',
                    'estado' => $c['estado'] ?? 'disponible'
                ];
            }
            
            self::$cacheData = [
                'jugadores' => $jugadoresFiltrados,
                'equipos' => $equiposFiltrados,
                'partidos' => $partidosFiltrados,
                'campos' => $camposFiltrados
            ];
            self::$cacheTime = $now;
            
            return response()->json(self::$cacheData);
            
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

            if ($partido['resultado_confirmado'] ?? false) {
                return response()->json(['error' => 'El acta de este partido ya ha sido cerrada.'], 403);
            }

            $updateData = [
                'goles_local' => (int)$request->goles_local,
                'goles_visitante' => (int)$request->goles_visitante,
                'detalle_jugadores' => $request->detalle_jugadores 
            ];

            $confirmarFinal = filter_var($request->confirmar_final, FILTER_VALIDATE_BOOLEAN);

            if ($confirmarFinal) {
                $updateData['resultado_confirmado'] = true;
                $updateData['estatus'] = 'confirmado';

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

                $equipoLocal = trim($partido['equipo_local']);
                $equipoVis   = trim($partido['equipo_visitante']);
                $equiposEnJuego = [$equipoLocal, $equipoVis];

                $jugadoresRef = $this->database->getReference('jugadores');
                $todosLosJugadores = $jugadoresRef->getValue() ?? [];

                foreach ($todosLosJugadores as $tel => $j) {
                    $equipoJugador = trim($j['equipo'] ?? '');
                    
                    if (in_array($equipoJugador, $equiposEnJuego) && ($j['estatus'] ?? '') === 'suspendido') {
                        $restantes = (int)($j['partidos_suspension'] ?? 0);

                        if ($restantes > 0) {
                            $nuevosRestantes = $restantes - 1;
                            $updSuspension = ['partidos_suspension' => $nuevosRestantes];

                            if ($nuevosRestantes <= 0) {
                                $updSuspension['estatus'] = 'activo';
                                $updSuspension['partidos_suspension'] = 0;
                            }
                            
                            $this->database->getReference('jugadores/' . $tel)->update($updSuspension);
                        }
                    }
                }
            }

            $referencia->update($updateData);
            
            // Limpiar cache para próxima solicitud
            self::$cacheData = null;
            
            return response()->json(['message' => 'Marcador actualizado', 'confirmado' => $confirmarFinal]);

        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    public function crear(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'equipo_local' => 'required',
                'equipo_visitante' => 'required',
                'fecha' => 'required',
                'hora' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $partidoId = 'partido_' . time();
            $this->database->getReference('partidos/' . $partidoId)->set([
                'equipo_local' => $request->equipo_local,
                'equipo_visitante' => $request->equipo_visitante,
                'fecha' => $request->fecha,
                'hora' => $request->hora,
                'jornada' => $request->jornada ?? '1',
                'estatus' => 'pendiente',
                'goles_local' => 0,
                'goles_visitante' => 0,
                'resultado_confirmado' => false
            ]);

            return response()->json(['message' => 'Partido creado', 'id' => $partidoId]);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    public function eliminar(Request $request, $id)
    {
        try {
            $this->database->getReference('partidos/' . $id)->remove();
            return response()->json(['message' => 'Partido eliminado']);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    public function limpiarTodo()
    {
        try {
            $this->database->getReference('partidos')->remove();
            return response()->json(['message' => 'Todos los partidos eliminados']);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    public function actualizarDatosSorteo(Request $request, $id)
    {
        try {
            $updateData = [
                'equipo_local' => $request->equipo_local,
                'equipo_visitante' => $request->equipo_visitante,
                'fecha' => $request->fecha,
                'hora' => $request->hora,
                'jornada' => $request->jornada,
                'campo_id' => $request->campo_id
            ];

            $this->database->getReference('partidos/' . $id)->update($updateData);
            return response()->json(['message' => 'Datos actualizados']);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    public function generarTorneo(Request $request)
    {
        try {
            $equipos = $request->equipos ?? [];
            $jornadas = $request->jornadas ?? 1;
            $fechaInicio = $request->fecha_inicio ?? date('Y-m-d');

            if (count($equipos) < 2) {
                return response()->json(['error' => 'Se requieren al menos 2 equipos'], 422);
            }

            $this->database->getReference('partidos')->remove();

            $partidos = [];
            $partidosId = [];
            
            if (count($equipos) == 2) {
                // Modalidad ida y vuelta
                for ($i = 0; $i < $jornadas; $i++) {
                    $fecha = date('Y-m-d', strtotime("+{$i} week", strtotime($fechaInicio)));
                    
                    // Ida
                    $partidosId[] = 'partido_' . uniqid();
                    $partidos[] = [
                        'equipo_local' => $equipos[0],
                        'equipo_visitante' => $equipos[1],
                        'fecha' => $fecha,
                        'hora' => '00:00',
                        'jornada' => ($i * 2) + 1,
                        'estatus' => 'pendiente',
                        'goles_local' => 0,
                        'goles_visitante' => 0,
                        'resultado_confirmado' => false,
                        'tipo' => 'liga'
                    ];
                    
                    // Vuelta
                    $partidosId[] = 'partido_' . uniqid();
                    $partidos[] = [
                        'equipo_local' => $equipos[1],
                        'equipo_visitante' => $equipos[0],
                        'fecha' => date('Y-m-d', strtotime("+1 day", strtotime($fecha))),
                        'hora' => '00:00',
                        'jornada' => ($i * 2) + 2,
                        'estatus' => 'pendiente',
                        'goles_local' => 0,
                        'goles_visitante' => 0,
                        'resultado_confirmado' => false,
                        'tipo' => 'liga'
                    ];
                }
            } else {
                // round-robin
                $n = count($equipos);
                $partidosCreados = [];
                
                for ($i = 0; $i < $jornadas; $i++) {
                    for ($j = 0; $j < $n / 2; $j++) {
                        $local = $equipos[$j];
                        $visitante = $equipos[$n - 1 - $j];
                        
                        $fecha = date('Y-m-d', strtotime("+{$i} week", strtotime($fechaInicio)));
                        
                        $partidosId[] = 'partido_' . uniqid();
                        $partidos[] = [
                            'equipo_local' => $local,
                            'equipo_visitante' => $visitante,
                            'fecha' => $fecha,
                            'hora' => '00:00',
                            'jornada' => $i + 1,
                            'estatus' => 'pendiente',
                            'goles_local' => 0,
                            'goles_visitante' => 0,
                            'resultado_confirmado' => false,
                            'tipo' => 'liga'
                        ];
                    }
                    
                    // rotar equipos
                    $primero = array_shift($equipos);
                    $ultimo = array_pop($equipos);
                    array_unshift($equipos, $ultimo);
                    array_push($equipos, $primero);
                }
            }

            foreach ($partidosId as $index => $pid) {
                $this->database->getReference('partidos/' . $pid)->set($partidos[$index]);
            }

            // Limpiar cache
            self::$cacheData = null;

            return response()->json([
                'message' => 'Torneo generado',
                'partidos_creados' => count($partidos)
            ]);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }

    public function guardarPodio(Request $request)
    {
        try {
            $historialRef = $this->database->getReference('historial_torneos');
            $historialActual = $historialRef->getValue() ?? [];

            // Aceptar ambos formatos: nuevo (admin) y antiguo
            $nuevoTorneo = [
                'fecha' => $request->fecha_finalizacion ?? date('Y-m-d H:i:s'),
                'nombre_torneo' => $request->nombre_torneo ?? 'Torneo de Copa ' . date('Y'),
                'primer_lugar' => $request->primer_lugar ?? $request->campeon ?? '',
                'segundo_lugar' => $request->segundo_lugar ?? $request->subCampeon ?? '',
                'tercer_lugar' => $request->tercer_lugar ?? '',
                'goleador' => $request->goleador ?? $request->max_goleador ?? '',
                'goles_goleador' => (int)($request->goles_goleador ?? $request->goles_max_goleador ?? 0),
                'resumen_partidos' => $request->resumen_partidos ?? [],
                'tabla_final' => $request->tabla_final ?? []
            ];

            $historialActual[] = $nuevoTorneo;
            $historialRef->set($historialActual);

            return response()->json(['message' => 'Podio guardado en historial']);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 500); 
        }
    }
}