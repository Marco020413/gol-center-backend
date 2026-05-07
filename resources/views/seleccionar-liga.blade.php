<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gol Center - Seleccionar Liga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl font-black text-white">G</span>
            </div>
            <h1 class="text-2xl font-black text-white">SELECCIONAR LIGA</h1>
            <p class="text-slate-400 text-sm mt-2">Elige la liga que deseas administrar</p>
        </div>

        <div id="liga-selector" class="space-y-4">
            <button onclick="seleccionarLiga('710268', 'Liga Principal')" 
                    class="w-full bg-slate-800 border border-slate-700 rounded-xl p-4 text-left hover:bg-slate-700 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-white">Liga Principal</h3>
                        <p class="text-slate-400 text-sm">ID: 710268</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded bg-green-500/20 text-green-400">
                        activa
                    </span>
                </div>
            </button>

            <button onclick="seleccionarLiga('970440', 'Liga Secundaria')" 
                    class="w-full bg-slate-800 border border-slate-700 rounded-xl p-4 text-left hover:bg-slate-700 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-white">Liga Secundaria</h3>
                        <p class="text-slate-400 text-sm">ID: 970440</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded bg-green-500/20 text-green-400">
                        activa
                    </span>
                </div>
            </button>
        </div>

        <div class="text-center mt-6">
            <a href="/logout" class="text-slate-500 hover:text-white text-sm">← Cerrar Sesión</a>
        </div>
    </div>

    <script>
        function seleccionarLiga(ligaId, nombreLiga) {
            localStorage.setItem('current_liga_id', ligaId);
            localStorage.setItem('current_liga_nombre', nombreLiga);
            window.location.href = '/admin';
        }
    </script>
</body>
</html>
