<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\AuthController;
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

// Logout - clear cookie properly and redirect
Route::get('/logout', function () {
    // Clear the cookie by setting it to expire in the past
    $response = redirect('/login');
    $response->withCookie(cookie('admin_token', '', -1, '/', '', false, false));
    return $response;
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
        
        // Cargar todos los datos que necesita la vista
        $jugadores = app(JugadorController::class)->listarTodos()->getData(true);
        $equipos = app(EquipoController::class)->listar()->getData(true);
        
        // Llamar a listar sin Request
        $database = app('firebase')->createDatabase();
        $partidos = $database->getReference('partidos')->getValue() ?? [];
        
        // Pre-calcular tabla de posiciones
        $stats = [];
        $equipos = $equipos ?: [];
        
        foreach ($equipos as $id => $eq) {
            $stats[$eq['nombre']] = [
                'nombre' => $eq['nombre'],
                'escudo' => $eq['escudo'] ?? '',
                'pj' => 0, 'g' => 0, 'e' => 0, 'p' => 0, 'gf' => 0, 'gc' => 0, 'pts' => 0
            ];
        }
        
        if ($partidos) {
            foreach ($partidos as $p) {
                $esFaseRegular = isset($p['jornada']) && is_numeric($p['jornada']);
                if (($p['resultado_confirmado'] ?? false) && $esFaseRegular) {
                    $loc = $p['equipo_local'] ?? '';
                    $vis = $p['equipo_visitante'] ?? '';
                    $gl = (int)($p['goles_local'] ?? 0);
                    $gv = (int)($p['goles_visitante'] ?? 0);
                    
                    if (isset($stats[$loc]) && isset($stats[$vis])) {
                        $stats[$loc]['pj']++; $stats[$vis]['pj']++;
                        $stats[$loc]['gf'] += $gl; $stats[$loc]['gc'] += $gv;
                        $stats[$vis]['gf'] += $gv; $stats[$vis]['gc'] += $gl;
                        
                        if ($gl > $gv) {
                            $stats[$loc]['g']++; $stats[$loc]['pts'] += 3;
                            $stats[$vis]['p']++;
                        } elseif ($gl < $gv) {
                            $stats[$vis]['g']++; $stats[$vis]['pts'] += 3;
                            $stats[$loc]['p']++;
                        } else {
                            $stats[$loc]['e']++; $stats[$vis]['e']++;
                            $stats[$loc]['pts']++; $stats[$vis]['pts']++;
                        }
                    }
                }
            }
        }
        
        // Ordenar por puntos, diferencia de goles, GF
        usort($stats, function($a, $b) {
            if ($b['pts'] != $a['pts']) return $b['pts'] - $a['pts'];
            $difA = $a['gf'] - $a['gc'];
            $difB = $b['gf'] - $b['gc'];
            if ($difB != $difA) return $difB - $difA;
            return $b['gf'] - $a['gf'];
        });
        
        // Prevent browser caching
        return response()->view('welcome', [
            'jugadores' => $jugadores,
            'tablaPosiciones' => $stats
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, proxy-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    } catch (\Exception $e) {
        // Token inválido o expirado - redirigir
        return redirect('/login');
    }
});