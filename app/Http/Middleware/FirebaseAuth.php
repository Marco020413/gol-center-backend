<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirebaseAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Obtener el token del Header (Authorization: Bearer <token>)
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'No se proporcionó un token de Firebase'], 401);
        }

        try {
            // 2. Usamos el servicio que registramos en el FirebaseServiceProvider
            $auth = app('firebase.auth');
            $verifiedIdToken = $auth->verifyIdToken($token);
            
            // Opcional: Guardar el UID del admin/árbitro por si lo necesitas luego
            $request->attributes->set('firebase_user_id', $verifiedIdToken->claims()->get('sub'));

            return $next($request);
        } catch (\Exception $e) {
            // Si el token expiró, es falso o está mal formado
            return response()->json([
                'error' => 'Token inválido o expirado',
                'debug' => $e->getMessage() // Quitar esto en producción
            ], 401);
        }
    }
}