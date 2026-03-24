<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class FirebaseTestController extends Controller
{
    public function testConnection()
    {
        $database = app('firebase')->createDatabase();
    
        // Referencia a un nodo de prueba
        $reference = $database->getReference('test_connection');
    
        // Insertamos algo con un timestamp
        $reference->set([
            'last_check' => now()->toDateTimeString(),
            'message' => '¡Hola desde Laravel en Codespaces!',
            'user' => 'Marco'
        ]);

        // Leemos lo que acabamos de escribir para confirmar
        $value = $reference->getValue();

        return response()->json([
            'status' => 'Conectado y Escrito con éxito',
            'data_from_firebase' => $value
        ]);
    }
}