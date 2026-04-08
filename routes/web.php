<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\AuthController;

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
        
        // Guardar token en cookie (más confiable que localStorage)
        return response()->json([
            'token' => $idToken,
            'message' => 'Login exitoso'
        ])->cookie('admin_token', $idToken, 60, '/', null, false, false);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Credenciales inválidas: ' . $e->getMessage()
        ], 401);
    }
});

// Logout
Route::post('/logout', function () {
    return response()->json(['message' => 'Logout'])->cookie('admin_token', '', -1);
});

// Admin - verificar autenticación desde cookie o header
Route::get('/admin', function () {
    // Intentar obtener token de cookie o header
    $token = request()->cookie('admin_token') ?: request()->bearerToken();
    
    if (!$token) {
        return redirect('/login');
    }
    
    try {
        $auth = app('firebase.auth');
        $verifiedIdToken = $auth->verifyIdToken($token);
        
        $jugadores = app(JugadorController::class)->listarTodos()->getData(true);
        return view('welcome', ['jugadores' => $jugadores]);
    } catch (\Exception $e) {
        return redirect('/login');
    }
});