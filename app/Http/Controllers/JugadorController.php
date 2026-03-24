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

    public function registrar(Request $request)
    {
        // 1. Validamos que lleguen los datos del formulario
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string',
            'telefono' => 'required|numeric',
            'equipo'   => 'required|string',
            'edad'     => 'required|numeric',
            'direccion'=> 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            // 2. Definimos el path manualmente (jugadores / numero de telefono)
            // Esto es lo que faltaba y hacía que fallara el registro
            $path = 'jugadores/' . $request->telefono;

            $this->database->getReference($path)->set([
                'nombre'           => $request->nombre,
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

    public function listarTodos()
    {
        // NO TOCAMOS ESTO: Se queda como tú lo tenías para que tu tabla funcione
        $jugadores = $this->database->getReference('jugadores')->getValue();
        return response()->json($jugadores);
    }

    public function eliminar($telefono)
    {
        $this->database->getReference('jugadores/' . $telefono)->remove();
        return response()->json(['message' => 'Jugador eliminado correctamente']);
    }

    public function actualizar(Request $request, $telefono)
    {
        $validator = Validator::make($request->all(), [
            'nombre'    => 'required|string',
            'equipo'    => 'required|string',
            'edad'      => 'required|numeric',
            'direccion' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Datos inválidos'], 400);
        }

        try {
            $this->database->getReference('jugadores/' . $telefono)->update([
                'nombre'    => $request->nombre,
                'equipo'    => $request->equipo,
                'edad'      => $request->edad,
                'direccion' => $request->direccion
            ]);

            return response()->json(['message' => '¡Jugador actualizado!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}