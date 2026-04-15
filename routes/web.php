<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\PartidoController;
use App\Http\Controllers\EquipoController;

Route::get('/', function () {
    return file_get_contents(public_path('index.html'));
});

Route::get('/login', function () {
    return view('login'); 
});

// Login como ruta web (sin CSRF)
Route::post('/login', function () {
    try {
        $request = request();
        $email = $request->input('email');
        $password = $request->input('password');
        
        $auth = app('firebase.auth');
        $signInResult = $auth->signInWithEmailAndPassword($email, $password);
        $idToken = $signInResult->idToken();
        
        $cookie = Cookie::make('admin_token', $idToken, 60, '/', '', false, false);
        
        return response()->json([
            'token' => $idToken,
            'message' => 'Login exitoso'
        ])->withCookie($cookie);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Credenciales inválidas: ' . $e->getMessage()
        ], 401);
    }
})->withoutMiddleware('Illuminate\Foundation\Http\Middleware\VerifyCsrfToken');

// Logout
Route::get('/logout', function () {
    $response = redirect('/login');
    $response->withCookie(cookie('admin_token', '', -1, '/', '', false, false));
    return $response;
});

// Admin route - OPTIMIZADO: Carga mínima inicial, datos vía AJAX
Route::get('/admin', function () {
    $token = request()->cookie('admin_token') ?: request()->bearerToken();
    $cookieHeader = request()->header('Cookie');
    
    if (!$token && $cookieHeader) {
        preg_match('/admin_token=([^;]+)/', $cookieHeader, $matches);
        if (isset($matches[1])) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        return redirect('/login');
    }
    
    try {
        $auth = app('firebase.auth');
        $verifiedIdToken = $auth->verifyIdToken($token);
        
        // OPTIMIZADO: No cargar datos aquí - el JavaScript los cargará vía AJAX
        // Esto reduce el tiempo de respuesta inicial drásticamente
        return response()->view('welcome', [
            'jugadores' => [],
            'tablaPosiciones' => []
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=5',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    } catch (\Exception $e) {
        return redirect('/login');
    }
});