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
        &copy; 2026 Gol Center | Hilario
    </footer>

    <script>
    // Variables globales para el estado
    let editMode = false;
    let editTelefono = null;
    let cachePartidos = [];
    let cachePartidosLista = []; 

    document.addEventListener('DOMContentLoaded', () => {
        window.modalJugador = document.getElementById('modalJugador');
        window.modalEquipo = document.getElementById('modalEquipo');
        window.formJugador = document.getElementById('formRegistroJugador');
        window.formEquipo = document.getElementById('formRegistroEquipo');

        // UNIFICADO: Procesa tanto Registro como Edición
        if(window.formJugador) {
            // 1. Modifica el onsubmit para incluir el número
            window.formJugador.onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnGuardar');
                const msgError = document.getElementById('mensajeError'); // Usamos tu div de error
                
                btn.innerText = 'Procesando...'; 
                btn.disabled = true;
                if(msgError) msgError.classList.add('hidden');

                const data = {
                    nombre: window.formJugador.nombre.value,
                    edad: window.formJugador.edad.value,
                    direccion: window.formJugador.direccion.value,
                    telefono: editMode ? editTelefono : window.formJugador.telefono.value,
                    equipo: window.formJugador.equipo.value,
                    numero: window.formJugador.numero.value
                };

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
                        alert(editMode ? '✅ ¡Actualizado correctamente!' : '✅ ¡Jugador registrado!'); 
                        location.reload(); 
                    } else {
                        // AQUÍ CAPTURAMOS EL AVISO DE DORSAL OCUPADO
                        const mensaje = result.error || "Error al procesar la solicitud";
                        alert("⚠️ " + mensaje);
                        
                        // También lo mostramos en el cuadrito rojo del modal si existe
                        if(msgError) {
                            msgError.innerText = mensaje;
                            msgError.classList.remove('hidden');
                        }
                        
                        btn.innerText = editMode ? 'Actualizar Datos' : 'Registrar Jugador :)';
                        btn.disabled = false;
                    }
                } catch (error) { 
                    console.error('Error:', error); 
                    alert('❌ Error crítico de conexión');
                    btn.disabled = false;
                }
            };

            // 2. Modifica la función editar para recibir el dorsal (#)
            async function editarJugador(telefono, nombre, equipo, edad, direccion, numero) {
                editMode = true;
                editTelefono = telefono;
                
                document.querySelector('#modalJugador h3').innerText = 'Editar Jugador';
                const f = window.formJugador;
                f.nombre.value = nombre;
                f.telefono.value = telefono;
                f.telefono.disabled = true; 
                f.edad.value = edad;
                f.direccion.value = direccion;
                f.numero.value = numero || ''; // <--- CARGA EL DORSAL EN EL MODAL

                await cargarEquipos(); 
                f.equipo.value = equipo; 
                abrirModal();
            }
        }

        // Registro de Equipos
        if(window.formEquipo) {
            window.formEquipo.onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnGuardarEquipo');
                btn.innerText = 'Subiendo...'; 
                btn.disabled = true;

                // IMPORTANTE: Usamos FormData directo para que incluya archivos
                const data = new FormData(window.formEquipo);

                try {
                    const response = await fetch('/api/admin/equipos/registrar', {
                        method: 'POST',
                        body: data, // Enviamos el FormData directamente, NO JSON.stringify
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    if (response.ok) { 
                        alert('🏆 Equipo creado exitosamente'); 
                        location.reload(); 
                    } else {
                        alert('❌ Error al crear equipo');
                        btn.innerText = 'Guardar Equipo';
                        btn.disabled = false;
                    }
                } catch (error) { 
                    console.error('Error:', error);
                    alert('❌ Error de conexión');
                    btn.disabled = false;
                }
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
    function cerrarModalEquipo() { 
        window.modalEquipo.classList.replace('flex', 'hidden'); 
        editMode = false; 
    }

    async function editarJugador(telefono, nombre, equipo, edad, direccion, numero) {
        editMode = true;
        editTelefono = telefono;
        
        document.querySelector('#modalJugador h3').innerText = 'Editar Jugador';
        document.getElementById('btnGuardar').innerText = 'Actualizar Datos';
        
        const f = window.formRegistroJugador; // Asegúrate que el ID coincida
        f.nombre.value = nombre;
        f.telefono.value = telefono;
        f.telefono.disabled = true; 
        f.edad.value = edad;
        f.direccion.value = direccion;
        f.numero.value = numero; 

        await cargarEquipos(); 
        f.equipo.value = equipo; 

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
        const selectFiltro = document.getElementById('filtroEquipo');
        if(!select) return;
        try {
            const response = await fetch('/api/equipos');
            const equipos = await response.json();
            const currentVal = select.value;
            select.innerHTML = '<option value="Libre">-- AGENTE LIBRE --</option>';
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

        // Usamos window.event para mayor compatibilidad o verificamos si existe
        if(window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('text-blue-500', 'border-b-2', 'border-blue-500');
        }

        // Disparar carga de datos según la pestaña
        if(tabName === 'partidos') cargarPartidosCards();
        if(tabName === 'equipos_gest') cargarGestionEquipos();
    }

    function abrirModalEquipo() { 
        window.modalEquipo.classList.replace('hidden', 'flex'); 
        cargarGaleriaEscudos(); 
    }

    async function cargarGaleriaEscudos() {
        const contenedor = document.getElementById('contenedorEscudos');
        if(!contenedor) return;

        try {
            const response = await fetch('/api/equipos/escudos');
            const escudos = await response.json();
            
            contenedor.innerHTML = ''; 

            if (escudos.length === 0) {
                contenedor.innerHTML = '<p class="col-span-4 text-[10px] text-slate-500 italic py-4">No hay escudos. ¡Sube el primero!</p>';
            }

            escudos.forEach((url) => {
                const nombreArchivo = url.split('/').pop();
                // Limpiamos los números del nombre para que se vea bien
                const nombreLimpio = nombreArchivo.includes('_') ? nombreArchivo.split('_').slice(1).join('_') : nombreArchivo;

                const label = document.createElement('label');
                label.className = 'cursor-pointer group';
                label.innerHTML = `
                    <input type="radio" name="escudo_url" value="${url}" class="hidden peer" onchange="mostrarPreview('${url}', '${nombreLimpio}')">
                    <img src="${url}" class="size-12 mx-auto object-contain peer-checked:border-2 border-blue-500 rounded-lg bg-white/10 hover:scale-110 transition">
                    <p class="text-[7px] mt-1 uppercase truncate text-slate-500">${nombreLimpio}</p>
                `;
                contenedor.appendChild(label);
            });
        } catch (e) { 
            console.error("Error al cargar escudos:", e); 
            contenedor.innerHTML = '<p class="col-span-4 text-red-500 text-[8px]">Error al conectar con el servidor</p>';
        }
    }

    // Nueva función de previsualización
    function mostrarPreview(url, nombre) {
        const contenedor = document.getElementById('previewContenedor');
        const img = document.getElementById('imgPreview');
        const txt = document.getElementById('namePreview');
        
        contenedor.classList.remove('hidden');
        contenedor.classList.add('flex');
        img.src = url;
        txt.innerText = nombre;
    }
    
    // CORRECCIÓN: Función para abrir el modal en modo EDICIÓN
    async function editarEquipo(id, nombre, escudo) {
        editMode = true;
        document.getElementById('tituloModalEquipo').innerText = 'Editar Equipo';
        document.getElementById('equipo_id_edit').value = id; // El ID oculto que ya tienes en el HTML
        document.getElementById('nombreEquipoInput').value = nombre;
        
        // Mostramos el escudo actual
        mostrarPreview(escudo, nombre);
        
        abrirModalEquipo();
    }

    async function registrarNuevoEquipo() {
        const form = document.getElementById('formRegistroEquipo');
        const btn = document.getElementById('btnGuardarEquipo');
        
        btn.innerText = 'Procesando...';
        btn.disabled = true;

        const data = new FormData(form);

        try {
            const response = await fetch('/api/admin/equipos/registrar', {
                method: 'POST',
                body: data,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (response.ok) {
                alert('✅ Equipo guardado con éxito');
                location.reload();
            } else {
                alert('❌ Error al guardar');
                btn.innerText = 'Guardar Equipo';
                btn.disabled = false;
            }
        } catch (e) {
            alert('❌ Error de conexión');
            btn.disabled = false;
        }
    }

    // Limpiar el modal al abrir para "Nuevo Equipo"
        function abrirModalEquipo() { 
            if(!editMode) {
                document.getElementById('formRegistroEquipo').reset();
                document.getElementById('equipo_id_edit').value = '';
                document.getElementById('tituloModalEquipo').innerText = 'Nuevo Equipo';
                document.getElementById('previewContenedor').classList.add('hidden');
            }
            window.modalEquipo.classList.replace('hidden', 'flex'); 
            cargarGaleriaEscudos(); 
        }

    // 1. CARGAR LISTA (Actualizado para que el botón de editar funcione con tu HTML)
    async function cargarGestionEquipos() {
        const contenedor = document.getElementById('listaEquiposCards');
        if(!contenedor) return;
        try {
            const response = await fetch('/api/equipos');
            const equipos = await response.json();
            contenedor.innerHTML = '';
            for (const id in equipos) {
                const eq = equipos[id];
                contenedor.innerHTML += `
                    <div class="bg-slate-900 border border-slate-800 p-4 rounded-xl flex items-center justify-between shadow-lg">
                        <div class="flex items-center gap-4">
                            <img src="${eq.escudo}" class="size-12 object-contain bg-white/5 rounded-lg border border-slate-700">
                            <p class="font-bold text-white text-sm uppercase">${eq.nombre}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="editarEquipo('${id}', '${eq.nombre}', '${eq.escudo}')" class="text-blue-500 hover:bg-blue-500/10 p-2 rounded-lg transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button onclick="eliminarEquipoExhaustivo('${id}', '${eq.nombre}')" class="text-red-500 hover:bg-red-500/10 p-2 rounded-lg transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                `;
            }
        } catch (e) { console.error(e); }
    }

    // 2. EDITAR EQUIPO (Corregido el error de innerText)
    async function editarEquipo(id, nombre, escudo) {
        editMode = true;
        const titulo = document.getElementById('tituloModalEquipo');
        if(titulo) titulo.innerText = 'Editar Equipo';
        
        document.getElementById('equipo_id_edit').value = id;
        document.getElementById('nombreEquipoInput').value = nombre;
        
        // Mostramos el escudo actual en la vista previa
        mostrarPreview(escudo, nombre);
        
        window.modalEquipo.classList.replace('hidden', 'flex');
        cargarGaleriaEscudos();
    }

    // 3. ELIMINAR SEGURO (Mantenemos tu versión exhaustiva)
    async function eliminarEquipoExhaustivo(id, nombre) {
        if(!confirm(`⚠️ ¿Seguro que quieres borrar a "${nombre}"?`)) return;
        const validacion = prompt(`Para confirmar, escribe el nombre del equipo: ${nombre}`);
        
        if (validacion === nombre) {
            try {
                const response = await fetch(`/api/admin/equipos/eliminar/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if(response.ok) { alert('🗑️ Equipo eliminado'); location.reload(); }
            } catch (e) { alert('Error de conexión'); }
        } else {
            alert('❌ Nombre incorrecto.');
        }
    }


    // Modifica tu función changeTab para cargar los equipos al entrar al tab
    function changeTab(tabName) {
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('text-blue-500', 'border-b-2', 'border-blue-500');
            b.classList.add('text-slate-500');
        });

        const target = document.getElementById('content-' + tabName);
        if(target) target.classList.remove('hidden');

        // Resaltar el botón presionado de forma segura
        if(window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('text-blue-500', 'border-b-2', 'border-blue-500');
        }

        // DISPARAR CARGAS AUTOMÁTICAS
        if(tabName === 'partidos') cargarPartidosCards();
        if(tabName === 'equipos_gest') cargarGestionEquipos();
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.modalJugador = document.getElementById('modalJugador');
        window.modalEquipo = document.getElementById('modalEquipo');
        window.abrirModalCrearPartido = function() {
            const modal = document.getElementById('modalCrearPartido');
            if (modal) {
                modal.classList.replace('hidden', 'flex');
                llenarSelectsEquipos(); 
                if (typeof llenarSelectsCampos === 'function') {
                    llenarSelectsCampos(); 
                }
            }
        };

        // CARGA INICIAL: Traemos los partidos de una vez para que estén listos
        cargarPartidosCards();
        cargarGestionEquipos();
    });

    function filtrarTabla() {
        const busqueda = document.getElementById('busquedaJugador').value.toLowerCase().trim();
        const equipoFiltro = document.getElementById('filtroEquipo').value; // "Libre" o nombre del equipo
        const orden = document.getElementById('ordenarPor').value;
        
        const tablaBody = document.querySelector('#content-jugadores tbody');
        if (!tablaBody) return;

        const filas = Array.from(tablaBody.querySelectorAll('tr'));

        filas.forEach(fila => {
            // 1. Extraer datos de los data-fields
            const nombre = fila.querySelector('[data-field="nombre"]')?.innerText.toLowerCase() || "";
            const telefono = fila.querySelector('[data-field="telefono"]')?.innerText.toLowerCase() || "";
            
            // LEEMOS EL ATRIBUTO DATA-VALOR QUE PUSIMOS EN EL PASO ANTERIOR
            const equipoCelda = fila.querySelector('[data-field="equipo"]');
            const valorEquipo = equipoCelda ? equipoCelda.getAttribute('data-valor') : "";

            // 2. Lógica de Búsqueda
            const coincideBusqueda = nombre.includes(busqueda) || telefono.includes(busqueda);
            
            // 3. Lógica de Filtro de Equipo
            let coincideEquipo = true;
            if (equipoFiltro === "Libre") {
                // Ahora comparamos contra el valor puro "Libre"
                coincideEquipo = (valorEquipo === "Libre");
            } else if (equipoFiltro !== "") {
                coincideEquipo = (valorEquipo === equipoFiltro);
            }

            fila.style.display = (coincideBusqueda && coincideEquipo) ? "" : "none";
        });

        // --- Mantenemos tu lógica de ordenamiento que ya funcionaba ---
        const filasVisibles = filas.filter(f => f.style.display !== "none");
        filasVisibles.sort((a, b) => {
            if (orden === 'goles') return (parseInt(b.cells[3].innerText) || 0) - (parseInt(a.cells[3].innerText) || 0);
            if (orden === 'pj') return (parseInt(b.cells[2].innerText) || 0) - (parseInt(a.cells[2].innerText) || 0);
            if (orden === 'dorsal') {
                const numA = parseInt(a.querySelector('.bg-blue-600\\/20')?.innerText) || 0;
                const numB = parseInt(b.querySelector('.bg-blue-600\\/20')?.innerText) || 0;
                return numA - numB;
            }
            return a.querySelector('[data-field="nombre"]').innerText.localeCompare(b.querySelector('[data-field="nombre"]').innerText);
        }).forEach(f => tablaBody.appendChild(f));
    }
        

   function abrirModalCrearPartido() {
        const modal = document.getElementById('modalCrearPartido');
        if (modal) {
            modal.classList.replace('hidden', 'flex');
            llenarSelectsEquipos(); 
            llenarSelectsCampos();
        }
    }

    function cerrarModalCrearPartido() {
        const modal = document.getElementById('modalCrearPartido');
        if (modal) {
            modal.classList.replace('flex', 'hidden');
            document.getElementById('formCrearPartido').reset();
        }
    }

    async function llenarSelectsEquipos() {
        try {
            const res = await fetch('/api/equipos');
            const equipos = await res.json();
            const selects = [document.getElementById('selectLocal'), document.getElementById('selectVisitante')];
            
            selects.forEach(s => {
                if (!s) return;
                s.innerHTML = '<option value="">Selecciona un club</option>';
                for (const id in equipos) {
                    const opt = document.createElement('option');
                    opt.value = equipos[id].nombre;
                    opt.textContent = equipos[id].nombre.toUpperCase();
                    s.appendChild(opt);
                }
            });
        } catch (e) { console.error("Error llenando selects:", e); }
    }

    // Usamos addEventListener en lugar de onsubmit directo para mayor estabilidad

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Inicializar referencias de modales
        window.modalJugador = document.getElementById('modalJugador');
        window.modalEquipo = document.getElementById('modalEquipo');
        window.modalCrearPartido = document.getElementById('modalCrearPartido');
        window.modalActualizarMarcador = document.getElementById('modalActualizarMarcador');

        // 2. ACTIVAR SENSORES DE AGENDA 
        // Escuchamos cambios en Campo, Fecha y Hora para actualizar la tabla visual
        const selectCampos = document.getElementById('selectCampos');
        const inputFecha = document.querySelector('#formCrearPartido input[name="fecha"]');
        const inputHora = document.querySelector('#formCrearPartido input[name="hora"]');

        if(selectCampos) selectCampos.addEventListener('change', window.verificarConflictosInteligentes);
        if(inputFecha) inputFecha.addEventListener('change', window.verificarConflictosInteligentes);
        if(inputHora) inputHora.addEventListener('change', window.verificarConflictosInteligentes);

        // 3. --- LISTENER CREAR PARTIDO (VERSION INTELIGENTE) ---
        const formPartidos = document.getElementById('formCrearPartido');
        if (formPartidos) {
            formPartidos.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const data = {
                    local: document.getElementById('selectLocal').value,
                    visitante: document.getElementById('selectVisitante').value,
                    campo_id: document.getElementById('selectCampos').value,
                    fecha: formPartidos.fecha.value,
                    hora: formPartidos.hora.value
                };

                if (data.local === data.visitante) return alert("❌ No pueden jugar contra el mismo equipo");
                
                try {
                    const res = await fetch('/api/admin/partidos/crear', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await res.json(); // Obtenemos la respuesta del controlador

                    if (res.ok) { 
                        alert("⚽ " + (result.message || "Partido programado")); 
                        cerrarModalCrearPartido(); 
                        cargarPartidosCards(); 
                        // Escondemos la agenda para la próxima vez
                        document.getElementById('agendaCanchaContenedor').classList.add('hidden');
                    } else {
                        // AQUÍ SE MUESTRA EL ERROR DEL CONTROLADOR (Cancha ocupada, etc)
                        alert("🚫 NO SE PUDO CREAR:\n" + (result.error || "Error desconocido"));
                    }
                } catch (e) { 
                    alert("Error de conexión con el servidor"); 
                }
            });
        }

        // 4. --- LISTENER ACTUALIZAR RESULTADO ---
        const formActualizar = document.getElementById('formActualizarMarcador');
        if (formActualizar) {
            formActualizar.onsubmit = async (e) => {
                e.preventDefault();
                const id = document.getElementById('edit_partido_id').value;
                const checkFinal = document.getElementById('confirmar_final');
                const esFinal = checkFinal ? checkFinal.checked : false;

                if (esFinal) {
                    if (!confirm("⚠️ Al confirmar como FINALIZADO, el acta se cerrará y no podrás editar los goles después. ¿Proceder?")) return;
                }

                const data = {
                    goles_local: document.getElementById('goles_local').value,
                    goles_visitante: document.getElementById('goles_visitante').value,
                    confirmar_final: esFinal
                };

                try {
                    const res = await fetch(`/api/admin/partidos/actualizar/${id}`, {
                        method: 'PUT',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                        },
                        body: JSON.stringify(data)
                    });

                    if (res.ok) {
                        alert(esFinal ? "🔒 Acta cerrada correctamente" : "✅ Marcador actualizado");
                        cerrarModalMarcador();
                        cargarPartidosCards(); 
                    } else {
                        const err = await res.json();
                        alert("❌ " + (err.error || "Error al actualizar"));
                    }
                } catch (e) { alert("Error de conexión"); }
            };
        }

        // 5. Carga inicial de datos
        cargarPartidosCards();
        cargarGestionEquipos();
    });

    // --- FUNCIONES GLOBALES ---

    async function cargarPartidosCards() {
    const contenedor = document.getElementById('contenedorListaPartidos');
    if (!contenedor) return;

    try {
        const res = await fetch('/api/partidos');
        const partidos = await res.json();
        
        // Guardamos en la variable global convirtiendo el objeto a Array con su ID
        cachePartidosLista = Object.keys(partidos).map(id => ({
            id: id,
            ...partidos[id]
        }));

        // Mandamos a dibujar con los filtros aplicados (por defecto "todos")
        aplicarFiltrosPartidos();
    } catch (e) { console.error("Error cargando partidos:", e); }
}

    window.aplicarFiltrosPartidos = function() {
        const contenedor = document.getElementById('contenedorListaPartidos');
        const busqueda = document.getElementById('filtroEquipoPartido').value.toLowerCase().trim();
        const estatus = document.getElementById('filtroEstatusPartido').value;
        const orden = document.getElementById('ordenarPartidos').value;

        // 1. Filtrar los datos que tenemos en memoria
        let partidosFiltrados = cachePartidosLista.filter(p => {
            const coincideNombre = p.equipo_local.toLowerCase().includes(busqueda) || 
                                p.equipo_visitante.toLowerCase().includes(busqueda);
            
            let coincideEstatus = true;
            if (estatus === 'programado') coincideEstatus = (p.estatus === 'programado');
            if (estatus === 'en_curso') coincideEstatus = (p.estatus === 'en_curso');
            if (estatus === 'finalizado') coincideEstatus = (p.estatus === 'finalizado' && !p.resultado_confirmado);
            if (estatus === 'confirmado') coincideEstatus = (p.resultado_confirmado === true);

            return coincideNombre && coincideEstatus;
        });

        // 2. Ordenar por Fecha y Hora (Combinadas para precisión)
        partidosFiltrados.sort((a, b) => {
            const datetimeA = new Date(`${a.fecha}T${a.hora || '00:00'}`);
            const datetimeB = new Date(`${b.fecha}T${b.hora || '00:00'}`);
            return orden === 'recientes' ? datetimeB - datetimeA : datetimeA - datetimeB;
        });

        // 3. Dibujar en el contenedor
        contenedor.innerHTML = '';
        
        if (partidosFiltrados.length === 0) {
            contenedor.innerHTML = '<p class="text-center text-slate-600 py-10 italic">No se encontraron partidos.</p>';
            return;
        }

        partidosFiltrados.forEach(p => {
            // Lógica de colores de estatus (Tu diseño original)
            let statusHTML = '';
            if(p.resultado_confirmado) {
                statusHTML = '<span class="text-white bg-slate-700 px-2 py-0.5 rounded text-[8px] font-black uppercase">Acta Cerrada 🔒</span>';
            } else if(p.estatus === 'en_curso') {
                statusHTML = '<span class="text-green-500 animate-pulse font-black uppercase text-[9px]">En Curso 🟢</span>';
            } else if(p.estatus === 'finalizado') {
                statusHTML = '<span class="text-amber-500 font-black uppercase text-[9px]">Por Subir Acta ⚠️</span>';
            } else {
                statusHTML = '<span class="text-slate-500 font-black uppercase text-[9px]">Programado</span>';
            }

            const botonGestionar = !p.resultado_confirmado 
                ? `<button onclick="abrirActualizarMarcador('${p.id}')" class="mt-2 w-full text-[10px] bg-blue-600/10 text-blue-500 border border-blue-500/20 px-2 py-1 rounded hover:bg-blue-600 hover:text-white transition uppercase font-black">Gestionar</button>` 
                : `<span class="text-[9px] text-slate-500 italic block mt-2">Resultado Inamovible</span>`;

            // Insertar la tarjeta con tu diseño original
            contenedor.innerHTML += `
                <div class="bg-slate-900 border ${p.resultado_confirmado ? 'border-slate-800' : 'border-blue-500/20'} rounded-xl p-4 flex items-center justify-between shadow-lg mb-4">
                    <div class="flex-1 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <img src="${p.escudo_local}" class="size-7 object-contain" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5323/5323982.png'">
                                <span class="font-bold text-slate-200 text-sm uppercase">${p.equipo_local}</span>
                            </div>
                            <span class="text-xl font-black text-white">${p.goles_local}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <img src="${p.escudo_visitante}" class="size-7 object-contain" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5323/5323982.png'">
                                <span class="font-bold text-slate-200 text-sm uppercase">${p.equipo_visitante}</span>
                            </div>
                            <span class="text-xl font-black text-white">${p.goles_visitante}</span>
                        </div>
                    </div>
                    <div class="w-px h-10 bg-slate-800 mx-4"></div>
                    <div class="text-right min-w-[120px]">
                        <div class="mb-1">${statusHTML}</div>
                        <p class="text-[10px] text-slate-500 font-bold">${p.fecha} | ${p.hora || '--:--'}</p>
                        ${botonGestionar}
                    </div>
                </div>
            `;
        });
    }

    async function abrirActualizarMarcador(id) {
        try {
            const res = await fetch('/api/partidos');
            const partidos = await res.json();
            const p = partidos[id];
            const displayStatus = document.getElementById('display_estatus');
            
            if(!p) return alert("No se encontró el partido");

            document.getElementById('edit_partido_id').value = id;
            
            // Nombres de equipos
            const lblLocal = document.getElementById('edit_labelLocal');
            const lblVisit = document.getElementById('edit_labelVisitante');
            if(lblLocal) lblLocal.innerText = p.equipo_local.toUpperCase();
            if(lblVisit) lblVisit.innerText = p.equipo_visitante.toUpperCase();

            // Goles
            document.getElementById('goles_local').value = p.goles_local || 0;
            document.getElementById('goles_visitante').value = p.goles_visitante || 0;

            if(displayStatus) {
                let textoEstado = p.estatus.replace('_', ' ').toUpperCase();
                displayStatus.innerText = textoEstado;
                
                if(p.estatus === 'en_curso') {
                    displayStatus.className = "text-green-500 font-black uppercase text-xs tracking-widest bg-green-500/10 px-3 py-1 rounded-full border border-green-500/20 animate-pulse";
                } else if(p.estatus === 'finalizado') {
                    displayStatus.className = "text-amber-500 font-black uppercase text-xs tracking-widest bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/20";
                } else {
                    displayStatus.className = "text-blue-500 font-black uppercase text-xs tracking-widest bg-blue-500/10 px-3 py-1 rounded-full border border-blue-500/20";
                }
            }

            // Reset checkbox
            const checkFinal = document.getElementById('confirmar_final');
            if(checkFinal) checkFinal.checked = false;

            window.modalActualizarMarcador.classList.replace('hidden', 'flex');
        } catch (e) { console.error("Error abriendo gestor:", e); }
    }

    function cerrarModalMarcador() { window.modalActualizarMarcador.classList.replace('flex', 'hidden'); }

    // --- FUNCIÓN PARA BORRAR PARTIDO (SEGURIDAD EXTRA) ---
    async function eliminarPartido() {
        const id = document.getElementById('edit_partido_id').value;
        if (!confirm("⚠️ ¿Estás COMPLETAMENTE seguro de borrar este partido? Esta acción es irreversible.")) return;
        
        try {
            const res = await fetch(`/api/admin/partidos/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (res.ok) {
                alert("🗑️ Partido eliminado");
                cerrarModalMarcador();
                cargarPartidosCards();
            }
        } catch (e) { alert("Error al eliminar"); }
    }

    // Funciones de Pestañas y creación (Mantenlas igual)
    function changeTab(tabName) {
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('text-blue-500', 'border-b-2', 'border-blue-500');
            b.classList.add('text-slate-500');
        });
        const target = document.getElementById('content-' + tabName);
        if(target) target.classList.remove('hidden');
        if(window.event && window.event.currentTarget) window.event.currentTarget.classList.add('text-blue-500', 'border-b-2', 'border-blue-500');
        if(tabName === 'partidos') cargarPartidosCards();
    }
    
    function abrirModalCrearPartido() { window.modalCrearPartido.classList.replace('hidden', 'flex'); llenarSelectsEquipos(); }
    function cerrarModalCrearPartido() { window.modalCrearPartido.classList.replace('flex', 'hidden'); }
    
    async function llenarSelectsEquipos() {
        const res = await fetch('/api/equipos');
        const equipos = await res.json();
        const selects = [document.getElementById('selectLocal'), document.getElementById('selectVisitante')];
        selects.forEach(s => {
            if (!s) return;
            s.innerHTML = '<option value="">Selecciona club</option>';
            for (const id in equipos) {
                const opt = document.createElement('option');
                opt.value = equipos[id].nombre;
                opt.textContent = equipos[id].nombre.toUpperCase();
                s.appendChild(opt);
            }
        });
    }

        window.llenarSelectsCampos = async function() {
    try {
        const res = await fetch('/api/campos');
        const campos = await res.json();
        const select = document.getElementById('selectCampos');
        if(!select) return;
        select.innerHTML = '<option value="">Selecciona Cancha</option>';
        for (const id in campos) {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = campos[id].nombre.toUpperCase() + " (" + campos[id].lugar.toUpperCase() + ")";
            select.appendChild(opt);
        }
    } catch (e) { console.error("Error:", e); }
};

            function abrirModalCampo() { document.getElementById('modalCrearCampo').classList.replace('hidden', 'flex'); }
            function cerrarModalCampo() { document.getElementById('modalCrearCampo').classList.replace('flex', 'hidden'); document.getElementById('formCrearCampo').reset(); }

    // Guardar Cancha
document.getElementById('formCrearCampo').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('/api/admin/campos/registrar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(data)
    });
    if(res.ok) { alert("Cancha registrada"); cerrarModalCampo(); cargarCamposCards(); }
};

// Cargar Canchas en el Tab
// Busca donde dice async function cargarCamposCards() y cámbialo a esto:
window.cargarCamposCards = async function() {
    const contenedor = document.getElementById('listaCamposCards');
    if(!contenedor) return;
    try {
        const res = await fetch('/api/campos');
        const campos = await res.json();
        contenedor.innerHTML = '';
        for (const id in campos) {
            contenedor.innerHTML += `
                <div class="bg-slate-900 border border-slate-800 p-4 rounded-xl flex justify-between items-center">
                    <div>
                        <h4 class="text-white font-bold uppercase text-sm">${campos[id].nombre}</h4>
                        <p class="text-slate-500 text-[10px] uppercase">${campos[id].lugar}</p>
                    </div>
                    <button onclick="eliminarCampo('${id}')" class="text-red-500 hover:bg-red-500/10 p-2 rounded-lg transition">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>`;
        }
    } catch (e) { console.error("Error:", e); }
};
    
    window.verificarConflictosInteligentes = async function() {
        const campoId = document.getElementById('selectCampos').value;
        const fecha = document.querySelector('#formCrearPartido input[name="fecha"]').value;
        const horaNueva = document.querySelector('#formCrearPartido input[name="hora"]').value;
        const btnSubmit = document.querySelector('#formCrearPartido button[type="submit"]');
        
        const contenedor = document.getElementById('agendaCanchaContenedor');
        const listaAgenda = document.getElementById('listaAgendaCancha');
        const alerta = document.getElementById('alertaConflicto');

        if (!campoId || !fecha) {
            if(contenedor) contenedor.classList.add('hidden');
            return;
        }

        try {
            const res = await fetch('/api/partidos');
            const partidos = await res.json();
            
            const partidosHoy = Object.values(partidos).filter(p => p.campo_id === campoId && p.fecha === fecha);

            contenedor.classList.remove('hidden');
            listaAgenda.innerHTML = '';

            if (partidosHoy.length === 0) {
                listaAgenda.innerHTML = '<p class="text-[10px] text-slate-500 italic text-center py-2">Sede disponible para esta fecha</p>';
            } else {
                partidosHoy.sort((a,b) => a.hora.localeCompare(b.hora)).forEach(p => {
                    // --- LÓGICA PARA CALCULAR HORA FIN (Inicio + 100 min) ---
                    const [horas, minutos] = p.hora.split(':').map(Number);
                    let totalMinutosFin = (horas * 60) + minutos + 100;
                    
                    const horasFin = Math.floor(totalMinutosFin / 60);
                    const minutosFin = totalMinutosFin % 60;
                    
                    // Formateamos para que siempre tenga 2 dígitos (ej: 09:05)
                    const horaFinFormateada = `${horasFin.toString().padStart(2, '0')}:${minutosFin.toString().padStart(2, '0')}`;

                    listaAgenda.innerHTML += `
                        <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-2 rounded-lg mb-1">
                            <div class="flex flex-col">
                                <span class="text-blue-400 font-bold text-xs">${p.hora} - ${horaFinFormateada}</span>
                                <span class="text-[8px] text-slate-500 uppercase tracking-tighter">Ocupado (100 min)</span>
                            </div>
                            <span class="text-[9px] text-slate-400 uppercase truncate ml-4">${p.equipo_local} vs ${p.equipo_visitante}</span>
                        </div>`;
                });
            }

            // VALIDACIÓN DE CHOQUE
            if (horaNueva) {
                let choque = false;
                const totalMinNuevo = (parseInt(horaNueva.split(':')[0]) * 60) + parseInt(horaNueva.split(':')[1]);

                partidosHoy.forEach(p => {
                    const totalMinPartido = (parseInt(p.hora.split(':')[0]) * 60) + parseInt(p.hora.split(':')[1]);
                    if (Math.abs(totalMinPartido - totalMinNuevo) < 100) { choque = true; }
                });

                if (choque) {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
                    alerta.classList.remove('hidden');
                } else {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
                    alerta.classList.add('hidden');
                }
            }
        } catch (e) { console.error("Error agenda:", e); }
    };
</script>
</body>
</html>