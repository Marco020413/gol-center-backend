<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gol Center - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <style>
        .tab-pane { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans antialiased">
    
    <header class="w-full bg-slate-900/80 border-b border-slate-800 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="size-12 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-900/20 overflow-hidden text-white font-bold text-2xl">
                    <img src="https://cdn-icons-png.flaticon.com/512/5323/5323982.png" alt="Escudo" class="size-10 object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white uppercase">GOL <span class="text-blue-500">CENTER</span></h1>
                    <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold">Panel de Administración</p>
                </div>
            </div>
            
            <nav class="flex gap-4">
                <span class="text-sm text-slate-400 self-center hidden sm:block">Bienvenido, <strong>Admin</strong></span>
                <button class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-semibold transition shadow-md shadow-blue-900/40">
                    Cerrar Sesión
                </button>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="text-center py-10 text-slate-600 text-xs border-t border-slate-900 mt-10">
        &copy; 2026 Gol Center | Ciberseguridad & Desarrollo UT Tecámac
    </footer>

    <script>
    // Variables globales para el estado
    let editMode = false;
    let editTelefono = null;

    document.addEventListener('DOMContentLoaded', () => {
        window.modalJugador = document.getElementById('modalJugador');
        window.modalEquipo = document.getElementById('modalEquipo');
        window.formJugador = document.getElementById('formRegistroJugador');
        window.formEquipo = document.getElementById('formRegistroEquipo');

        // UNIFICADO: Procesa tanto Registro como Edición
        if(window.formJugador) {
            window.formJugador.onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnGuardar');
                btn.innerText = 'Procesando...'; btn.disabled = true;

                const formData = new FormData(window.formJugador);
                const data = Object.fromEntries(formData.entries());

                // Si estamos editando, usamos el ID que guardamos al dar clic en el lápiz
                const url = editMode ? `/api/admin/jugadores/actualizar/${editTelefono}` : '/api/admin/jugadores/registrar';
                const method = editMode ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: { 
                            'Content-Type': 'application/json', 
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (response.ok) { 
                        alert(editMode ? '✅ ¡Actualizado!' : '✅ ¡Registrado!'); 
                        location.reload(); 
                    } else {
                        alert("Error: " + (result.error || "No se pudo completar"));
                        btn.innerText = 'Registrar Jugador :)'; btn.disabled = false;
                    }
                } catch (error) { 
                    console.error('Error:', error); 
                    alert('❌ Error de conexión');
                    btn.disabled = false;
                }
            };
        }

        // Registro de Equipos
        if(window.formEquipo) {
            window.formEquipo.onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnGuardarEquipo');
                btn.innerText = 'Creando...'; btn.disabled = true;
                const data = Object.fromEntries(new FormData(window.formEquipo));
                try {
                    const response = await fetch('/api/admin/equipos/registrar', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    if (response.ok) { alert('¡Equipo creado!'); location.reload(); }
                } catch (error) { console.error('Error:', error); }
            };
        }
    });

    // Funciones Globales
    function abrirModal() { window.modalJugador.classList.replace('hidden', 'flex'); cargarEquipos(); }
    
    function cerrarModal() { 
        window.modalJugador.classList.replace('flex', 'hidden'); 
        editMode = false;
        window.formJugador.reset();
        window.formJugador.telefono.disabled = false;
        document.querySelector('#modalJugador h3').innerText = 'Nuevo Jugador';
        document.getElementById('btnGuardar').innerText = 'Registrar Jugador :)';
    }

    function abrirModalEquipo() { window.modalEquipo.classList.replace('hidden', 'flex'); }
    function cerrarModalEquipo() { window.modalEquipo.classList.replace('flex', 'hidden'); }

    async function editarJugador(telefono, nombre, equipo, edad, direccion) {
        editMode = true;
        editTelefono = telefono;
        
        document.querySelector('#modalJugador h3').innerText = 'Editar Jugador';
        document.getElementById('btnGuardar').innerText = 'Actualizar Datos';
        
        const f = window.formJugador;
        f.nombre.value = nombre;
        f.telefono.value = telefono;
        f.telefono.disabled = true; 
        f.equipo.value = equipo;
        f.edad.value = edad;
        f.direccion.value = direccion;

        abrirModal();
    }

    async function eliminarJugador(telefono) {
        if (!confirm('¿Seguro que quieres eliminar?')) return;
        try {
            const response = await fetch(`/api/admin/jugadores/eliminar/${telefono}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (response.ok) { alert('🗑️ Eliminado'); location.reload(); }
        } catch (error) { alert('❌ Error'); }
    }

    async function cargarEquipos() {
        const select = document.getElementById('selectEquipos');
        if(!select) return;
        try {
            const response = await fetch('/api/equipos');
            const equipos = await response.json();
            const currentVal = select.value;
            select.innerHTML = '<option value="">Selecciona un equipo</option>';
            for (const id in equipos) {
                const option = document.createElement('option');
                option.value = equipos[id].nombre;
                option.textContent = equipos[id].nombre;
                select.appendChild(option);
            }
            if(editMode) select.value = currentVal;
        } catch (e) { console.error("Error:", e); }
    }

    function changeTab(tabName) {
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('text-blue-500', 'border-b-2', 'border-blue-500');
            b.classList.add('text-slate-500');
        });
        const target = document.getElementById('content-' + tabName);
        if(target) target.classList.remove('hidden');
        if(event.currentTarget) {
            event.currentTarget.classList.add('text-blue-500', 'border-b-2', 'border-blue-500');
        }
    }
</script>
</body>
</html>