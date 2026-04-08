<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Datos inválidos', 'details' => $validator->errors()], 422);
        }

        try {
            $auth = app('firebase.auth');
            
            $signInResult = $auth->signInWithEmailAndPassword(
                $request->email,
                $request->password
            );
            
            $idToken = $signInResult->idToken();
            
            return response()->json([
                'token' => $idToken,
                'message' => 'Login exitoso'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Credenciales inválidas: ' . $e->getMessage()
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        return response()->json(['message' => 'Logout exitoso']);
    }
}