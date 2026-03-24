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
            $referencia = $this->database->getReference('partidos/' . $id);
            $partido = $referencia->getValue();

            // Regla de Oro: Si ya está confirmado, nadie toca nada
            if ($partido['resultado_confirmado'] ?? false) {
                return response()->json(['error' => 'El acta de este partido ya ha sido cerrada.'], 403);
            }

            $updateData = [
                'goles_local' => (int)$request->goles_local,
                'goles_visitante' => (int)$request->goles_visitante,
            ];

            // Si el usuario marcó la casilla de confirmar resultado final
            if ($request->confirmar_final) {
                $updateData['resultado_confirmado'] = true;
            }

            $referencia->update($updateData);
            return response()->json(['message' => 'Actualizado correctamente']);
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
            $equipos = $this->database->getReference('equipos')->getValue() ?? [];
            $escudoLocal = '';
            $escudoVisitante = '';

            foreach ($equipos as $eq) {
                if ($eq['nombre'] === $request->local) $escudoLocal = $eq['escudo'] ?? '';
                if ($eq['nombre'] === $request->visitante) $escudoVisitante = $eq['escudo'] ?? '';
            }

            // 3. Validar choque de horarios en el servidor (Seguridad extra)
            $nuevoInicio = \Carbon\Carbon::parse($request->fecha . ' ' . $request->hora);
            $nuevoFin = $nuevoInicio->copy()->addMinutes(100);
            
            $partidosExistentes = $this->database->getReference('partidos')->getValue() ?? [];

            foreach ($partidosExistentes as $p) {
                // Solo comparamos si es el mismo campo y la misma fecha
                if ($p['campo_id'] === $request->campo_id && $p['fecha'] === $request->fecha) {
                    $pInicio = \Carbon\Carbon::parse($p['fecha'] . ' ' . $p['hora']);
                    $pFin = $pInicio->copy()->addMinutes(100);

                    // Verificar si se traslapan
                    if ($nuevoInicio->lt($pFin) && $nuevoFin->gt($pInicio)) {
                        return response()->json(['error' => 'La cancha ya está ocupada en ese rango de tiempo.'], 422);
                    }
                }
            }

            // 4. Registrar en Firebase
            $id_partido = uniqid('partido_');
            $this->database->getReference('partidos/' . $id_partido)->set([
                'equipo_local'      => $request->local,
                'equipo_visitante'  => $request->visitante,
                'escudo_local'     => $escudoLocal,
                'escudo_visitante' => $escudoVisitante,
                'campo_id'          => $request->campo_id,
                'goles_local'       => 0,
                'goles_visitante'   => 0,
                'fecha'             => $request->fecha,
                'hora'              => $request->hora,
                'estatus'           => 'programado',
                'resultado_confirmado' => false
            ]);

            return response()->json(['message' => 'Partido programado exitosamente']);

        } catch (\Exception $e) {
            // Esto nos dirá en el log exactamente qué falló
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function eliminar($id)
    {
        $this->database->getReference('partidos/' . $id)->remove();
        return response()->json(['message' => 'Partido borrado']);
    }
}