<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gol Center - Admin Login</title>
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
            <h1 class="text-2xl font-black text-white">GOL CENTER</h1>
            <p class="text-slate-400 text-sm mt-2">Acceso Administrador</p>
        </div>

        <form id="login-form" class="space-y-6">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Email</label>
                <input type="email" id="email" required
                    class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="admin@ golcenter.com">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Contraseña</label>
                <input type="password" id="password" required
                    class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="••••••••">
            </div>

            <div id="error-msg" class="hidden bg-rose-500/20 border border-rose-500/50 rounded-lg p-3 text-rose-400 text-sm text-center"></div>

            <button type="submit" id="login-btn"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors">
                INICIAR SESIÓN
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="/" class="text-slate-500 hover:text-white text-sm">← Volver al inicio</a>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('login-btn');
            const errorMsg = document.getElementById('error-msg');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            btn.disabled = true;
            btn.textContent = 'Verificando...';
            errorMsg.classList.add('hidden');
            
            try {
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Error al iniciar sesión');
                }
                
                if (data.token) {
                    // El token también se guarda en cookie desde el servidor
                    localStorage.setItem('admin_token', data.token);
                    window.location.href = '/admin';
                } else {
                    throw new Error('No se recibió token');
                }
                
            } catch (err) {
                errorMsg.textContent = err.message;
                errorMsg.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'INICIAR SESIÓN';
            }
        });
    </script>
</body>
</html>