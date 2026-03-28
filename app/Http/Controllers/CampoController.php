<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class CampoController extends Controller {
    private $database;
    public function __construct() { $this->database = app('firebase')->createDatabase(); }
public function index() { 
        try {
            $campos = $this->database->getReference('campos')->getValue() ?? [];
            return response()->json($campos);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function crear(Request $request) {
        $id = uniqid('campo_');
        $this->database->getReference('campos/' . $id)->set([
            'nombre' => $request->nombre,
            'lugar' => $request->lugar,
            'estado' => 'disponible' // Estado inicial por defecto
        ]);
        return response()->json(['message' => 'Campo registrado']);
    }

    // NUEVO MÉTODO: Actualizar Cancha
    public function actualizar(Request $request, $id) {
        try {
            $campoRef = $this->database->getReference('campos/' . $id);
            $partidosRef = $this->database->getReference('partidos');
            $partidos = $partidosRef->getValue() ?? [];

            // Si intentan poner mantenimiento y NO han enviado nueva sede aún
            if ($request->estado === 'mantenimiento' && !$request->has('nueva_sede_id')) {
                $conflictos = [];
                foreach ($partidos as $pId => $p) {
                    // Filtramos partidos programados o en curso en esta cancha
                    if ($p['campo_id'] === $id && !($p['resultado_confirmado'] ?? false)) {
                        $conflictos[] = [
                            'id' => $pId,
                            'resumen' => "{$p['equipo_local']} vs {$p['equipo_visitante']}",
                            'fecha' => $p['fecha'],
                            'hora' => $p['hora']
                        ];
                    }
                }

                if (count($conflictos) > 0) {
                    return response()->json([
                        'error' => 'conflictos_mantenimiento',
                        'partidos' => $conflictos
                    ], 422);
                }
            }

            // Si el admin ya eligió nueva sede en el modal, reasignamos antes de cambiar a mantenimiento
            if ($request->has('nueva_sede_id')) {
                $partidos = $partidosRef->getValue() ?? []; // Refrescamos datos
                foreach ($partidos as $pId => $p) {
                    if ($p['campo_id'] === $id && !($p['resultado_confirmado'] ?? false)) {
                        $partidosRef->getChild($pId)->update(['campo_id' => $request->nueva_sede_id]);
                    }
                }
            }

            // Finalmente actualizamos los datos de la sede (incluyendo el estado)
            $campoRef->update([
                'nombre' => $request->nombre,
                'lugar' => $request->lugar,
                'estado' => $request->estado
            ]);

            return response()->json(['message' => 'Actualización exitosa']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

   public function eliminar(Request $request, $id) {
        try {
            $partidosRef = $this->database->getReference('partidos');
            $partidos = $partidosRef->getValue() ?? [];
            
            $conflictos = [];
                foreach ($partidos as $pId => $p) {
                    if ($p['campo_id'] === $id && !($p['resultado_confirmado'] ?? false)) {
                        $conflictos[] = [
                            'id' => $pId,
                            'resumen' => "{$p['equipo_local']} vs {$p['equipo_visitante']}",
                            'fecha' => $p['fecha'],
                            'hora' => $p['hora']
                        ];
                    }
                }

            if (count($conflictos) > 0 && !$request->has('nueva_sede_id')) {
                return response()->json([
                    'error' => 'conflictos_detectados',
                    'partidos' => $conflictos,
                    'cantidad' => count($conflictos)
                ], 422);
            }

            if ($request->has('nueva_sede_id')) {
                foreach ($conflictos as $conf) {
                    $partidosRef->getChild($conf['id'])->update(['campo_id' => $request->nueva_sede_id]);
                }
            }

            $this->database->getReference('campos/' . $id)->remove();
            return response()->json(['message' => 'Cancha eliminada y partidos reasignados']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}