<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class CampoController extends Controller {
    private $database;
    public function __construct() { $this->database = app('firebase')->createDatabase(); }


    public function listar() {
        try {
            // Obtenemos los campos de la rama 'campos'
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
            'lugar' => $request->lugar
        ]);
        return response()->json(['message' => 'Campo registrado']);
    }

    public function eliminar($id) {
        $this->database->getReference('campos/' . $id)->remove();
        return response()->json(['message' => 'Campo eliminado']);
    }
}