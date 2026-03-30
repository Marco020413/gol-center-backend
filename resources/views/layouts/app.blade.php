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
        #contenedorCedulaJugadores {
            scrollbar-width: thin;
            scrollbar-color: #1e293b transparent;
        }
        /* Asegura que el modal no sea más alto que la pantalla del celular */
        #modalActualizarMarcador > div {
            max-height: 95vh;
            display: flex;
            flex-direction: column;
        }
        #formActualizarMarcador {
            overflow-y: auto;
        }
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
    let editCampoId = null;
    let ultimaCarga = {};
    let equiposCargados = false; 
    let limitePartidos = 5;
    let cacheEquiposData = null;
    let cacheCamposData = null;
    let limiteJugadores = 5;

    //LISTENERS Y CONFIGURACIONES INICIALES
    document.addEventListener('DOMContentLoaded', () => {
  
        window.modalJugador = document.getElementById('modalJugador');
        window.modalEquipo = document.getElementById('modalEquipo');
        window.modalCrearPartido = document.getElementById('modalCrearPartido');
        window.modalActualizarMarcador = document.getElementById('modalActualizarMarcador');
        
        window.formJugador = document.getElementById('formRegistroJugador');
        window.formEquipo = document.getElementById('formRegistroEquipo');
        window.formPartidos = document.getElementById('formCrearPartido');
        window.formActualizar = document.getElementById('formActualizarMarcador');
        window.recuperarFixtureGuardado();
        window.llenarSelectsEquipos();
        window.llenarSelectsCampos();


        const inputBusqueda = document.getElementById('busquedaJugador');
        const selectFiltroEquipo = document.getElementById('filtroEquipo');

        if (inputBusqueda) {
            inputBusqueda.addEventListener('input', () => { 
                limiteJugadores = 5; // Reiniciamos el límite al escribir
            });
        }
        if (selectFiltroEquipo) {
            selectFiltroEquipo.addEventListener('change', () => { 
                limiteJugadores = 5; // Reiniciamos el límite al cambiar equipo
            });
        }

            setTimeout(() => {
            if(typeof filtrarTabla === 'function') filtrarTabla();
        }, 100);
        

        // 3. FUNCIÓN PARA ABRIR MODAL CREAR PARTIDO
        window.abrirModalCrearPartido = function() {
            if (window.modalCrearPartido) {
                window.modalCrearPartido.classList.replace('hidden', 'flex');
                if (typeof llenarSelectsEquipos === 'function') llenarSelectsEquipos(); 
                if (typeof llenarSelectsCampos === 'function') llenarSelectsCampos(); 
            }
        };
        // 4. FUNCIÓN PARA GUARDAD TABLA DE TORNEO
        window.recuperarFixtureGuardado = async function() {
            try {
                const res = await fetch('/api/partidos');
                const partidosData = await res.json();
                const partidos = Object.keys(partidosData).map(id => ({ id, ...partidosData[id] }));

                if (partidos.length > 0) {
                    window.pintarFixtureVisual(partidos);
                }
            } catch (e) { console.error("Error recuperando fixture:", e); }
        };

        // 4. ACTIVAR SENSORES DE AGENDA (CONFLICTOS INTELIGENTES)
        const selectCampos = document.getElementById('selectCampos');
        const inputFecha = document.querySelector('#formCrearPartido input[name="fecha"]');
        const inputHora = document.querySelector('#formCrearPartido input[name="hora"]');

        if(selectCampos) selectCampos.addEventListener('change', window.verificarConflictosInteligentes);
        if(inputFecha) inputFecha.addEventListener('change', window.verificarConflictosInteligentes);
        if(inputHora) inputHora.addEventListener('change', window.verificarConflictosInteligentes);

        // 5. LÓGICA DE FORMULARIO: REGISTRO / EDICIÓN JUGADORES
        if(window.formJugador) {
            window.formJugador.onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnGuardar');
                const msgError = document.getElementById('mensajeError'); 
                btn.innerText = 'Procesando...'; 
                btn.disabled = true;
                if(msgError) msgError.classList.add('hidden');

                if (window.formJugador.telefono.value.length !== 10 && !editMode) {
                    alert("⚠️ El teléfono debe tener 10 dígitos");
                    btn.disabled = false;
                    btn.innerText = 'Registrar Jugador';
                    return;
                }

                const data = {
                    nombre: window.formJugador.nombre.value,
                    edad: window.formJugador.edad.value,
                    direccion: window.formJugador.direccion.value,
                    telefono: editMode ? editTelefono : window.formJugador.telefono.value,
                    equipo: window.formJugador.equipo.value,
                    numero: window.formJugador.numero.value,
                    estatus: document.getElementById('edit_estatus').value 
                };

                const url = editMode ? `/api/admin/jugadores/actualizar/${editTelefono}` : '/api/admin/jugadores/registrar';
                const method = editMode ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();
                    if (response.ok) { 
                        alert('✅ ¡Guardado con éxito!'); 
                        location.reload(); 
                    } else {
                        alert("⚠️ " + (result.error || "Error al procesar"));
                        btn.innerText = editMode ? 'Actualizar Datos' : 'Registrar Jugador';
                        btn.disabled = false;
                    }
                } catch (error) { 
                    alert('❌ Error de conexión');
                    btn.disabled = false;
                }
            };
        }

        // 6. LÓGICA DE FORMULARIO: REGISTRO DE EQUIPOS
        if(window.formEquipo) {
            window.formEquipo.onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnGuardarEquipo');
                const data = new FormData(window.formEquipo);
                try {
                    const response = await fetch('/api/admin/equipos/registrar', {
                        method: 'POST',
                        body: data,
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (response.ok) { alert('🏆 Equipo creado'); location.reload(); }
                } catch (error) { alert('❌ Error de conexión'); }
            };
        }

        // 7. LÓGICA DE FORMULARIO: CREAR PARTIDO (VERSION INTELIGENTE)
        if (window.formPartidos) {
            window.formPartidos.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // REGLA DE ORO: Si tenemos idPartidoSorteo, usamos la ruta de actualizar
                const idSorteo = window.idPartidoSorteo;
                const url = idSorteo ? `/api/admin/partidos/actualizar-datos/${idSorteo}` : '/api/admin/partidos/crear';
                const metodo = idSorteo ? 'PUT' : 'POST';

                const data = {
                    local: document.getElementById('selectLocal').value,
                    visitante: document.getElementById('selectVisitante').value,
                    campo_id: document.getElementById('selectCampos').value,
                    fecha: window.formPartidos.fecha.value,
                    hora: window.formPartidos.hora.value
                };

                try {
                    const res = await fetch(url, {
                        method: metodo,
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                        },
                        body: JSON.stringify(data)
                    });

                    if (res.ok) {
                        alert("✅ ¡Partido programado con éxito!");
                        window.idPartidoSorteo = null; 
                        location.reload(); 
                    }
                } catch (e) { alert("Error de conexión"); }
            });
        }

        // 8. LÓGICA DE FORMULARIO: ACTUALIZAR MARCADOR Y CÉDULA
        if (window.formActualizar) {
            window.formActualizar.onsubmit = async (e) => {
                e.preventDefault();
                const listaEstadisticas = {};
                document.querySelectorAll('.fila-jugador-cedula').forEach(fila => {
                    const tel = fila.dataset.telefono;
                    const asistio = fila.querySelector('.check-asistencia').checked;
                    const goles = fila.querySelector('.input-gol-jugador').value;
                    listaEstadisticas[tel] = {
                        asistio: asistio,
                        goles: parseInt(goles) || 0
                    };
                });

                const id = document.getElementById('edit_partido_id').value;
                const esFinal = document.getElementById('confirmar_final')?.checked || false;

                if (esFinal && !confirm("⚠️ Al confirmar como FINALIZADO, el acta se cerrará. ¿Proceder?")) return;

                const data = {
                    goles_local: document.getElementById('goles_local').value,
                    goles_visitante: document.getElementById('goles_visitante').value,
                    confirmar_final: esFinal,
                    detalle_jugadores: listaEstadisticas 
                };

                try {
                    const res = await fetch(`/api/admin/partidos/actualizar/${id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(data)
                    });
                    const result = await res.json();
                    if (res.ok) {
                        alert(esFinal ? "🔒 Acta cerrada" : "✅ Marcador actualizado");
                        if(window.modalActualizarMarcador) window.modalActualizarMarcador.classList.replace('flex', 'hidden');
                        cargarPartidosCards(); 
                    } else {
                        alert("❌ " + (result.error || "Error al actualizar"));
                    }
                } catch (e) { alert("Error de conexión"); }
            };
        }

        // 9. CARGA INICIAL DE DATOS
        if (typeof window.cargarTablaPosiciones === 'function') {
            window.cargarTablaPosiciones();
        }
        cargarPartidosCards(); 
        cargarGestionEquipos();
        
    });

    // --- FUNCIONES DE APERTURA DE MODALES ---
    window.abrirModal = function() { 
        document.querySelector('#modalJugador h3').innerText = 'Nuevo Jugador';
        document.getElementById('btnGuardar').innerText = 'Registrar Jugador :)';
        editMode = false;
        
        // Mostramos el modal
        const modal = document.getElementById('modalJugador');
        if(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        // Cargamos los equipos en el select del modal
        cargarEquipos(); 
    };

    window.abrirModalEquipo = function() { 
        const modal = document.getElementById('modalEquipo');
        if(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    window.cerrarModalEquipo = function() { 
        const modal = document.getElementById('modalEquipo');
        if(modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    };
    // --- FUNCIONES GLOBALES ---

    window.editarJugador = async function(telefono, nombre, equipo, edad, direccion, numero, pj, estatus) {
        editMode = true;
        editTelefono = telefono;
        
        document.querySelector('#modalJugador h3').innerText = 'Editar Jugador';
        document.getElementById('btnGuardar').innerText = 'Actualizar Datos';
        
        const f = window.formJugador; 
        f.nombre.value = nombre;
        f.telefono.value = telefono;
        f.telefono.disabled = true; 
        f.edad.value = edad;
        f.direccion.value = direccion;
        f.numero.value = numero; 

        // Cargar estatus
        if(document.getElementById('edit_estatus')) {
            document.getElementById('edit_estatus').value = estatus || 'activo';
        }

        // Bloqueo de equipo por historial
        const selectEquipo = document.getElementById('selectEquipos');
        const aviso = document.getElementById('avisoEquipoBloqueado');
        if (parseInt(pj) > 0) {
            selectEquipo.disabled = true;
            selectEquipo.classList.add('opacity-50');
            if(aviso) aviso.classList.remove('hidden');
        } else {
            selectEquipo.disabled = false;
            selectEquipo.classList.remove('opacity-50');
            if(aviso) aviso.classList.add('hidden');
        }

        await cargarEquipos(); 
        f.equipo.value = equipo; 
        window.modalJugador.classList.replace('hidden', 'flex');
    };

    function cerrarModal() { 
        window.modalJugador.classList.replace('flex', 'hidden'); 
        editMode = false;
        window.formJugador.reset();
        window.formJugador.telefono.disabled = false;
        document.getElementById('selectEquipos').disabled = false;
        document.getElementById('selectEquipos').classList.remove('opacity-50');
        if(document.getElementById('avisoEquipoBloqueado')) document.getElementById('avisoEquipoBloqueado').classList.add('hidden');
    }

    async function cargarEquipos() {
        const select = document.getElementById('selectEquipos');
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
    
    window.changeTab = function(tabName) {
        // 1. Ocultar todos los paneles
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
        
        // 2. Resetear estilos de botones
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('text-blue-500', 'border-b-2', 'border-blue-500');
            b.classList.add('text-slate-500');
        });

        // 3. Mostrar el panel objetivo
        const target = document.getElementById('content-' + tabName);
        if(target) target.classList.remove('hidden');

        // 4. Activar estilo del botón
        const btnActivo = event ? event.currentTarget : null;
        if(btnActivo) btnActivo.classList.add('text-blue-500', 'border-b-2', 'border-blue-500');

        // --- REGLA DE RENDIMIENTO: Resetear paginación al cambiar ---
        if (tabName === 'partidos') {
            window.limitePartidos = 5; 
            // Llamamos a la carga de datos
            if(typeof cargarPartidosCards === 'function') cargarPartidosCards();
        }

        // 5. CARGA INTELIGENTE (Lazy Loading)
        const ahora = Date.now();
        const necesitaCarga = !ultimaCarga[tabName] || (ahora - ultimaCarga[tabName] > 10000);

        if (necesitaCarga) {
            switch(tabName) {
                case 'partidos':
                    cargarPartidosCards(); 
                    break;
                case 'posiciones':
                    window.cargarTablaPosiciones(); 
                    break;
                case 'equipos_gest':
                    if(typeof cargarGestionEquipos === 'function') cargarGestionEquipos();
                    break;
                case 'general':
                    if(typeof recuperarFixtureGuardado === 'function') recuperarFixtureGuardado();
                    break;
            }
            ultimaCarga[tabName] = ahora;
        }
    };

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


    

    

    window.eliminarJugador = async function(telefono) {
        // 1. Confirmación de seguridad
        if (!confirm('⚠️ ¿Estás seguro de eliminar a este jugador? Se borrarán sus estadísticas permanentemente.')) {
            return;
        }

        try {
            const response = await fetch(`/api/admin/jugadores/eliminar/${telefono}`, {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                alert('🗑️ Jugador eliminado correctamente');
                location.reload(); // Recargamos para actualizar la tabla
            } else {
                const result = await response.json();
                alert('❌ Error: ' + (result.error || 'No se pudo eliminar al jugador'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error de conexión con el servidor');
        }
    };
    
    function filtrarTabla() {
        const busqueda = document.getElementById('busquedaJugador').value.toLowerCase().trim();
        const equipoFiltro = document.getElementById('filtroEquipo').value; 
        const orden = document.getElementById('ordenarPor').value;
        
        const tablaBody = document.querySelector('#content-jugadores tbody');
        if (!tablaBody) return;

        const filas = Array.from(tablaBody.querySelectorAll('tr'));

        // 1. PRIMERA PASADA: Identificar quiénes cumplen con el filtro
        let filasQueCumplen = filas.filter(fila => {
            const nombreContenedor = fila.querySelector('[data-field="nombre"]');
            const nombreCompletoTexto = nombreContenedor?.innerText.toUpperCase() || "";
            const nombreSolo = nombreContenedor?.innerText.toLowerCase() || "";
            const telefono = fila.querySelector('[data-field="telefono"]')?.innerText.toLowerCase() || "";
            const equipoCelda = fila.querySelector('[data-field="equipo"]');
            const valorEquipo = equipoCelda ? equipoCelda.getAttribute('data-valor') : "";
            const nombreEquipoTexto = equipoCelda?.innerText.toLowerCase() || "";

            const coincideBusqueda = nombreSolo.includes(busqueda) || 
                                    telefono.includes(busqueda) || 
                                    nombreEquipoTexto.includes(busqueda);
            
            let coincideFiltro = true;
            if (equipoFiltro === "Libre") {
                coincideFiltro = (valorEquipo === "Libre");
            } else if (equipoFiltro === "SUSPENDIDO") {
                coincideFiltro = nombreCompletoTexto.includes("SUSPENDIDO");
            } else if (equipoFiltro === "LESIONADO") {
                coincideFiltro = nombreCompletoTexto.includes("LESIONADO");
            } else if (equipoFiltro !== "") {
                coincideFiltro = (valorEquipo === equipoFiltro);
            }

            return coincideBusqueda && coincideFiltro;
        });

        // 2. ORDENAR las filas que cumplen
        filasQueCumplen.sort((a, b) => {
            if (orden === 'goles') return (parseInt(b.cells[3].innerText) || 0) - (parseInt(a.cells[3].innerText) || 0);
            if (orden === 'pj') return (parseInt(b.cells[2].innerText) || 0) - (parseInt(a.cells[2].innerText) || 0);
            if (orden === 'dorsal') {
                const numA = parseInt(a.querySelector('.size-8')?.innerText) || 0;
                const numB = parseInt(b.querySelector('.size-8')?.innerText) || 0;
                return numA - numB;
            }
            return a.querySelector('[data-field="nombre"]').innerText.localeCompare(b.querySelector('[data-field="nombre"]').innerText);
        });

        // 3. APLICAR VISIBILIDAD Y LÍMITE
        filas.forEach(f => f.style.display = "none"); // Ocultamos todas primero

        // Solo mostramos las que cumplen, hasta el límite actual
        filasQueCumplen.slice(0, limiteJugadores).forEach(f => {
            f.style.display = "";
            tablaBody.appendChild(f); // Re-inyectar para mantener el orden visual
        });

        // 4. GESTIONAR BOTÓN "MOSTRAR MÁS"
        gestionarBotonVerMasJugadores(filasQueCumplen.length);
    }
    

    function gestionarBotonVerMasJugadores(totalFiltrados) {
        let btnContenedor = document.getElementById('btnContenedorJugadores');
        
        // Si no existe el contenedor del botón en el HTML, lo creamos después de la tabla
        if (!btnContenedor) {
            btnContenedor = document.createElement('div');
            btnContenedor.id = 'btnContenedorJugadores';
            btnContenedor.className = 'flex flex-col items-center gap-2 py-6';
            document.getElementById('content-jugadores').appendChild(btnContenedor);
        }

        if (totalFiltrados > limiteJugadores) {
            btnContenedor.innerHTML = `
                <p class="text-[9px] text-slate-500 uppercase font-black">Mostrando ${limiteJugadores} de ${totalFiltrados} jugadores</p>
                <button onclick="window.cargarMasJugadores()" 
                    class="bg-slate-800 hover:bg-blue-600 text-white px-8 py-2 rounded-xl text-[10px] font-black uppercase transition-all active:scale-95 border border-slate-700">
                    ➕ Ver más jugadores
                </button>
            `;
        } else if (limiteJugadores > 5) {
            btnContenedor.innerHTML = `
                <button onclick="window.verMenosJugadores()" 
                    class="text-slate-500 hover:text-white text-[9px] font-bold uppercase tracking-widest transition-all">
                    ⬆️ Volver al principio
                </button>
            `;
        } else {
            btnContenedor.innerHTML = ''; // Si no hay nada que paginar, se limpia
        }
    }

    window.cargarMasJugadores = function() {
        limiteJugadores += 5;
        filtrarTabla();
    };

    window.verMenosJugadores = function() {
        limiteJugadores = 5;
        filtrarTabla();
        document.getElementById('content-jugadores').scrollIntoView({ behavior: 'smooth' });
    };

   function abrirModalCrearPartido() {
        const modal = document.getElementById('modalCrearPartido');
        if (modal) {
            modal.classList.replace('hidden', 'flex');
            llenarSelectsEquipos(); 
            llenarSelectsCampos();
        }
    }

    window.cerrarModalCrearPartido = function() {
        const modal = document.getElementById('modalCrearPartido');
        if (modal) {
            modal.classList.replace('flex', 'hidden');
            const form = document.getElementById('formCrearPartido');
            form.reset();
            
            const selLocal = document.getElementById('selectLocal');
            const selVisitante = document.getElementById('selectVisitante');
            
            selLocal.disabled = false;
            selVisitante.disabled = false;
            selLocal.classList.remove('opacity-50', 'cursor-not-allowed');
            selVisitante.classList.remove('opacity-50', 'cursor-not-allowed');
            
            window.idPartidoSorteo = null;
        }
    };

    window.llenarSelectsEquipos = async function() {
        const selectLocal = document.getElementById('selectLocal');
        const selectVisitante = document.getElementById('selectVisitante');
        
        if (!selectLocal || !selectVisitante) return;

        try {
            // SI NO HAY CACHÉ, HACEMOS EL FETCH UNA SOLA VEZ
            if (!cacheEquiposData) {
                const res = await fetch('/api/equipos');
                cacheEquiposData = await res.json();
            }

            const selects = [selectLocal, selectVisitante];
            selects.forEach(s => {
                const currentVal = s.value;
                s.innerHTML = '<option value="">Selecciona un club</option>';
                for (const id in cacheEquiposData) {
                    const opt = document.createElement('option');
                    opt.value = cacheEquiposData[id].nombre;
                    opt.textContent = cacheEquiposData[id].nombre.toUpperCase();
                    s.appendChild(opt);
                }
                if(currentVal) s.value = currentVal;
            });
        } catch (e) { console.error("Error al llenar selects:", e); }
    };
    

    // --- FUNCIONES GLOBALES ---

    async function cargarPartidosCards() {
        const contenedor = document.getElementById('contenedorListaPartidos');
        if (!contenedor) return;

        // Feedback visual inmediato para mejorar la percepción de velocidad
        contenedor.innerHTML = `
            <div class="col-span-full py-20 text-center animate-pulse">
                <div class="text-blue-500 font-black text-xs uppercase tracking-[0.3em]">Sincronizando Calendario...</div>
            </div>
        `;

        try {
            const res = await fetch('/api/partidos');
            
            // Si el servidor falla (Error 500), lanzamos error para el catch
            if (!res.ok) throw new Error("Error en servidor");

            const partidos = await res.json();
            
            // Protección contra datos vacíos
            if (!partidos || Object.keys(partidos).length === 0) {
                contenedor.innerHTML = '<p class="text-slate-500 italic text-center py-10">No hay partidos programados.</p>';
                return;
            }

            window.cachePartidosLista = Object.keys(partidos).map(id => ({
                id: id,
                ...partidos[id]
            }));

            aplicarFiltrosPartidos();
        } catch (e) { 
            console.error("Error cargando partidos:", e);
            contenedor.innerHTML = '<p class="text-red-500 text-[10px] text-center uppercase font-black py-10">⚠️ Error de conexión con la liga</p>';
        }
    }

    window.aplicarFiltrosPartidos = function() {
        const contenedor = document.getElementById('contenedorListaPartidos');
        if (!contenedor || !window.cachePartidosLista) return;

        const busqueda = document.getElementById('filtroEquipoPartido').value.toLowerCase().trim();
        const estatusFiltro = document.getElementById('filtroEstatusPartido').value;
        const orden = document.getElementById('ordenarPartidos').value;

        // 1. FILTRAR
        let filtrados = window.cachePartidosLista.filter(p => {
            const local = (p.equipo_local || "Equipo").toLowerCase();
            const visitante = (p.equipo_visitante || "Equipo").toLowerCase();
            const nombreMatch = local.includes(busqueda) || visitante.includes(busqueda);
            const estatusMatch = (estatusFiltro === 'todos') ? true : (p.estatus === estatusFiltro);
            return nombreMatch && estatusMatch;
        });

        // 2. ORDENAR (Programados arriba, Pendientes abajo)
        filtrados.sort((a, b) => {
            const tieneFechaA = a.fecha && a.fecha !== 'PENDIENTE';
            const tieneFechaB = b.fecha && b.fecha !== 'PENDIENTE';
            if (tieneFechaA && !tieneFechaB) return -1;
            if (!tieneFechaA && tieneFechaB) return 1;
            const timeA = new Date(`${a.fecha}T${a.hora || '00:00'}`).getTime();
            const timeB = new Date(`${b.fecha}T${b.hora || '00:00'}`).getTime();
            return orden === 'recientes' ? timeB - timeA : timeA - timeB;
        });

        // 3. PAGINACIÓN
        const totalEncontrados = filtrados.length;
        const partidosAMostrar = filtrados.slice(0, window.limitePartidos);

        if (totalEncontrados === 0) {
            contenedor.innerHTML = '<p class="text-center text-slate-600 py-10 italic">No se encontraron partidos.</p>';
            return;
        }

        let html = '';
        const escudoDefault = 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png';
        let yaPuseSeparadorPendientes = false;

        // 4. GENERAR CARDS
        partidosAMostrar.forEach((p, index) => {
            const nomLocal = (p.equipo_local || "POR DEFINIR").toUpperCase();
            const nomVis = (p.equipo_visitante || "POR DEFINIR").toUpperCase();
            const tieneFecha = p.fecha && p.fecha !== 'PENDIENTE';

            // Separador visual de Pendientes
            if (!tieneFecha && !yaPuseSeparadorPendientes && index > 0) {
                html += `
                    <div class="col-span-full flex items-center gap-4 my-6 opacity-60">
                        <span class="text-[9px] font-black text-amber-500 uppercase tracking-[0.2em] whitespace-nowrap">Partidos por programar</span>
                        <div class="h-px bg-amber-500/30 w-full"></div>
                    </div>`;
                yaPuseSeparadorPendientes = true;
            }

            // LÓGICA DE BADGE DE ESTADO (CENTRAL)
            let badgeEstatus = '';
            if (p.resultado_confirmado || p.estatus === 'confirmado') {
                badgeEstatus = `<span class="bg-slate-800 text-slate-500 text-[8px] px-2 py-1 rounded-md font-black uppercase">CERRADA 🔒</span>`;
            } else if (p.estatus === 'en_curso') {
                badgeEstatus = `<span class="bg-green-600/20 text-green-500 text-[8px] px-2 py-1 rounded-md font-black uppercase animate-pulse">EN VIVO 🟢</span>`;
            } else if (p.estatus === 'finalizado') {
                badgeEstatus = `<span class="bg-amber-600/20 text-amber-500 text-[8px] px-2 py-1 rounded-md font-black uppercase">POR SUBIR ACTA ⚠️</span>`;
            } else if (!tieneFecha) {
                badgeEstatus = `<span class="bg-red-600/20 text-red-500 text-[8px] px-2 py-1 rounded-md font-black uppercase">SIN FECHA</span>`;
            } else {
                badgeEstatus = `<span class="bg-blue-600/20 text-blue-500 text-[8px] px-2 py-1 rounded-md font-black uppercase">PROGRAMADO</span>`;
            }

            html += `
                <div onclick="window.verDetallePartido('${p.id}')" 
                    class="cursor-pointer bg-slate-900 border ${!tieneFecha ? 'border-red-900/30' : 'border-slate-800'} p-5 rounded-3xl mb-4 transition-all hover:border-blue-500 hover:bg-slate-800/50 shadow-lg relative overflow-hidden group">
                    
                    <div class="flex justify-between items-center gap-4">
                        
                        <div class="flex flex-col gap-4 flex-1">
                            <div class="flex items-center gap-4">
                                <img src="${p.escudo_local || escudoDefault}" class="size-8 object-contain">
                                <span class="text-white font-black uppercase tracking-tighter text-sm truncate">${nomLocal}</span>
                                <span class="text-xl font-black text-white ml-auto">${p.goles_local || 0}</span>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <div class="h-px bg-slate-800 flex-1"></div>
                                ${badgeEstatus}
                                <div class="h-px bg-slate-800 flex-1"></div>
                            </div>

                            <div class="flex items-center gap-4">
                                <img src="${p.escudo_visitante || escudoDefault}" class="size-8 object-contain">
                                <span class="text-white font-black uppercase tracking-tighter text-sm truncate">${nomVis}</span>
                                <span class="text-xl font-black text-white ml-auto">${p.goles_visitante || 0}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2 border-l border-slate-800 pl-6 min-w-[140px]">
                            <span class="text-[10px] ${!tieneFecha ? 'text-red-500 font-black' : 'text-slate-500 font-bold'}">
                                ${p.fecha || 'FECHA PENDIENTE'}
                            </span>

                            <button onclick="event.stopPropagation(); window.abrirAsignacionRapida('${p.equipo_local}', '${p.equipo_visitante}', '${p.id}')" 
                                    class="w-full bg-slate-800 hover:bg-slate-700 text-white text-[9px] font-black py-2 rounded-xl transition-all uppercase border border-slate-700 active:scale-95">
                                📅 LOGÍSTICA
                            </button>

                            ${!(p.resultado_confirmado || p.estatus === 'confirmado') ? `
                                <button onclick="event.stopPropagation(); window.abrirActualizarMarcador('${p.id}')" 
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white text-[9px] font-black py-2 rounded-xl transition-all uppercase shadow-lg shadow-blue-900/20 active:scale-95">
                                    ⚽ ESTADÍSTICAS
                                </button>
                            ` : `
                                <div class="w-full text-center py-2 bg-slate-950/50 rounded-xl">
                                    <span class="text-[8px] text-slate-600 italic uppercase">🔒 Finalizado</span>
                                </div>
                            `}
                        </div>
                    </div>

                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-[7px] text-blue-500 font-black tracking-[0.3em] uppercase">Toca para ver detalle</span>
                    </div>
                </div>`;
        });

        // 5. SECCIONAMIENTO VISUAL (Botones de navegación)
        if (totalEncontrados > 10) {
            html += `<div class="col-span-full flex flex-col items-center gap-4 py-10 border-t border-slate-800/50 mt-6">`;
            
            if (totalEncontrados > window.limitePartidos) {
                html += `
                    <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-2">
                        Mostrando ${window.limitePartidos} de ${totalEncontrados} partidos
                    </p>
                    <button onclick="window.cargarMasPartidos()" 
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase transition-all shadow-xl shadow-blue-900/20 active:scale-95 flex items-center justify-center gap-3">
                        <span>➕ Cargar siguientes 5</span>
                        <span class="bg-blue-400/30 px-2 py-0.5 rounded-lg text-[8px]">${totalEncontrados - window.limitePartidos} restantes</span>
                    </button>
                `;
            }

            if (window.limitePartidos > 10) {
                html += `
                    <button onclick="window.verMenosPartidos()" 
                            class="text-slate-500 hover:text-white text-[9px] font-bold uppercase tracking-widest hover:underline decoration-blue-500 underline-offset-4 transition-all mt-2">
                        ⬆️ Volver al inicio
                    </button>
                `;
            }

            html += `</div>`;
        }

        contenedor.innerHTML = html;
    };

    // Funciones de control global
    window.cargarMasPartidos = function() {
        window.limitePartidos += 5;
        aplicarFiltrosPartidos();
    };

    window.verMenosPartidos = function() {
        window.limitePartidos = 5;
        aplicarFiltrosPartidos();
        document.getElementById('content-partidos').scrollTo({ top: 0, behavior: 'smooth' });
    };

    window.cerrarModalDetalle = function() {
        const modal = document.getElementById('modalDetallePartido');
        if (modal) {
            modal.classList.replace('flex', 'hidden');
        }
    };
    
    async function abrirActualizarMarcador(id) {
        try {
            const resPartidos = await fetch('/api/partidos');
            const partidos = await resPartidos.json();
            const p = partidos[id];
            
            const resJugadores = await fetch('/api/jugadores');
            const todosLosJugadores = await resJugadores.json();

            if(!p) return alert("Partido no encontrado");

            document.getElementById('edit_partido_id').value = id;
            document.getElementById('edit_labelLocal').innerText = p.equipo_local.toUpperCase();
            document.getElementById('edit_labelVisitante').innerText = p.equipo_visitante.toUpperCase();
            document.getElementById('goles_local').value = p.goles_local || 0;
            document.getElementById('goles_visitante').value = p.goles_visitante || 0;

            const statsGuardadas = p.detalle_jugadores || {};
            const contenedor = document.getElementById('contenedorCedulaJugadores');
            contenedor.innerHTML = ''; 

            const equipos = [
                { nombre: p.equipo_local, tipo: 'local', color: 'blue' },
                { nombre: p.equipo_visitante, tipo: 'visitante', color: 'red' }
            ];

            equipos.forEach(eq => {
                // Contamos bajas para el aviso visual
                const bajas = Object.values(todosLosJugadores).filter(j => 
                    j.equipo === eq.nombre && (j.estatus === 'suspendido' || j.estatus === 'lesionado')
                ).length;

                // Obtenemos a TODOS los jugadores del equipo (incluyendo suspendidos para no perder sus goles)
                const jugadoresEquipo = Object.entries(todosLosJugadores)
                    .filter(([tel, j]) => j.equipo === eq.nombre)
                    .sort((a,b) => a[1].numero - b[1].numero);

                let htmlSeccion = `
                    <div class="border border-slate-800 rounded-xl overflow-hidden mb-3">
                        <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" 
                                class="w-full flex items-center justify-between p-3 bg-slate-900 hover:bg-slate-800 transition text-left">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-3">
                                    <div class="size-2 rounded-full bg-${eq.color}-500 shadow-[0_0_8px] shadow-${eq.color}-500/50"></div>
                                    <span class="text-[11px] font-black text-white uppercase tracking-widest">${eq.nombre}</span>
                                    <span class="text-[9px] text-slate-500 bg-slate-950 px-2 py-0.5 rounded-full">${jugadoresEquipo.length} REGISTRADOS</span>
                                </div>
                                ${bajas > 0 ? `<span class="text-[8px] text-red-400 font-bold ml-5 uppercase italic">⚠️ ${bajas} baja(s) por sanción o lesión</span>` : ''}
                            </div>
                            <svg class="size-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div class="hidden p-2 space-y-2 bg-slate-950/30">
                `;

                jugadoresEquipo.forEach(([tel, j]) => {
                    const previa = statsGuardadas[tel] || { asistio: true, goles: 0 };
                    const esInactivo = (j.estatus === 'suspendido' || j.estatus === 'lesionado');

                    htmlSeccion += `
                        <div class="fila-jugador-cedula flex items-center gap-3 bg-slate-900/60 p-2 rounded-lg border border-slate-800/40 
                            ${(esInactivo || !previa.asistio) ? 'opacity-30 grayscale' : ''}" 
                            id="fila_jugador_${tel}" data-telefono="${tel}">
                            
                            <input type="checkbox" ${previa.asistio && !esInactivo ? 'checked' : ''} 
                                ${esInactivo ? 'disabled' : ''}
                                class="check-asistencia size-4 rounded accent-green-500" 
                                onchange="window.toggleAsistencia('${tel}', this)">
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] text-white font-bold truncate uppercase">
                                    <span class="text-slate-500 mr-1">#${j.numero}</span>${j.nombre}
                                    ${esInactivo ? `<span class="text-[7px] text-red-500 ml-1 font-black">[${j.estatus.toUpperCase()}]</span>` : ''}
                                </p>
                            </div>

                            <div class="flex items-center gap-1 bg-slate-950 rounded-lg p-1 border border-slate-800">
                                <button type="button" onclick="window.modificarGolJugador('${tel}', -1, '${eq.tipo}')" 
                                        ${esInactivo ? 'disabled' : ''}
                                        class="btn-control-gol size-6 flex items-center justify-center text-slate-400 hover:text-white rounded">-</button>
                                
                                <input type="number" value="${previa.goles}" readonly 
                                    id="goles_jugador_${tel}" 
                                    class="input-gol-jugador input-gol-${eq.tipo} w-7 bg-transparent text-center text-[11px] font-black text-blue-400 outline-none">
                                
                                <button type="button" onclick="window.modificarGolJugador('${tel}', 1, '${eq.tipo}')" 
                                        ${esInactivo ? 'disabled' : ''}
                                        class="btn-control-gol size-6 flex items-center justify-center text-white bg-blue-600/20 hover:bg-blue-600 rounded">+</button>
                            </div>
                        </div>
                    `;
                });

                htmlSeccion += `</div></div>`;
                contenedor.innerHTML += htmlSeccion;
            });

            window.modalActualizarMarcador.classList.replace('hidden', 'flex');
        } catch (e) { console.error("Error:", e); }
    }

    function cerrarModalMarcador() { window.modalActualizarMarcador.classList.replace('flex', 'hidden'); }

    // --- FUNCIÓN PARA BORRAR PARTIDO---
    async function eliminarPartido() {
        const id = document.getElementById('edit_partido_id').value;
        if (!confirm("⚠️ ¿Estás COMPLETAMENTE seguro de borrar este partido? Esta acción es irreversible.")) return;
        
        try {
            const res = await fetch(`/api/admin/partidos/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (res.ok) {
                cacheCamposData = null;
                window.cargarCamposCards(true);
                alert("🗑️ Partido eliminado");
                cerrarModalMarcador();
                cargarPartidosCards();
            }
        } catch (e) { alert("Error al eliminar"); }
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

    function abrirModalCampo() { 
        document.getElementById('modalCrearCampo').classList.replace('hidden', 'flex'); 
        }

    function cerrarModalCampo() { 
    // Corregido: de flex a hidden
        document.getElementById('modalCrearCampo').classList.replace('flex', 'hidden'); 
        document.getElementById('formCrearCampo').reset();
                
        // Limpiamos variables de edición para que la próxima vez que abras sea "Nuevo" y no "Editar"
        window.editCampoId = null; 
        document.querySelector('#modalCrearCampo h3').innerText = 'Registrar Sede';
        document.getElementById('estadoCampoContainer').classList.add('hidden');
        }

   

    
    window.verDetallePartido = async function(id) {
        try {
            const p = window.cachePartidosLista.find(item => item.id === id);
            if(!p) return;

            const modal = document.getElementById('modalDetallePartido');
            modal.classList.replace('hidden', 'flex');

            // 1. LLENAR DATOS BÁSICOS
            document.getElementById('det_fecha').innerText = p.fecha || 'PENDIENTE';
            
            if (p.hora && p.hora !== '00:00') {
                const [h, m] = p.hora.split(':').map(Number);
                const fin = new Date(); fin.setHours(h, m + 100);
                const horaFinStr = fin.getHours().toString().padStart(2, '0') + ':' + fin.getMinutes().toString().padStart(2, '0');
                document.getElementById('det_rango_hora').innerText = `HORARIO: ${p.hora} HRS - ${horaFinStr} HRS`;
            } else {
                document.getElementById('det_rango_hora').innerText = "HORARIO POR DEFINIR";
            }

            document.getElementById('det_nombre_local').innerText = p.equipo_local;
            document.getElementById('det_nombre_visitante').innerText = p.equipo_visitante;
            document.getElementById('det_goles_local').innerText = p.goles_local || 0;
            document.getElementById('det_goles_visitante').innerText = p.goles_visitante || 0;
            document.getElementById('det_escudo_local').src = p.escudo_local || escudoDefault;
            document.getElementById('det_escudo_visitante').src = p.escudo_visitante || escudoDefault;
            

            if (!window.cacheCamposData) {
                const resC = await fetch('/api/campos');
                window.cacheCamposData = await resC.json();
            }

            const infoCampo = window.cacheCamposData ? window.cacheCamposData[p.campo_id] : null;
            const detCanchaElement = document.getElementById('det_cancha');
            
            if (infoCampo) {
                detCanchaElement.innerText = `${infoCampo.nombre.toUpperCase()} (${infoCampo.lugar.toUpperCase()})`;
            } else {
                detCanchaElement.innerText = "SEDE POR CONFIRMAR";
            }


            const est = document.getElementById('det_estatus');
            const actaCerrada = p.resultado_confirmado || p.estatus === 'confirmado';
            est.innerText = (actaCerrada ? 'Finalizado' : p.estatus).toUpperCase();
            est.className = actaCerrada 
                ? "text-[8px] font-black px-3 py-1 rounded-full bg-slate-800 text-slate-500 border border-slate-700" 
                : "text-[8px] font-black px-3 py-1 rounded-full bg-green-500/10 text-green-500 border border-green-500/20";

            // 2. GOLEADORES
            const listaLocal = document.getElementById('lista_goleadores_local');
            const listaVisitante = document.getElementById('lista_goleadores_visitante');
            listaLocal.innerHTML = '<span class="text-[7px] animate-pulse text-slate-600">CARGANDO...</span>';
            listaVisitante.innerHTML = '<span class="text-[7px] animate-pulse text-slate-600">CARGANDO...</span>';

            if(p.detalle_jugadores) {
                if(!window.cacheJugadoresGlobal) {
                    const resJ = await fetch('/api/jugadores');
                    window.cacheJugadoresGlobal = await resJ.json();
                }
                
                const jugadores = window.cacheJugadoresGlobal;
                listaLocal.innerHTML = ''; listaVisitante.innerHTML = '';

                Object.entries(p.detalle_jugadores).forEach(([tel, stats]) => {
                    if(stats.goles > 0) {
                        const infoJ = jugadores[tel];
                        const nombre = infoJ ? infoJ.nombre : "Jugador";
                        const esLocal = infoJ && infoJ.equipo === p.equipo_local;
                        
                        const item = `
                            <div class="flex items-center gap-2 ${esLocal ? 'justify-end' : 'justify-start'} mb-1">
                                ${esLocal ? `<span class="text-[10px] text-white font-bold uppercase">${nombre}</span>` : ''}
                                <div class="flex items-center bg-slate-900 px-2 py-1 rounded-lg border border-slate-800">
                                    <span class="text-[10px]">⚽</span>
                                    ${stats.goles > 1 ? `<span class="text-[10px] ml-1 font-black text-blue-500">${stats.goles}</span>` : ''}
                                </div>
                                ${!esLocal ? `<span class="text-[10px] text-white font-bold uppercase">${nombre}</span>` : ''}
                            </div>`;
                        
                        if(esLocal) listaLocal.innerHTML += item;
                        else listaVisitante.innerHTML += item;
                    }
                });
            }

            if(listaLocal.innerHTML === '' || listaLocal.innerHTML.includes('CARGANDO')) 
                listaLocal.innerHTML = '<span class="text-[8px] text-slate-700 uppercase block text-right italic">Sin anotaciones</span>';
            if(listaVisitante.innerHTML === '' || listaVisitante.innerHTML.includes('CARGANDO')) 
                listaVisitante.innerHTML = '<span class="text-[8px] text-slate-700 uppercase block text-left italic">Sin anotaciones</span>';

        } catch (e) { console.error("Error en detalle:", e); }
    };

    window.cerrarModalReasignar = function() {
        const modal = document.getElementById('modalReasignarSede');
        if (modal) {
            modal.classList.replace('flex', 'hidden');
        }
    };

    window.cerrarDetalle = function() {
        document.getElementById('modalDetallePartido').classList.replace('flex', 'hidden');
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


    // Función para los botones + y -
    window.modificarGolJugador = function(telefono, cambio) {
        const input = document.getElementById(`goles_jugador_${telefono}`);
        let nuevoValor = (parseInt(input.value) || 0) + cambio;
        if (nuevoValor < 0) nuevoValor = 0;
        input.value = nuevoValor;
        recalcularMarcador();
    }

    // Sumar todos los goles de la lista y actualizar los cuadros grandes de arriba
    window.recalcularMarcadorGlobal = function() {
        let sumaLocal = 0;
        let sumaVisitante = 0;

        document.querySelectorAll('.input-gol-local').forEach(i => sumaLocal += parseInt(i.value) || 0);
        document.querySelectorAll('.input-gol-visitante').forEach(i => sumaVisitante += parseInt(i.value) || 0);

        document.getElementById('goles_local').value = sumaLocal;
        document.getElementById('goles_visitante').value = sumaVisitante;
    };

    window.toggleAsistencia = function(telefono, checkbox) {
        const fila = document.getElementById(`fila_jugador_${telefono}`);
        const inputGol = document.getElementById(`goles_jugador_${telefono}`);
        const botones = fila.querySelectorAll('.btn-control-gol');

        if (checkbox.checked) {
            fila.classList.remove('opacity-30', 'grayscale');
            botones.forEach(b => b.disabled = false);
        } else {
            fila.classList.add('opacity-30', 'grayscale');
            inputGol.value = 0; // Si no jugó, 0 goles
            botones.forEach(b => b.disabled = true);
            window.recalcularMarcadorGlobal();
        }
    };

    window.modificarGolJugador = function(telefono, cambio, tipo) {
        const input = document.getElementById(`goles_jugador_${telefono}`);
        let nuevoVal = (parseInt(input.value) || 0) + cambio;
        if (nuevoVal < 0) nuevoVal = 0;
        input.value = nuevoVal;
        window.recalcularMarcadorGlobal();
    };



    //Tabla de Posiciones

    window.cargarTablaPosiciones = async function() {
        const cuerpo = document.getElementById('tablaCuerpoPosiciones');
            if(!cuerpo) return;

            try {
                const [resE, resP] = await Promise.all([
                    fetch('/api/equipos'),
                    fetch('/api/partidos')
                ]);
                
                const equipos = await resE.json();
                const partidos = await resP.json();

                // 1. Inicializar objeto de estadísticas para cada equipo
                let stats = {};
                for (const id in equipos) {
                    stats[equipos[id].nombre] = {
                        nombre: equipos[id].nombre,
                        escudo: equipos[id].escudo,
                        pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0
                    };
                }

                // 2. Procesar partidos (Solo los que tienen acta cerrada)
                Object.values(partidos).forEach(partido => {
                    if (partido.resultado_confirmado) {
                        const loc = partido.equipo_local;
                        const vis = partido.equipo_visitante;
                        const gl = parseInt(partido.goles_local);
                        const gv = parseInt(partido.goles_visitante);

                        if (stats[loc] && stats[vis]) {
                            stats[loc].pj++; stats[vis].pj++;
                            stats[loc].gf += gl; stats[loc].gc += gv;
                            stats[vis].gf += gv; stats[vis].gc += gl;

                            if (gl > gv) {
                                stats[loc].g++; stats[loc].pts += 3;
                                stats[vis].p++;
                            } else if (gl < gv) {
                                stats[vis].g++; stats[vis].pts += 3;
                                stats[loc].p++;
                            } else {
                                stats[loc].e++; stats[vis].e++;
                                stats[loc].pts += 1; stats[vis].pts += 1;
                            }
                        }
                    }
                });

                // 3. Convertir a Array y Ordenar (Pts > Diferencia Goles > GF)
                const tablaOrdenada = Object.values(stats).sort((a, b) => {
                    if (b.pts !== a.pts) return b.pts - a.pts;
                    const difA = a.gf - a.gc;
                    const difB = b.gf - b.gc;
                    if (difB !== difA) return difB - difA;
                    return b.gf - a.gf;
                });

                // 4. Dibujar la tabla
                cuerpo.innerHTML = '';
                tablaOrdenada.forEach((team, index) => {
                    const difG = team.gf - team.gc;
                    cuerpo.innerHTML += `
                        <tr class="hover:bg-blue-500/5 transition-colors">
                            <td class="px-4 py-4 text-center text-slate-500 font-bold text-xs">${index + 1}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="${team.escudo}" class="size-6 object-contain">
                                    <span class="text-white font-bold text-xs uppercase tracking-tight">${team.nombre}</span>
                                </div>
                            </td>
                            <td class="px-2 py-4 text-center text-slate-300 text-xs">${team.pj}</td>
                            <td class="px-2 py-4 text-center text-slate-400 text-xs hidden md:table-cell">${team.g}</td>
                            <td class="px-2 py-4 text-center text-slate-400 text-xs hidden md:table-cell">${team.e}</td>
                            <td class="px-2 py-4 text-center text-slate-400 text-xs hidden md:table-cell">${team.p}</td>
                            <td class="px-3 py-4 text-center font-black text-white text-sm">${team.pts}</td>
                            <td class="px-2 py-4 text-center text-slate-400 text-xs">${team.gf}</td>
                            <td class="px-2 py-4 text-center text-slate-400 text-xs">${team.gc}</td>
                            <td class="px-2 py-4 text-center font-bold ${difG > 0 ? 'text-green-500' : difG < 0 ? 'text-red-500' : 'text-slate-500'} text-xs">
                                ${difG > 0 ? '+' + difG : difG}
                            </td>
                        </tr>
                    `;
                });

            } catch (e) { console.error("Error tabla posiciones:", e); }
        };

   
   window.ejecutarGuardadoCancha = async function(id, data, nuevaSedeId = null) {
        if(nuevaSedeId) data.nueva_sede_id = nuevaSedeId;

        // Si hay un ID, es actualización (PUT), si no, es registro (POST)
        const url = id ? `/api/admin/campos/actualizar/${id}` : '/api/admin/campos/registrar';
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await res.json();

            if (res.ok) {
                alert('✅ Operación exitosa');
                if(window.modalReasignarSede) window.cerrarModalReasignar();
                cerrarModalCampo();
                window.cargarCamposCards();
                window.editCampoId = null; // Limpiamos el ID después del éxito
                return;
            } 
            
            // MANEJO DE CONFLICTOS POR MANTENIMIENTO (422)
            if (res.status === 422 && result.error === 'conflictos_mantenimiento') {
                
                // Abrimos modal pasando TRUE para modo mantenimiento
                window.abrirModalReasignacion(id, result.partidos, true);
                
                // Re-vinculamos el botón del modal para que use el MISMO ID
                const btnConfirmar = document.getElementById('btnConfirmarBorradoEspecial');
                btnConfirmar.onclick = () => {
                    const sedeDestino = document.getElementById('selectNuevaSedeBorrado').value;
                    if(!sedeDestino) return alert("⚠️ Selecciona una sede de destino");
                    
                    // IMPORTANTE: Pasamos el 'id' original para que no cree uno nuevo
                    window.ejecutarGuardadoCancha(id, data, sedeDestino);
                };
            } else {
                alert("❌ " + (result.error || "Error al procesar la solicitud"));
            }
        } catch (e) { 
            console.error("Error en el guardado:", e);
        }
    };

    window.eliminarCampo = async function(campoId, nuevaSedeId = null) {
        // Si no es una confirmación de reasignación, pedimos confirmación básica primero
        if (!nuevaSedeId) {
            if (!confirm("¿Estás seguro de eliminar esta sede? Esta acción no se puede deshacer.")) return;
        }

        try {
            const options = {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            };

            if(nuevaSedeId) options.body = JSON.stringify({ nueva_sede_id: nuevaSedeId });

            const response = await fetch(`/api/admin/campos/eliminar/${campoId}`, options);
            const result = await response.json();

            if (response.ok) {
                alert('✅ Sede eliminada correctamente.');
                if(window.modalReasignarSede) cerrarModalReasignar();
                window.cargarCamposCards();
            } 
            else if (result.error === 'conflictos_detectados' || response.status === 422) {
                // 1. Personalizar el Modal específicamente para ELIMINACIÓN
                document.getElementById('reasignarTitulo').innerText = "⚠️ Reasignación Obligatoria";
                document.getElementById('reasignarDesc').innerText = "Esta sede tiene partidos pendientes que deben moverse antes de eliminarla permanentemente.";
                
                const btnConfirmar = document.getElementById('btnConfirmarBorradoEspecial');
                btnConfirmar.innerText = "Reasignar y Eliminar Sede";
                // Color ROJO para eliminación
                btnConfirmar.className = "flex-1 bg-red-600 hover:bg-red-500 text-white font-bold text-xs py-3 rounded-xl uppercase transition shadow-lg shadow-red-900/20";

                // 2. Abrir el modal (usa tu función existente que carga las sedes disponibles)
                window.abrirModalReasignacion(campoId, result.partidos);

                // 3. Re-configurar el botón para que al confirmar llame de nuevo a eliminarCampo
                btnConfirmar.onclick = () => {
                    const sedeDestino = document.getElementById('selectNuevaSedeBorrado').value;
                    if(!sedeDestino) return alert("⚠️ Selecciona una sede destino para los partidos.");
                    window.eliminarCampo(campoId, sedeDestino);
                };
            } 
            else {
                alert('❌ ' + (result.error || "Error al eliminar"));
            }
        } catch (e) { 
            console.error("Error en eliminación:", e);
            alert("❌ Error de conexión");
        }
    };

    // Cargar Canchas en el Tab
    // FUNCIÓN AUXILIAR: Centraliza la obtención de datos
    async function obtenerDatosCampos(forzar = false) {
        if (!cacheCamposData || forzar) {
            const res = await fetch('/api/campos');
            cacheCamposData = await res.json();
        }
        return cacheCamposData;
    }

    // 1. Corregir cargarCamposCards (La pestaña de Canchas)
    window.cargarCamposCards = async function(forzar = false) {
        const contenedor = document.getElementById('listaCamposCards');
        if(!contenedor) return;

        try {
            const campos = await obtenerDatosCampos(forzar); // Usa caché o pide nuevos
            contenedor.innerHTML = '';
            
            for (const id in campos) {
                const c = campos[id];
                const isMantenimiento = c.estado === 'mantenimiento';
                
                contenedor.innerHTML += `
                    <div class="bg-slate-900 border ${isMantenimiento ? 'border-amber-500/50' : 'border-slate-800'} p-4 rounded-xl flex justify-between items-center transition-all">
                        <div class="flex items-center gap-3">
                            <div class="size-2 rounded-full ${isMantenimiento ? 'bg-amber-500 animate-pulse' : 'bg-green-500'}"></div>
                            <div>
                                <h4 class="text-white font-bold uppercase text-sm">${c.nombre}</h4>
                                <p class="text-slate-500 text-[10px] uppercase">${c.lugar} ${isMantenimiento ? '• <span class="text-amber-500 text-[8px] font-black">MANTENIMIENTO</span>' : ''}</p>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <button onclick="prepararEdicionCampo('${id}', '${c.nombre}', '${c.lugar}', '${c.estado}')" class="text-blue-500 hover:bg-blue-500/10 p-2 rounded-lg transition">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button onclick="eliminarCampo('${id}')" class="text-red-500 hover:bg-red-500/10 p-2 rounded-lg transition">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>`;
            }
        } catch (e) { console.error("Error cargando cards de campos:", e); }
    };

    // 2. Corregir llenarSelectsCampos (Los formularios)
    window.llenarSelectsCampos = async function() {
        const select = document.getElementById('selectCampos');
        if(!select) return;

        try {
            const campos = await obtenerDatosCampos(); // Usa la misma caché
            select.innerHTML = '<option value="">Selecciona Cancha</option>';

            for (const id in campos) {
                const campo = campos[id];
                const isMantenimiento = campo.estado === 'mantenimiento';
                const opt = document.createElement('option');
                
                opt.value = id;
                if (isMantenimiento) {
                    opt.textContent = "🚧 " + campo.nombre.toUpperCase() + " (EN MANTENIMIENTO)";
                    opt.disabled = true;
                    opt.className = "text-slate-500 bg-slate-900 italic";
                } else {
                    opt.textContent = "✅ " + campo.nombre.toUpperCase() + " (" + campo.lugar.toUpperCase() + ")";
                    opt.className = "text-white";
                }
                select.appendChild(opt);
            }
        } catch (e) { console.error("Error al llenar select de campos:", e); }
    };


        // Funciones para el Modal de Edición
    window.prepararEdicionCampo = function(id, nombre, lugar, estado) {
        window.editCampoId = id; // Asignación global
        const form = document.getElementById('formCrearCampo');
        form.nombre.value = nombre;
        form.lugar.value = lugar;
        
        const containerEstado = document.getElementById('estadoCampoContainer');
        if(containerEstado) containerEstado.classList.remove('hidden');
        
        document.getElementById('selectEstadoCampo').value = estado || 'disponible';
        document.querySelector('#modalCrearCampo h3').innerText = 'Editar Sede';
        
        // Cambiar el texto del botón a "GUARDAR CAMBIOS"
        document.querySelector('#formCrearCampo button[type="submit"]').innerText = "GUARDAR CAMBIOS";
        
        abrirModalCampo();
    };

    document.getElementById('formCrearCampo').onsubmit = async (e) => {
        e.preventDefault();
        
        // Aseguramos que tomamos el ID correcto de la variable global
        const idAEditar = window.editCampoId; 

        const data = {
            nombre: e.target.nombre.value,
            lugar: e.target.lugar.value,
            estado: document.getElementById('selectEstadoCampo').value || 'disponible'
        };

        // Llamamos a la función inteligente
        window.ejecutarGuardadoCancha(idAEditar, data);
    };
    
    window.cargarPartidosCards = async function() {
        const contenedor = document.getElementById('contenedorListaPartidos');
        if(!contenedor) return;

        try {
            const res = await fetch('/api/partidos');
            const partidos = await res.json();

            window.cachePartidosLista = Object.keys(partidos).map(id => ({
                id: id,
                ...partidos[id]
            }));
            aplicarFiltrosPartidos();
            
        } catch (e) { 
            console.error("Error cargando partidos:", e);
        }
    };

    window.abrirModalReasignacion = async function(campoId, partidosAfectados, esMantenimiento = false) {
        const modal = document.getElementById('modalReasignarSede');
        const lista = document.getElementById('listaPartidosAfectados');
        const select = document.getElementById('selectNuevaSedeBorrado');
        const btnConfirmar = document.getElementById('btnConfirmarBorradoEspecial');

        if (!modal || !lista || !select) return console.error("Faltan elementos del modal en el HTML");

        // 1. CONFIGURACIÓN DINÁMICA DE TEXTOS Y ESTILOS
        if (esMantenimiento) {
            document.getElementById('reasignarTitulo').innerText = "🚧 Mantenimiento Urgente";
            document.getElementById('reasignarDesc').innerText = "Hay partidos programados en esta sede. Debes moverlos a otra cancha para poder iniciar el mantenimiento.";
            btnConfirmar.innerText = "Reasignar y Cerrar para Mantenimiento";
            btnConfirmar.className = "flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold text-[10px] py-3 rounded-xl uppercase transition shadow-lg shadow-blue-900/20";
        } else {
            document.getElementById('reasignarTitulo').innerText = "⚠️ Reasignación Obligatoria";
            document.getElementById('reasignarDesc').innerText = "Esta sede tiene partidos pendientes que deben moverse a otra ubicación antes de eliminarla permanentemente.";
            btnConfirmar.innerText = "Reasignar y Eliminar Sede";
            btnConfirmar.className = "flex-1 bg-red-600 hover:bg-red-500 text-white font-bold text-[10px] py-3 rounded-xl uppercase transition shadow-lg shadow-red-900/20";
        }

        // 2. MOSTRAR PARTIDOS AFECTADOS
        lista.innerHTML = partidosAfectados.map(p => `
            <div class="flex items-center justify-between text-[10px] text-slate-300 bg-slate-800/50 p-2 rounded-lg border border-slate-700/50">
                <span><span class="text-blue-500">⚽</span> ${p.resumen}</span>
                <span class="font-bold text-blue-400">${p.fecha} | ${p.hora}</span>
            </div>
        `).join('');

        try {
            // 3. CARGAR SEDES DISPONIBLES (Validando estado y agenda)
            const [resC, resP] = await Promise.all([fetch('/api/campos'), fetch('/api/partidos')]);
            const campos = await resC.json();
            const todosLosPartidos = Object.values(await resP.json());

            select.innerHTML = '<option value="">Selecciona nueva sede...</option>';

            Object.entries(campos).forEach(([id, c]) => {
                if(id === campoId) return; // Saltamos la sede origen

                const campoEnMantenimiento = c.estado === 'mantenimiento';
                let tieneConflictoAgenda = false;

                // Validar conflictos de horario
                partidosAfectados.forEach(pA => {
                    const choque = todosLosPartidos.find(pE => 
                        pE.campo_id === id && pE.fecha === pA.fecha &&
                        Math.abs(((parseInt(pE.hora.split(':')[0])*60)+parseInt(pE.hora.split(':')[1])) - ((parseInt(pA.hora.split(':')[0])*60)+parseInt(pA.hora.split(':')[1]))) < 100
                    );
                    if(choque) tieneConflictoAgenda = true;
                });

                const option = document.createElement('option');
                option.value = id;

                // Lógica de visualización y bloqueo en el select
                if (campoEnMantenimiento) {
                    option.innerText = `🚧 ${c.nombre} (MANTENIMIENTO)`;
                    option.disabled = true;
                    option.className = "text-slate-500 bg-slate-900 italic";
                } else if (tieneConflictoAgenda) {
                    option.innerText = `🚫 ${c.nombre} (OCUPADA)`;
                    option.disabled = true;
                    option.className = "text-slate-500 bg-slate-900";
                } else {
                    option.innerText = `✅ ${c.nombre} (Disponible)`;
                    option.className = "text-green-400 font-bold";
                }
                
                select.appendChild(option);
            });

            // 4. ABRIR EL MODAL
            const modalCrear = document.getElementById('modalCrearCampo');
            if(modalCrear) modalCrear.classList.replace('flex', 'hidden');
            
            modal.classList.replace('hidden', 'flex');

        } catch (e) { 
            console.error("Error al abrir reasignación:", e);
            alert("Error al cargar sedes disponibles.");
        }
    };

    window.generarTorneoAleatorio = async function() {
        if(!confirm("🎲 ¿Generar nuevo sorteo? Esto creará el rol de juegos para toda la temporada.")) return;

        try {
            // 1. Obtener equipos
            const resE = await fetch('/api/equipos');
            const equiposData = await resE.json();
            let equipos = Object.values(equiposData).map(e => e.nombre);

            if (equipos.length < 2) return alert("❌ Mínimo 2 equipos para sortear.");
            
            // Mezcla aleatoria
            equipos.sort(() => Math.random() - 0.5);

            // Manejo de impares
            if (equipos.length % 2 !== 0) equipos.push("DESCANSO");

            let partidosPaquete = [];
            const n = equipos.length;
            
            // 2. Algoritmo Round Robin
            for (let j = 0; j < n - 1; j++) {
                for (let i = 0; i < n / 2; i++) {
                    const loc = equipos[i];
                    const vis = equipos[n - 1 - i];
                    if (loc !== "DESCANSO" && vis !== "DESCANSO") {
                        partidosPaquete.push({ 
                            equipo_local: loc, 
                            equipo_visitante: vis, 
                            jornada: j + 1 
                        });
                    }
                }
                equipos.splice(1, 0, equipos.pop());
            }

            // 3. ENVIAR TODO EL PAQUETE (Ruta rápida)
            const res = await fetch('/api/admin/partidos/generar-torneo', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify({ partidos: partidosPaquete })
            });

            if(res.ok) {
                alert("🏆 ¡Torneo generado con éxito!");
                
                // 4. Dibujar el diagrama inmediatamente sin recargar
                window.pintarFixtureVisual(partidosPaquete);
                
                // 5. Actualizar la lista de la otra pestaña
                if(window.cargarPartidosCards) window.cargarPartidosCards();
            } else {
                const err = await res.json();
                alert("❌ Error: " + (err.error || "No se pudo generar"));
            }
        } catch (e) { 
            console.error(e); 
            alert("❌ Error de comunicación con el servidor");
        }
    };

    window.pintarFixtureVisual = function(partidos) {
        const contenedor = document.getElementById('contenedorFixture');
        if (!contenedor) return;

        contenedor.innerHTML = '<h3 class="text-white font-black uppercase text-center my-8 tracking-tighter text-xl">Fixture del Torneo</h3>';

        // Agrupar partidos por jornada
        const jornadas = {};
        partidos.forEach(p => {
            const jor = p.jornada || 1;
            if (!jornadas[jor]) jornadas[jor] = [];
            jornadas[jor].push(p);
        });

        // Dibujar cada jornada
        Object.keys(jornadas).forEach(numJor => {
            let htmlJornada = `
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl mb-6">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="bg-blue-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase">Semana ${numJor}</span>
                        <div class="h-px bg-slate-800 flex-1"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            `;

            jornadas[numJor].forEach(p => {
                htmlJornada += `
                    <button onclick="window.abrirAsignacionRapida('${p.equipo_local}', '${p.equipo_visitante}', '${p.id}')" 
                            class="w-full flex items-center justify-between bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 hover:border-blue-500 hover:bg-blue-500/5 transition group text-left">
                        <div class="flex-1 text-right font-bold text-white text-[11px] uppercase truncate group-hover:text-blue-400">${p.equipo_local}</div>
                        <div class="px-4 text-blue-500 font-black text-[9px] italic">VS</div>
                        <div class="flex-1 text-left font-bold text-white text-[11px] uppercase truncate group-hover:text-blue-400">${p.equipo_visitante}</div>
                    </button>
                `;
            });

            htmlJornada += `</div></div>`;
            contenedor.innerHTML += htmlJornada;
        });
    };

    window.recuperarFixtureGuardado = async function() {
        try {
            const res = await fetch('/api/partidos');
            const partidosData = await res.json();
            // Convertimos el objeto de Firebase en Array
            const partidos = Object.keys(partidosData).map(id => ({ id, ...partidosData[id] }));

            if (partidos.length > 0) {
                // Si ya existen partidos en la DB, dibujamos el diagrama
                window.pintarFixtureVisual(partidos);
            }
        } catch (e) { 
            console.error("Error al recuperar el fixture:", e); 
        }
    };

    window.limpiarTodo = async function() {
        if(!confirm("⚠️ ¿BORRAR TODO? Esta acción eliminará todos los partidos del torneo actual. No se puede deshacer.")) return;

        try {
            const res = await fetch('/api/admin/partidos/limpiar-todo', {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            if(res.ok) {
                alert("🧹 Torneo limpiado correctamente.");
                // Limpiamos el contenedor visual
                const contenedor = document.getElementById('contenedorFixture');
                if(contenedor) contenedor.innerHTML = '';
                
                // Actualizamos la pestaña de partidos
                if(window.cargarPartidosCards) window.cargarPartidosCards();
            } else {
                alert("❌ Error al intentar limpiar la base de datos.");
            }
        } catch (e) { 
            console.error(e);
            alert("❌ Error de comunicación");
        }
    };

    window.abrirAsignacionRapida = async function(local, visitante, partidoId) {
        const modal = document.getElementById('modalCrearPartido');
        if (!modal) return;

        // 1. Mostrar modal
        modal.classList.replace('hidden', 'flex');
        
        // 2. Cargar datos previos
        await window.llenarSelectsEquipos();
        await window.llenarSelectsCampos();
        const p = window.cachePartidosLista.find(item => item.id === partidoId);

        // 3. Referencias de los inputs
        const inputFecha = modal.querySelector('input[name="fecha"]');
        const inputHora = modal.querySelector('input[name="hora"]');
        const selCancha = document.getElementById('selectCampos');
        const btnSubmit = modal.querySelector('button[type="submit"]');

        // 4. Lógica de Bloqueo "En Vivo" o "Finalizado"
        // Si el partido está en curso o ya terminó, NO se puede mover la logística
        const estaBloqueado = p && (p.estatus === 'en_curso' || p.estatus === 'finalizado' || p.resultado_confirmado);

        if (estaBloqueado) {
            modal.querySelector('h3').innerText = "🚫 PROGRAMACIÓN BLOQUEADA (EN CURSO)";
            if(btnSubmit) btnSubmit.classList.add('hidden'); // Escondemos el botón de guardar
            
            // Bloqueamos los inputs
            [inputFecha, inputHora, selCancha].forEach(el => {
                if(el) {
                    el.disabled = true;
                    el.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });
        } else {
            // Si es programado o pendiente, restauramos todo
            modal.querySelector('h3').innerText = "📅 GESTIONAR PROGRAMACIÓN";
            if(btnSubmit) btnSubmit.classList.remove('hidden');
            
            [inputFecha, inputHora, selCancha].forEach(el => {
                if(el) {
                    el.disabled = false;
                    el.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });
        }

        // 5. Llenado de datos (Auto-completado)
        if (p) {
            if (inputFecha) inputFecha.value = p.fecha || '';
            if (inputHora) inputHora.value = p.hora || '';
            if (selCancha) selCancha.value = p.campo_id || '';
        }

        // Bloqueo permanente de equipos (esto siempre va)
        document.getElementById('selectLocal').disabled = true;
        document.getElementById('selectVisitante').disabled = true;

        window.idPartidoSorteo = partidoId;

        // Disparamos la agenda visual
        setTimeout(() => {
            if (typeof window.verificarConflictosInteligentes === 'function') {
                window.verificarConflictosInteligentes();
            }
        }, 200);
};

</script>
</body>
</html>