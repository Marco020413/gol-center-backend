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

        #overlay-carga {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8);
        display: none; /* Oculto por defecto */
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        }

        .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #334155;
        border-top: 5px solid #f59e0b; /* Color Ambar */
        border-radius: 50%;
        animation: spin 1s linear infinite;
        }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans antialiased">
    <div id="overlay-carga">
        <div class="spinner"></div>
        <p id="texto-carga" class="text-white font-black uppercase tracking-widest mt-4 text-[10px]">Cargando...</p>
    </div>

    <!-- Funciones de carga disponibles inmediatamente -->
    <script>
    // Función para mostrar pantalla de carga
    window.mostrarCarga = function(texto) {
        const loader = document.getElementById('overlay-carga');
        const textoEl = document.getElementById('texto-carga');
        if (loader) {
            if (textoEl && texto) textoEl.innerText = texto;
            loader.style.display = 'flex';
        }
    };

    // Función para ocultar pantalla de carga
    window.ocultarCarga = function() {
        const loader = document.getElementById('overlay-carga');
        if (loader) loader.style.display = 'none';
    };
    </script>

    <!-- Main App Script -->
    <script>
    // Pantalla de carga al inicio (admin)
    window.mostrarCarga('Cargando Panel de Administración...');

    // Ocultar cuando la página esté completamente cargada
    window.addEventListener('load', function() {
        setTimeout(() => window.ocultarCarga(), 500);
    });
    </script>
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
                <button onclick="logout()" id="logout-btn" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-semibold transition shadow-md shadow-blue-900/40 flex items-center gap-2">
                    <span id="logout-icon">Cerrar Sesión</span>
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
    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 1: UTILIDADES (Helpers)
    // ═══════════════════════════════════════════════════════════════════════════════
    // ChangeTab: Disponible inmediatamente para los botones del HTML
    window.changeTab = function(tabName) {
        localStorage.setItem('pestanaActiva', tabName);
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('text-blue-500', 'border-b-2', 'border-blue-500');
            b.classList.add('text-slate-500');
        });
        const target = document.getElementById('content-' + tabName);
        if(target) target.classList.remove('hidden');
        const btnActivo = document.getElementById('btn-tab-' + tabName);
        if(btnActivo) {
            btnActivo.classList.remove('text-slate-500');
            btnActivo.classList.add('text-blue-500', 'border-b-2', 'border-blue-500');
        }
        
        // CARGAR DATOS SEGÚN PESTAÑA
        const ahora = Date.now();
        const necesitaCarga = !ultimaCarga[tabName] || (ahora - ultimaCarga[tabName] > 10000);
        
        if (necesitaCarga) {
            switch(tabName) {
                case 'partidos': 
                    window.limitePartidos = 5;
                    if(typeof cargarPartidosCards === 'function') cargarPartidosCards(); 
                    break;
                case 'posiciones': 
                    if(window.cargarTablaPosiciones) window.cargarTablaPosiciones(); 
                    break;
                case 'equipos_gest': 
                    if(typeof cargarGestionEquipos === 'function') cargarGestionEquipos(); 
                    break;
                case 'roles': 
                    if(typeof recuperarFixtureGuardado === 'function') recuperarFixtureGuardado(); 
                    break;
                case 'campos':
                    if(typeof cargarCamposCards === 'function') cargarCamposCards();
                    break;
                case 'historial':
                    if(typeof cargarHistorialSaloDeLaFama === 'function') cargarHistorialSaloDeLaFama();
                    break;
            }
            ultimaCarga[tabName] = ahora;
        }
    };
    
    // Debounce: Evita excesivas llamadas durante búsquedas
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Notas: Intersection Observers removidos para evitar cargue automático undesired

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 2: VARIABLES DE ESTADO
    // ═══════════════════════════════════════════════════════════════════════════════
    let editMode = false;
    let editTelefono = null;
    let cachePartidos = [];
    let cachePartidosLista = []; 
    let editCampoId = null;
    let verificandoLiguilla = false;
    let ultimaCarga = {};
    let equiposCargados = false; 
    let limitePartidos = 5;
    let cacheEquiposData = null;
    let cacheCamposData = null;
    let limiteJugadores = 15;
    let cacheHistorialCompleto = null;

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 3: INICIALIZACIÓN (DOMContentLoaded)
    // ═══════════════════════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', async () => { 


        window.modalJugador = document.getElementById('modalJugador');
        window.modalEquipo = document.getElementById('modalEquipo');
        window.modalCrearPartido = document.getElementById('modalCrearPartido');
        window.modalActualizarMarcador = document.getElementById('modalActualizarMarcador');
        
        window.formJugador = document.getElementById('formRegistroJugador');
        window.formEquipo = document.getElementById('formRegistroEquipo');
        window.formPartidos = document.getElementById('formCrearPartido');
        window.formActualizar = document.getElementById('formActualizarMarcador');

        // 2. CARGA DE DATOS BASE (OPTIMIZADO: Parallel + Caching)
        // Cargar todos los datos base en paralelo una sola vez al inicio
        const datosBase = await Promise.all([
            fetch('/api/equipos').then(r => r.json()).catch(() => ({})),
            fetch('/api/campos').then(r => r.json()).catch(() => ({})),
            fetch('/api/partidos').then(r => r.json()).catch(() => ({}))
        ]);
        
        // Guardar en caché global para reuse
        window.cacheEquiposData = datosBase[0];
        window.cacheCamposData = datosBase[1];
        window.cachePartidosData = datosBase[2];
        
        // Procesar datos base
        window.llenarSelectsEquipos(); 
        if(typeof window.llenarSelectsCampos === 'function') window.llenarSelectsCampos();
        
        // Solo verificar liguilla una vez al inicio, no en cada cambio de pestaña
        if (!sessionStorage.getItem('liguillaVerificada')) {
            if(typeof window.recuperarFixtureGuardado === 'function') window.recuperarFixtureGuardado();
            if(typeof window.verificarProgresoLiguilla === 'function') window.verificarProgresoLiguilla();
            sessionStorage.setItem('liguillaVerificada', 'true');
        }

        // 3. PERSISTENCIA DE PESTAÑA (DESPUÉS DE CARGAR DATOS)
        // Ahora los datos ya están disponibles para cualquier pestaña
        const lastTab = localStorage.getItem('pestanaActiva') || 'jugadores';
        if (typeof window.changeTab === 'function') {
            window.changeTab(lastTab);
        }

        // 4. SANEADOR DE JUGADORES Y FILTRADO INICIAL
        const sanearYFiltrarTabla = async () => {
            // Solo ejecutar si existe la tabla de jugadores
            if (!document.getElementById('tablaPrincipalJugadores')) return;
            
            try {
                const res = await fetch('/api/equipos');
                const equiposActivos = await res.json();
                const nombresEquipos = Object.values(equiposActivos).map(e => e.nombre);

                document.querySelectorAll('#tablaPrincipalJugadores tbody tr').forEach(fila => {
                    const celdaEquipo = fila.querySelector('[data-field="equipo"]');
                    if (!celdaEquipo) return;
                    const valorEquipo = celdaEquipo.getAttribute('data-valor');

                    if (valorEquipo !== 'Libre' && !nombresEquipos.includes(valorEquipo) && valorEquipo !== "") {
                        celdaEquipo.innerHTML = `
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-slate-500 text-[10px] uppercase font-black">${valorEquipo}</span>
                                <span class="bg-amber-500/10 border border-amber-500/50 text-amber-500 text-[9px] px-2 py-0.5 rounded-full font-black animate-pulse">
                                    ⚠️ SIN EQUIPO
                                </span>
                            </div>`;
                        celdaEquipo.setAttribute('data-valor', 'Libre');
                    }
                });
                if (typeof filtrarTabla === 'function') filtrarTabla();
            } catch (e) { console.error("Error en saneado inicial:", e); }
        };
        setTimeout(sanearYFiltrarTabla, 300);

        // 5. LISTENERS DE BÚSQUEDA Y FILTROS (con debounce)
        const inputBusqueda = document.getElementById('busquedaJugador');
        const selectFiltroEquipo = document.getElementById('filtroEquipo');
        
        // Función de filtrado con reset de paginación
        const ejecutarFiltro = () => {
            window.limiteJugadores = 15;
            if (typeof filtrarTabla === 'function') filtrarTabla();
        };
        
        if (inputBusqueda) {
            // Aplicar debounce de 300ms para búsquedas en vivo
            inputBusqueda.addEventListener('input', debounce(ejecutarFiltro, 300));
        }
        if (selectFiltroEquipo) {
            selectFiltroEquipo.addEventListener('change', ejecutarFiltro);
        }

        // 6. FUNCIÓN PARA ABRIR MODAL CREAR PARTIDO
        window.abrirModalCrearPartido = function() {
            if (window.modalCrearPartido) {
                window.modalCrearPartido.classList.replace('hidden', 'flex');
                if (typeof window.llenarSelectsEquipos === 'function') window.llenarSelectsEquipos(); 
                if (typeof window.llenarSelectsCampos === 'function') window.llenarSelectsCampos(); 
            }
        };

        // 7. SENSORES DE AGENDA
        const selectCampos = document.getElementById('selectCampos');
        const inputFecha = document.querySelector('#formCrearPartido input[name="fecha"]');
        const inputHora = document.querySelector('#formCrearPartido input[name="hora"]');
        if(selectCampos) selectCampos.addEventListener('change', window.verificarConflictosInteligentes);
        if(inputFecha) inputFecha.addEventListener('change', window.verificarConflictosInteligentes);
        if(inputHora) inputHora.addEventListener('change', window.verificarConflictosInteligentes);

        // 8. LÓGICA DE FORMULARIO: REGISTRO / EDICIÓN JUGADORES
        if(window.formJugador) {
            window.formJugador.onsubmit = async (e) => {
                e.preventDefault();
                
                // 1. Identificar si es edición por el estado del input o la variable global
                const inputTelElement = window.formJugador.telefono;
                const esEdicion = inputTelElement.disabled || window.editMode === true;

                // 2. OBTENER EL TELÉFONO DE FORMA SEGURA (Evita el bug de 'undefined')
                // Si es edición, intentamos window.editTelefono, si no el value del input, si no el texto del modal
                let telefonoFinal = esEdicion 
                    ? (window.editTelefono || inputTelElement.value) 
                    : inputTelElement.value;

                if (!telefonoFinal || telefonoFinal === "" || telefonoFinal === "undefined") {
                    alert("❌ Error: No se pudo detectar el teléfono del jugador. Recarga la página.");
                    return;
                }

                const btn = document.getElementById('btnGuardar');
                btn.innerText = 'Procesando...'; 
                btn.disabled = true;

                const data = {
                    nombre: window.formJugador.nombre.value,
                    edad: window.formJugador.edad.value,
                    direccion: window.formJugador.direccion.value,
                    telefono: telefonoFinal,
                    equipo: document.getElementById('selectEquipos').value,
                    numero: window.formJugador.numero.value,
                    estatus: document.getElementById('edit_estatus').value, 
                    partidos_suspension: parseInt(document.getElementById('partidos_suspension').value) || 0
                };

                const url = esEdicion ? `/api/admin/jugadores/actualizar/${telefonoFinal}` : '/api/admin/jugadores/registrar';
                const method = esEdicion ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();

                    if (response.ok) { 
                        alert('✅ ¡Guardado con éxito!'); 
                        location.reload(); 
                    } else {
                        alert("⚠️ " + (result.error || "Error al procesar la solicitud"));
                        btn.disabled = false; 
                        btn.innerText = esEdicion ? 'Actualizar Datos' : 'Registrar Jugador';
                    }
                } catch (error) { 
                    alert('❌ Error de conexión'); 
                    btn.disabled = false; 
                    btn.innerText = 'Guardar';
                }
            };
        }

        // 9. LÓGICA DE FORMULARIO: REGISTRO DE EQUIPOS
        if(window.formEquipo) {
            window.formEquipo.onsubmit = async (e) => {
                e.preventDefault();
                const data = new FormData(window.formEquipo);
                try {
                    const response = await fetch('/api/admin/equipos/registrar', {
                        method: 'POST',
                        body: data,
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (response.ok) { alert('🏆 Equipo creado'); location.reload(); }
                } catch (error) { alert('❌ Error'); }
            };
        }

        // 10. LÓGICA DE FORMULARIO: CREAR PARTIDO
        if (window.formPartidos) {
            window.formPartidos.addEventListener('submit', async (e) => {
                e.preventDefault();
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
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(data)
                    });
                    if (res.ok) { alert("✅ ¡Éxito!"); location.reload(); }
                } catch (e) { alert("Error"); }
            });
        }

        // 11. LÓGICA DE FORMULARIO: ACTUALIZAR MARCADOR

        if (window.formActualizar) {
            window.formActualizar.onsubmit = async (e) => {
                e.preventDefault();

                // 1. Verificar si se marcó cerrar acta
                const checkCerrar = document.getElementById('confirmar_final'); // O 'confirmar_final' según tu ID de HTML
                const esFinal = checkCerrar?.checked || false;

                // 2. ALERT DE SEGURIDAD (Solo si esFinal es true)
                if (esFinal) {
                    const mensaje = "⚠️ ATENCIÓN: Estás marcando este partido como CERRADO.\n\n" +
                                "- Se bloqueará la edición futura.\n" +
                                "- Se sumarán los goles a los jugadores.\n" +
                                "- Se actualizarán los puntos en la tabla.\n\n" +
                                "¿Estás seguro de que los datos son correctos?";
                    
                    if (!confirm(mensaje)) {
                        return; // Si el usuario cancela, no se envía nada
                    }
                }

                const listaEstadisticas = {};
                document.querySelectorAll('.fila-jugador-cedula').forEach(fila => {
                    const tel = fila.dataset.telefono;
                    const asistio = fila.querySelector('.check-asistencia').checked;
                    const goles = fila.querySelector('.input-gol-jugador').value;
                    listaEstadisticas[tel] = { asistio, goles: parseInt(goles) || 0 };
                });

                const id = document.getElementById('edit_partido_id').value;
                const data = {
                    goles_local: document.getElementById('goles_local').value,
                    goles_visitante: document.getElementById('goles_visitante').value,
                    confirmar_final: esFinal,
                    detalle_jugadores: listaEstadisticas 
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
                        location.reload(); 
                    } else {
                        const errorData = await res.json();
                        alert("⚠️ Error: " + (errorData.error || "No se pudo actualizar"));
                    }
                } catch (e) { 
                    alert("Error de conexión"); 
                }
            };
        }

        // 12. CARGAS ADICIONALES (Opcionales, en segundo plano)
        // Cargamos lo que el usuario NO está viendo para que ya esté listo cuando cambie
        setTimeout(() => {
            if (lastTab !== 'posiciones' && window.cargarTablaPosiciones) window.cargarTablaPosiciones();
            if (lastTab !== 'equipos_gest' && typeof cargarGestionEquipos === 'function') cargarGestionEquipos();
        }, 2000);
        
    });
    
    window.addEventListener('focus', () => {
        const ultimaCarga = localStorage.getItem('ultima_actividad');
        const ahora = Date.now();
        
        if (ultimaCarga && (ahora - ultimaCarga > 1800000)) { 
            console.log("Regresando de inactividad... refrescando datos.");
            location.reload(); 
        }
        localStorage.setItem('ultima_actividad', Date.now());
    });

    async function cargarDatosSeguros(url, funcionPintar) {
        try {
            const res = await fetch(url);
            if(!res.ok) throw new Error("Error de servidor");
            const data = await res.json();
            funcionPintar(data);
        } catch (error) {
            // En lugar de morir en consola, avisamos al usuario
            document.getElementById('status-api').innerHTML = `
                <span class="text-red-500 text-xs">⚠️ Error de conexión. 
                    <button onclick="location.reload()" class="underline">Reintentar</button>
                </span>`;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 4: MODALES (Abrir/Cerrar)
    // ═══════════════════════════════════════════════════════════════════════════════
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
        const form = document.getElementById('formRegistroEquipo');
        const titulo = document.getElementById('tituloModalEquipo');
        const preview = document.getElementById('previewContenedor');
        const inputId = document.getElementById('equipo_id_edit');
        const selectPortero = document.getElementById('selectPortero');
        const btn = document.getElementById('btnGuardarEquipo');

        if(modal) {
            // RESET TOTAL: Forzamos modo creación
            editMode = false; 
            if(form) form.reset();
            if(inputId) inputId.value = '';
            if(titulo) titulo.innerText = 'Nuevo Equipo';
            if(preview) preview.classList.add('hidden');

            // Modo creación: permitir crear equipo sin jugadores (se agregan después)
            if(selectPortero) {
                selectPortero.disabled = true;
                selectPortero.innerHTML = '<option value="">Agrega jugadores al equipo para elegir portero</option>';
            }
            if(btn) {
                btn.disabled = false;
                btn.innerText = 'GUARDAR EQUIPO';
            }
            
            // Ocultar indicador de jugadores en creación
            const infoBox = document.getElementById('equipoJugadoresInfo');
            if(infoBox) infoBox.classList.add('hidden');
            
            equipoState.porterosCargados = true;
            equipoState.jugadoresCount = 0;

            // Mostrar modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Cargar galería por si acaso
            if(typeof cargarGaleriaEscudos === 'function') cargarGaleriaEscudos();
        }
    };

    window.cerrarModalEquipo = function() { 
        const modal = document.getElementById('modalEquipo');
        if(modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    };

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 5: JUGADORES (CRUD, edición, filtros)
    // ═══════════════════════════════════════════════════════════════════════════════
    window.toggleCamposSuspension = function() {
        const estatus = document.getElementById('edit_estatus').value;
        const contenedor = document.getElementById('contenedorPartidosSuspension');
        if (estatus === 'suspendido') {
            contenedor.classList.remove('hidden');
        } else {
            contenedor.classList.add('hidden');
            document.getElementById('partidos_suspension').value = 0;
        }
    };

    window.editarJugador = async function(telefono, nombre, equipo, edad, direccion, numero, pj, estatus, partidosSuspension) {
        // 1. VARIABLES DE CONTROL (CRÍTICO)
        editMode = true;
        editTelefono = telefono;
        
        // 2. CONFIGURACIÓN VISUAL E INICIO DE BLOQUEO
        document.querySelector('#modalJugador h3').innerText = 'Editar Jugador';
        const f = window.formJugador; 
        const btnGuardar = document.getElementById('btnGuardar');
        const selectEquipo = document.getElementById('selectEquipos');
        const aviso = document.getElementById('avisoEquipoBloqueado');

        // Bloqueamos para que el admin no guarde datos incompletos
        btnGuardar.disabled = true;
        btnGuardar.innerText = "⏳ Cargando...";
        btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');

        // 3. LLENADO DE CAMPOS BÁSICOS
        f.nombre.value = nombre;
        f.telefono.value = telefono;
        f.telefono.disabled = true; 
        f.edad.value = edad;
        f.direccion.value = direccion;
        f.numero.value = numero; 

        // 4. ESTATUS Y SUSPENSIÓN
        const selectEstatus = document.getElementById('edit_estatus');
        const estatusNormalizado = (estatus || 'activo').toLowerCase();
        console.log('Estatus recibido:', estatus, '-> normalizado:', estatusNormalizado);
        if(selectEstatus) {
            // Verificar que el valor exista en las opciones
            const opciones = Array.from(selectEstatus.options).map(o => o.value);
            console.log('Opciones disponibles:', opciones);
            
            if (opciones.includes(estatusNormalizado)) {
                selectEstatus.value = estatusNormalizado;
            } else {
                selectEstatus.value = 'activo';
            }
            document.getElementById('partidos_suspension').value = partidosSuspension || 0;
            if(typeof window.toggleCamposSuspension === 'function') window.toggleCamposSuspension(); 
        }

        // 5. MOSTRAR MODAL INMEDIATAMENTE (Velocidad visual)
        window.modalJugador.classList.replace('hidden', 'flex');

        // 6. CARGAR EQUIPOS (Esperamos a que la lista exista en el DOM)
        if (selectEquipo.options.length <= 1) {
            await cargarEquipos(); 
        }
        
        // --- AQUÍ EMPIEZA TU LÓGICA ORIGINAL SIN CAMBIAR UNA COMA ---
        
        // Normalizamos para comparar sin errores de mayúsculas/minúsculas
        const equipoNormalizado = (equipo || '').trim().toLowerCase();
        const equipoExiste = Array.from(selectEquipo.options).some(opt => opt.value.trim().toLowerCase() === equipoNormalizado);
        
        // Condición especial: ¿Es un equipo que ya no existe?
        const esFantasma = !equipoExiste && equipoNormalizado !== 'libre' && equipoNormalizado !== '';

        // 5. Lógica de Bloqueo / Desbloqueo Inteligente
        if (equipoNormalizado === 'libre' || equipoNormalizado === '' || esFantasma) {
            selectEquipo.disabled = false;
            selectEquipo.classList.remove('opacity-50');
            if(aviso) aviso.classList.add('hidden');
            
            if (esFantasma && aviso) {
                aviso.classList.remove('hidden');
                aviso.innerText = `ℹ️ El equipo "${equipo}" fue eliminado. Puedes reasignar al jugador.`;
                aviso.className = "text-[10px] text-blue-400 mt-2 font-bold italic";
                f.equipo.value = 'Libre';
            } else {
                f.equipo.value = equipo || 'Libre';
            }
        } 
        else if (parseInt(pj) >= 5 && equipoExiste) {
            selectEquipo.disabled = true;
            selectEquipo.classList.add('opacity-50');
            f.equipo.value = equipo;
            
            if(aviso) {
                aviso.classList.remove('hidden');
                aviso.innerText = "⚠️ Equipo bloqueado: El jugador ya tiene historial en este equipo.";
                aviso.className = "text-[10px] text-amber-500 mt-2 font-bold";
            }
        } 
        else {
            selectEquipo.disabled = false;
            selectEquipo.classList.remove('opacity-50');
            f.equipo.value = equipo || 'Libre';
            if(aviso) aviso.classList.add('hidden');
        }
        // --- FIN DE TU LÓGICA ORIGINAL ---

        // 8. DESBLOQUEO FINAL DEL BOTÓN
        btnGuardar.disabled = false;
        btnGuardar.innerText = "GUARDAR JUGADOR";
        btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed');
    };

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

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 10: HISTORIAL (Salón de la Fama)
    // ═══════════════════════════════════════════════════════════════════════════════
    async function cargarGaleriaEscudos() {
        const contenedor = document.getElementById('contenedorEscudos');
        if(!contenedor) return;

        try {
            const response = await fetch('/api/equipos/escudos');
            const escudos = await response.json();
            contenedor.innerHTML = ''; 

            if (escudos.length === 0) {
                contenedor.innerHTML = '<p class="col-span-4 text-[10px] text-slate-500 italic py-4">No hay escudos.</p>';
                return;
            }

            const escudoActual = window.escudoSeleccionado || equipoState?.escudoRespaldo || '';
            
            escudos.forEach((url) => {
                const nombreArchivo = url.split('/').pop();
                const nombreLimpio = nombreArchivo.includes('_') ? nombreArchivo.split('_').slice(1).join('_') : nombreArchivo;
                
                // Verificar si es el escudo actual del equipo
                const esActivo = escudoActual && url === escudoActual;

                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <button onclick="eliminarArchivoEscudo('${nombreArchivo}')" 
                            class="absolute -top-1 -right-1 bg-red-600 text-white rounded-full size-4 text-[8px] flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10">
                        ✕
                    </button>
                    
                    <label class="cursor-pointer">
                        <input type="radio" name="escudo_url" value="${url}" class="hidden peer" ${esActivo ? 'checked' : ''} onchange="mostrarPreview('${url}', '${nombreArchivo}'); window.escudoSeleccionado = '${url}';">
                        <img src="${url}" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5323/5323982.png'" class="size-12 mx-auto object-contain ${esActivo ? 'border-2 border-blue-500' : ''} rounded-lg bg-white/10 hover:scale-105 transition">
                        <p class="text-[6px] mt-1 uppercase truncate text-slate-500 text-center ${esActivo ? 'text-blue-400' : ''}">${nombreLimpio}</p>
                    </label>
                `;
                contenedor.appendChild(div);
            });
        } catch (e) { console.error("Error al cargar escudos:", e); }
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
        
        // Anclar escudo seleccionado
        window.escudoSeleccionado = url;
    }
    

    // ═══════════════════════════════════════════════════════════════════════════════
    // EQUIPOS - Estado y Elementos
    // ═══════════════════════════════════════════════════════════════════════════════
    const equipoElements = {
        modal: () => document.getElementById('modalEquipo'),
        btn: () => document.getElementById('btnGuardarEquipo'),
        inputNombre: () => document.getElementById('nombreEquipoInput'),
        selectPortero: () => document.getElementById('selectPortero'),
        inputId: () => document.getElementById('equipo_id_edit'),
        titulo: () => document.getElementById('tituloModalEquipo'),
        form: () => document.getElementById('formRegistroEquipo')
    };

    let equipoState = {
        editMode: false,
        guardando: false,
        porterosCargados: false,
        escudoRespaldo: '',
        escudoSeleccionado: '',
        equipoData: null
    };

    async function editarEquipo(id, nombre, escudo) {
        equipoState.editMode = !!id;
        equipoState.guardando = false;
        equipoState.porterosCargados = false;

        const data = window.equiposData?.[id] || { nombre, escudo };
        equipoState.equipoData = data;
        equipoState.escudoRespaldo = data.escudo || '';
        
        // GUARDAR escudo actual para que no se borre al guardar
        window.escudoSeleccionado = data.escudo || '';
        equipoState.escudoSeleccionado = data.escudo || '';

        // Preview: si es edición, mostrar escudo actual; si es nuevo, ocultar
        const previewContenedor = document.getElementById('previewContenedor');
        const previewImg = document.getElementById('imgPreview');
        const previewTxt = document.getElementById('namePreview');
        
        if (id && data.escudo) {
            // Mostrar escudo actual en edición
            if (previewContenedor && previewImg && previewTxt) {
                previewContenedor.classList.remove('hidden');
                previewContenedor.classList.add('flex');
                previewImg.src = data.escudo;
                previewTxt.innerText = data.escudo.split('/').pop();
            }
        } else if (previewContenedor) {
            // Ocultar en nuevo equipo
            previewContenedor.classList.add('hidden');
            previewContenedor.classList.remove('flex');
        }
        
        // UI Inicial
        equipoElements.titulo().innerText = id ? 'Editar Equipo' : 'Nuevo Equipo';
        equipoElements.inputId().value = id || '';
        equipoElements.inputNombre().value = data.nombre || '';
        
        // Toggle Modal
        const modal = equipoElements.modal();
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Botón en estado de carga
        const btn = equipoElements.btn();
        btn.disabled = true;
        btn.innerText = id ? 'Cargando porteros...' : 'Guardar Equipo';

        if (typeof cargarGaleriaEscudos === 'function') cargarGaleriaEscudos();

        // Carga de porteros
        if (id && data.nombre) {
            // Modo edición: cargar porteros
            await cargarJugadoresEquipo(data.nombre);
        } else {
            // Modo creación: permitir crear sin jugadores
            const select = equipoElements.selectPortero();
            const btn = equipoElements.btn();
            
            select.disabled = true;
            select.innerHTML = '<option value="">Agrega jugadores al equipo para elegir portero</option>';
            
            equipoState.porterosCargados = true;
            equipoState.jugadoresCount = 0;
            btn.disabled = false;
            btn.innerText = 'GUARDAR EQUIPO';
            
            // Ocultar indicador en creation
            const infoBox = document.getElementById('equipoJugadoresInfo');
            if(infoBox) infoBox.classList.add('hidden');
        }
    }

    async function cargarJugadoresEquipo(equipoNombre) {
        const select = equipoElements.selectPortero();
        resetSelectPortero(false);

        const btn = equipoElements.btn();
        const infoBox = document.getElementById('equipoJugadoresInfo');
        const countSpan = document.getElementById('eqJugadoresCount');
        
        try {
            const res = await fetch('/api/jugadores?_=' + Date.now());
            const jugadores = await res.json();

            const fragment = document.createDocumentFragment();
            const porteroIdActual = equipoState.equipoData?.portero_id;

            const jugadoresEquipo = Object.entries(jugadores)
                .filter(([_, j]) => j.equipo === equipoNombre)
                .sort((a, b) => a[1].nombre.localeCompare(b[1].nombre));
            
            // Contador de jugadores
            const totalJugadores = jugadoresEquipo.length;
            equipoState.jugadoresCount = totalJugadores;
            
            // Actualizar indicador visual
            if (infoBox && countSpan) {
                countSpan.innerText = totalJugadores;
                if (totalJugadores < 11) {
                    infoBox.classList.remove('hidden');
                } else {
                    infoBox.classList.add('hidden');
                }
            }
            
            // Si no hay jugadores, mostrar mensaje
            if (totalJugadores === 0) {
                select.disabled = true;
                select.innerHTML = '<option value="">Asigna jugadores al equipo primero para elegir un portero</option>';
                equipoState.porterosCargados = true;
                btn.disabled = true; // Bloquear hasta tener 11+
                btn.innerText = `Requiere 11+ jugadores (${totalJugadores}/11)`;
                return;
            }

            // Si tiene menos de 11, informar pero permitir selección
            if (totalJugadores < 11) {
                btn.disabled = true;
                btn.innerText = `Faltan ${11 - totalJugadores} jugadores (${totalJugadores}/11)`;
            }

            // Habilitar y permitir onchange
            select.disabled = false;
            select.onchange = validarEstadoBoton;

            jugadoresEquipo.forEach(([telefono, j]) => {
                const opt = new Option(`${j.nombre} (#${j.numero || '?'})`, telefono);
                if (telefono === porteroIdActual) {
                    opt.selected = true;
                }
                fragment.appendChild(opt);
            });

            select.appendChild(fragment);
        } catch (e) {
            console.error('Error:', e);
        } finally {
            equipoState.porterosCargados = true;
            validarEstadoBoton();
        }
    }

    async function registrarNuevoEquipo() {
        const btn = document.getElementById('btnGuardarEquipo');
        
        if (equipoState.guardando) {
            return;
        }

        const nombre = document.getElementById('nombreEquipoInput').value.trim();
        if (!nombre) {
            return alert('⚠️ Nombre obligatorio');
        }

        // Validar 11+ jugadores solo en edición
        const selectPortero = document.getElementById('selectPortero');
        const tienePortero = selectPortero?.value && selectPortero.value !== '';
        const tiene11Jugadores = (equipoState.jugadoresCount || 0) >= 11;
        
        if (esEdicion && !tiene11Jugadores) {
            return alert(`⚠️ El equipo necesita al menos 11 jugadores (tienes ${equipoState.jugadoresCount || 0})`);
        }

        const tieneNuevaImagen = document.getElementById('inputEscudo')?.files?.length > 0;
        const nuevoEscudo = window.escudoSeleccionado || null;
        const escudoAnterior = equipoState.escudoRespaldo || null;

        const escudoFinal = nuevoEscudo || (esEdicion ? escudoAnterior : null);

        if (!escudoFinal && !tieneNuevaImagen && !esEdicion) {
            return alert('⚠️ Selecciona un escudo');
        }

        equipoState.guardando = true;
        
        if (btn) {
            btn.innerText = 'Verificando...';
            btn.disabled = true;
        }

        const equipoId = document.getElementById('equipo_id_edit').value;
        const form = document.getElementById('formRegistroEquipo');
        const data = new FormData(form);
        
        if (equipoId) {
            data.append('_method', 'PUT');
        }
        
        if (escudoFinal) {
            data.append('escudo_url', escudoFinal);
        }

const select = document.getElementById('selectPortero');
        const porteroId = select?.value;
        const porteroNombre = (select?.selectedOptions?.[0]?.text || '').replace(/\s*\(#[^)]*\)\s*/g, '').trim();
        
        
        if (porteroId && porteroNombre) {
            data.append('portero_id', porteroId);
            data.append('portero_nombre', porteroNombre);
        } else {
            console.log('✗ No se guardó portero');
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: data,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });

            if (response.ok) {
                cerrarModalEquipo();
                await cargarGestionEquipos();
                window.equiposData = null;
                window.escudoSeleccionado = '';
                alert('✅ Equipo guardado');
            } else {
                throw await response.json();
            }
        } catch (e) {
            alert('❌ Error: ' + (e.message || e.error || 'Error de conexión'));
            equipoState.guardando = false;
            if (btn) {
                btn.innerText = 'Guardar Equipo';
                btn.disabled = false;
            }
        }
    }

    function resetSelectPortero(disabled = true) {
        const select = equipoElements.selectPortero();
        select.innerHTML = '<option value="">-- Seleccionar jugador --</option>';
        select.disabled = disabled;
    }

    function validarEstadoBoton() {
        const btn = equipoElements.btn();
        const select = document.getElementById('selectPortero');
        const nombreValido = equipoElements.inputNombre().value.trim().length > 0;
        const tienePortero = select?.value && select.value !== '';
        const tiene11Jugadores = (equipoState.jugadoresCount || 0) >= 11;
        const esEdicion = !!document.getElementById('equipo_id_edit').value;
        const infoBox = document.getElementById('equipoJugadoresInfo');
        const countSpan = document.getElementById('eqJugadoresCount');
        
        // Actualizar indicador visual
        if (infoBox && countSpan) {
            countSpan.innerText = equipoState.jugadoresCount || 0;
            if (esEdicion && (equipoState.jugadoresCount || 0) < 11) {
                infoBox.classList.remove('hidden');
            } else {
                infoBox.classList.add('hidden');
            }
        }
        
        // Validación: creación permite guardar sin jugadores (se agregan después)
        // Edición requiere 11+ jugadores
        let puedeGuardar = nombreValido;
        
        if (esEdicion) {
            puedeGuardar = puedeGuardar && tiene11Jugadores;
        }
        
        btn.disabled = !puedeGuardar;
        
        if (!btn.disabled && !equipoState.guardando) {
            if (esEdicion && !tiene11Jugadores) {
                btn.innerText = `Faltan ${11 - (equipoState.jugadoresCount || 0)} jugadores`;
            } else {
                btn.innerText = 'GUARDAR EQUIPO';
            }
        }
    }

    function validarFormularioEquipo() {
        validarEstadoBoton();
    }

    function cerrarModalEquipo() {
        const modal = equipoElements.modal();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        equipoState = { 
            ...equipoState, 
            guardando: false, 
            porterosCargados: false,
            escudoSeleccionado: ''
        };
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 7: EQUIPOS (CRUD, gestión, escudos)
    // ═══════════════════════════════════════════════════════════════════════════════
    async function cargarGestionEquipos() {
        const contenedor = document.getElementById('listaEquiposCards');
        if(!contenedor) return;
        try {
            const response = await fetch('/api/equipos');
            const equipos = await response.json();
            contenedor.innerHTML = '';
            
            for (const id in equipos) {
                const eq = equipos[id];
                window.equiposData = window.equiposData || {};
                window.equiposData[id] = eq;
                contenedor.innerHTML += `
                    <div class="bg-slate-900 border border-slate-800 p-4 rounded-xl flex items-center justify-between shadow-lg hover:border-emerald-500/50 cursor-pointer transition" onclick="verJugadoresEquipo('${id}', '${eq.nombre}')">
                        <div class="flex items-center gap-4">
                            <img src="${eq.escudo}" class="size-12 object-contain bg-white/5 rounded-lg border border-slate-700">
                            <div>
                                <p class="font-bold text-white text-sm uppercase">${eq.nombre}</p>
                                <p class="text-[10px] text-slate-500">Toca para ver jugadores</p>
                            </div>
                        </div>
                        <div class="flex gap-2" onclick="event.stopPropagation()">
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
        } catch (e) { console.error("Error al cargar lista de equipos:", e); }
    }

    window.eliminarArchivoEscudo = async function(nombreArchivo) {
        if(!confirm(`¿Borrar permanentemente el archivo "${nombreArchivo}" del servidor?`)) return;

        try {
            const response = await fetch('/api/admin/escudos/eliminar', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ archivo: nombreArchivo })
            });

            if(response.ok) {
                alert('🗑️ Archivo eliminado');
                cargarGaleriaEscudos(); // Recargar solo la galería
            } else {
                alert('❌ Error al eliminar el archivo');
            }
        } catch (e) { console.error(e); }
    };

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
    
    // Lazy loading removido - solo botones manuales
    function filtrarTabla() {
        const inputBusqueda = document.getElementById('busquedaJugador');
        const selectEquipo = document.getElementById('filtroEquipo');
        const selectOrden = document.getElementById('ordenarPor');
        
        // Verificar que existan los elementos
        if (!inputBusqueda || !selectEquipo || !selectOrden) return;
        
        const busqueda = inputBusqueda.value.toLowerCase().trim();
        const equipoFiltro = selectEquipo.value; 
        const orden = selectOrden.value;
        
        const tablaBody = document.querySelector('#tablaPrincipalJugadores tbody');
        if (!tablaBody) return;

        const filas = Array.from(tablaBody.querySelectorAll('tr'));
        
        const filasQueCumplen = filas.filter(fila => {
            const nombreContenedor = fila.querySelector('[data-field="nombre"]');
            const nombreCompletoTexto = nombreContenedor?.innerText.toUpperCase() || "";
            const nombreSolo = nombreContenedor?.innerText.toLowerCase() || "";
            const telefono = fila.querySelector('[data-field="telefono"]')?.innerText.toLowerCase() || "";
            const equipoCelda = fila.querySelector('[data-field="equipo"]');
            const valorEquipo = equipoCelda ? equipoCelda.getAttribute('data-valor') : "";
            const nombreEquipoTexto = equipoCelda?.innerText.toLowerCase() || "";
            
            // Buscar badges de estado en toda la fila
            const filaHtml = fila.innerHTML.toUpperCase();
            const tieneLesionado = filaHtml.includes('LESIONADO');
            const tieneSuspendido = filaHtml.includes('SUSPENDIDO');

            const coincideBusqueda = nombreSolo.includes(busqueda) || 
                                    telefono.includes(busqueda) || 
                                    nombreEquipoTexto.includes(busqueda);
            
            let coincideFiltro = true;
            if (equipoFiltro === "Libre") {
                coincideFiltro = (valorEquipo === "Libre");
            } else if (equipoFiltro === "SUSPENDIDO") {
                coincideFiltro = tieneSuspendido;
            } else if (equipoFiltro === "LESIONADO") {
                coincideFiltro = tieneLesionado;
            } else if (equipoFiltro !== "") {
                coincideFiltro = (valorEquipo === equipoFiltro);
            }

            return coincideBusqueda && coincideFiltro;
        });

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

        const fragment = document.createDocumentFragment();
        filas.forEach(f => f.style.display = 'none');
        filasQueCumplen.slice(0, limiteJugadores).forEach(f => {
            f.style.display = '';
            fragment.appendChild(f);
        });
        tablaBody.appendChild(fragment);

        gestionarBotonVerMasJugadores(filasQueCumplen.length);
    }
    

    function gestionarBotonVerMasJugadores(totalFiltrados) {
        // Crear o actualizar el contenedor del botón
        let btnContenedor = document.getElementById('btnContenedorJugadores');
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
            btnContenedor.style.display = '';
        } else if (limiteJugadores > 15) {
            btnContenedor.innerHTML = `
                <button onclick="window.verMenosJugadores()" 
                    class="text-slate-500 hover:text-white text-[9px] font-bold uppercase tracking-widest transition-all">
                    ⬆️ Volver al principio
                </button>
            `;
            btnContenedor.style.display = '';
        } else {
            btnContenedor.style.display = 'none';
        }
    }

    window.cargarMasJugadores = function() {
        limiteJugadores += 15;
        filtrarTabla();
    };

    window.verMenosJugadores = function() {
        limiteJugadores = 15;
        filtrarTabla();
        document.getElementById('content-jugadores').scrollIntoView({ behavior: 'smooth' });
    };

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
            // USAR CACHÉ GLOBAL SI ESTÁ DISPONIBLE (ya cargado al inicio)
            if (!window.cacheEquiposData && !cacheEquiposData) {
                const res = await fetch('/api/equipos');
                cacheEquiposData = await res.json();
            }
            const datos = window.cacheEquiposData || cacheEquiposData;

            const selects = [selectLocal, selectVisitante];
            selects.forEach(s => {
                const currentVal = s.value;
                s.innerHTML = '<option value="">Selecciona un club</option>';
                for (const id in datos) {
                    const opt = document.createElement('option');
                    opt.value = datos[id].nombre;
                    opt.textContent = datos[id].nombre.toUpperCase();
                    s.appendChild(opt);
                }
                if(currentVal) s.value = currentVal;
            });
        } catch (e) { console.error("Error al llenar selects:", e); }
    };

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 6: PARTIDOS (CRUD, filtros, estadísticas)
    // ═══════════════════════════════════════════════════════════════════════════════
    async function cargarPartidosCards() {
        const contenedor = document.getElementById('contenedorListaPartidos');
        if (!contenedor) return;

        // Feedback visual inmediato
        contenedor.innerHTML = `
            <div class="col-span-full py-20 text-center animate-pulse">
                <div class="text-blue-500 font-black text-xs uppercase tracking-[0.3em]">Sincronizando Calendario...</div>
            </div>
        `;

        try {
            // USAR CACHÉ GLOBAL SI ESTÁ DISPONIBLE
            let partidos;
            if (window.cachePartidosData && Object.keys(window.cachePartidosData).length > 0) {
                partidos = window.cachePartidosData;
            } else {
                const res = await fetch('/api/partidos');
                if (!res.ok) {
                    contenedor.innerHTML = '<p class="text-red-500 text-[10px] text-center py-10">⚠️ Error del servidor. Intenta más tarde.</p>';
                    return;
                }
                try {
                    partidos = await res.json();
                } catch(e) {
                    console.error('Error parsing JSON:', e);
                    return;
                }
                window.cachePartidosData = partidos;
            }
            
            //Obtener equipos para escudos (si no están en cache)
            let equiposData = window.cacheEquiposData;
            if (!equiposData) {
                const resE = await fetch('/api/equipos');
                equiposData = await resE.json();
                window.cacheEquiposData = equiposData;
            }
            
            // Añadir escudos a cada partido
            const escudoDefault = 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png';
            for (const id in partidos) {
                const p = partidos[id];
                const idLocal = String(p.equipo_local || '').replace(/[^a-zA-Z0-9]/g, '-');
                const idVisitante = String(p.equipo_visitante || '').replace(/[^a-zA-Z0-9]/g, '-');
                
                if (equiposData && equiposData[idLocal]) {
                    p.escudo_local = equiposData[idLocal].escudo || escudoDefault;
                } else {
                    p.escudo_local = escudoDefault;
                }
                
                if (equiposData && equiposData[idVisitante]) {
                    p.escudo_visitante = equiposData[idVisitante].escudo || escudoDefault;
                } else {
                    p.escudo_visitante = escudoDefault;
                }
            }
            
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
                    <!-- Sentinel para Intersection Observer -->
                    <div class="sentinel-partidos" data-total="${totalEncontrados}" data-actual="${window.limitePartidos}"></div>
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
        inicializarObserverPartidos();
    };

    function inicializarObserverPartidos() {
        // Solo observar si hay más partidos para cargar
        function inicializarObserverPartidos() {
        // Funcionalidad deshabilitada - solo botones manuales
    }

    // Funciones de control global
    window.cargarMasPartidos = function() {
        // Verificar si ya se mostrou todo
        const totalCargado = window.limitePartidos;
        const hayMas = window.cachePartidosLista && totalCargado < window.cachePartidosLista.length;
        
        if (!hayMas) return;
        
        window.limitePartidos += 5;
        aplicarFiltrosPartidos();
    };

    window.verMenosPartidos = function() {
        window.limitePartidos = 5;
        aplicarFiltrosPartidos();
        document.getElementById('content-partidos').scrollTo({ top: 0, behavior: 'smooth' });
        inicializarObserverPartidos();
    };

    window.cerrarModalDetalle = function() {
        const modal = document.getElementById('modalDetallePartido');
        if (modal) {
            modal.classList.replace('flex', 'hidden');
        }
    };
    
    async function abrirActualizarMarcador(id) {
        const contenedor = document.getElementById('contenedorCedulaJugadores');
        try {
            if(contenedor) contenedor.innerHTML = '<p class="text-blue-500 animate-pulse text-center py-4 uppercase text-[10px]">Cargando datos del partido...</p>';
            window.modalActualizarMarcador.classList.replace('hidden', 'flex');

            const [resP, resJ] = await Promise.all([
                fetch('/api/partidos'),
                fetch('/api/jugadores')
            ]);

            if (!resP.ok || !resJ.ok) throw new Error("Error 500");

            const partidos = await resP.json();
            const todosLosJugadores = await resJ.json();
            const p = partidos[id];

            if(!p) {
                window.modalActualizarMarcador.classList.replace('flex', 'hidden');
                return alert("Partido no encontrado");
            }

            document.getElementById('edit_partido_id').value = id;
            document.getElementById('edit_labelLocal').innerText = p.equipo_local.toUpperCase();
            document.getElementById('edit_labelVisitante').innerText = p.equipo_visitante.toUpperCase();
            document.getElementById('goles_local').value = p.goles_local || 0;
            document.getElementById('goles_visitante').value = p.goles_visitante || 0;

            const statsGuardadas = p.detalle_jugadores || {};
            contenedor.innerHTML = ''; 

            const equipos = [
                { nombre: p.equipo_local, tipo: 'local', color: 'blue' },
                { nombre: p.equipo_visitante, tipo: 'visitante', color: 'red' }
            ];

            equipos.forEach(eq => {
                const jugadoresEquipo = Object.entries(todosLosJugadores)
                    .filter(([tel, j]) => j.equipo === eq.nombre)
                    .sort((a,b) => (a[1].numero || 0) - (b[1].numero || 0));

                const bajas = jugadoresEquipo.filter(([tel, j]) => j.estatus === 'suspendido' || j.estatus === 'lesionado').length;

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
                                ${bajas > 0 ? `<span class="text-[8px] text-red-400 font-bold ml-5 uppercase italic">⚠️ ${bajas} bajas</span>` : ''}
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
                            ${(esInactivo || !previa.asistio) ? 'opacity-30 grayscale' : ''}" id="fila_jugador_${tel}" data-telefono="${tel}">
                            <input type="checkbox" ${previa.asistio && !esInactivo ? 'checked' : ''} ${esInactivo ? 'disabled' : ''} 
                                class="check-asistencia size-4 rounded accent-green-500" onchange="window.toggleAsistencia('${tel}', this)">
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] text-white font-bold truncate uppercase">#${j.numero} ${j.nombre}</p>
                            </div>
                            <div class="flex items-center gap-1 bg-slate-950 rounded-lg p-1 border border-slate-800">
                                <button type="button" onclick="window.modificarGolJugador('${tel}', -1, '${eq.tipo}')" ${esInactivo ? 'disabled' : ''} class="size-6 flex items-center justify-center text-slate-400 hover:text-white rounded">-</button>
                                <input type="number" value="${previa.goles}" readonly id="goles_jugador_${tel}" 
                                    class="input-gol-jugador input-gol-${eq.tipo} w-7 bg-transparent text-center text-[11px] font-black text-blue-400 outline-none">
                                <button type="button" onclick="window.modificarGolJugador('${tel}', 1, '${eq.tipo}')" ${esInactivo ? 'disabled' : ''} class="size-6 flex items-center justify-center text-white bg-blue-600/20 hover:bg-blue-600 rounded">+</button>
                            </div>
                        </div>`;
                });
                htmlSeccion += `</div></div>`;
                contenedor.innerHTML += htmlSeccion;
            });

        } catch (e) { 
            console.error(e);
            alert("⚠️ Error de conexión con el servidor.");
            window.modalActualizarMarcador.classList.replace('flex', 'hidden');
        }
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

    window.cerrarModalCampo = function() { 
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
        
        // El ID del partido que estamos editando (para no chocar con él mismo)
        const idActual = window.idPartidoSorteo;

        const contenedor = document.getElementById('agendaCanchaContenedor');
        const listaAgenda = document.getElementById('listaAgendaCancha');
        const alerta = document.getElementById('alertaConflicto');

        // Si no hay datos suficientes, escondemos la agenda pero NO tocamos los inputs
        if (!campoId || !fecha) {
            if(contenedor) contenedor.classList.add('hidden');
            return;
        }

        try {
            // Indicador visual de carga sutil solo en la zona de la agenda
            listaAgenda.innerHTML = '<p class="text-[10px] text-blue-500 animate-pulse text-center py-2">Consultando disponibilidad...</p>';
            contenedor.classList.remove('hidden');

            const res = await fetch('/api/partidos');
            const partidos = await res.json();
            
            // Filtramos: Partidos en la misma sede/fecha QUE NO SEAN el que estamos editando actualmente
            const partidosHoy = Object.values(partidos).filter(p => 
                p.campo_id === campoId && 
                p.fecha === fecha && 
                p.id !== idActual
            );

            // Limpiamos solo el área de la lista, sin tocar el resto del modal
            listaAgenda.innerHTML = '';

            if (partidosHoy.length === 0) {
                listaAgenda.innerHTML = '<p class="text-[10px] text-green-500 font-bold text-center py-2">✅ Sede disponible para esta fecha</p>';
            } else {
                partidosHoy.sort((a,b) => a.hora.localeCompare(b.hora)).forEach(p => {
                    const [horas, minutos] = p.hora.split(':').map(Number);
                    let totalMinutosFin = (horas * 60) + minutos + 100;
                    const horasFin = Math.floor(totalMinutosFin / 60);
                    const minutosFin = totalMinutosFin % 60;
                    const horaFinFormateada = `${horasFin.toString().padStart(2, '0')}:${minutosFin.toString().padStart(2, '0')}`;

                    listaAgenda.innerHTML += `
                        <div class="flex justify-between items-center bg-slate-950/50 border border-slate-800 p-2 rounded-lg mb-1">
                            <div class="flex flex-col">
                                <span class="text-blue-400 font-bold text-xs">${p.hora} - ${horaFinFormateada}</span>
                                <span class="text-[8px] text-slate-500 uppercase">Ocupado (100 min)</span>
                            </div>
                            <span class="text-[9px] text-slate-400 uppercase truncate ml-4">${p.equipo_local} vs ${p.equipo_visitante}</span>
                        </div>`;
                });
            }

            // --- LÓGICA DE VALIDACIÓN DE CHOQUE (SÓLO AFECTA AL BOTÓN) ---
            if (horaNueva) {
                let choque = false;
                const [hN, mN] = horaNueva.split(':').map(Number);
                const totalMinNuevo = (hN * 60) + mN;

                partidosHoy.forEach(p => {
                    const [hP, mP] = p.hora.split(':').map(Number);
                    const totalMinPartido = (hP * 60) + mP;
                    
                    // Si la diferencia es menor a 100 minutos, hay conflicto
                    if (Math.abs(totalMinPartido - totalMinNuevo) < 100) { 
                        choque = true; 
                    }
                });

                if (choque) {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
                    btnSubmit.innerText = "❌ HORARIO OCUPADO";
                    if(alerta) alerta.classList.remove('hidden');
                } else {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnSubmit.innerText = idActual ? "GUARDAR CAMBIOS ⚽" : "CREAR ENCUENTRO ⚽";
                    if(alerta) alerta.classList.add('hidden');
                }
            }
        } catch (e) { 
            console.error("Error agenda:", e);
            listaAgenda.innerHTML = '<p class="text-[9px] text-red-500 text-center uppercase">Error al conectar con el servidor</p>';
        }
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

    // Tabla de Posiciones - Fase Regular
    window.cargarTablaPosiciones = function() {
        const cuerpo = document.getElementById('tablaCuerpoPosiciones');
        if(!cuerpo) return;

        // Usar cache local para evitar recálculos innecesarios
        if (window.cacheTablaPosiciones && window.datosTablaPosiciones) {
            cuerpo.innerHTML = window.cacheTablaPosiciones;
            return;
        }

        try {
            const equipos = window.cacheEquiposData || {};
            const partidos = window.cachePartidosData || {};

            let stats = {};
            for (const id in equipos) {
                stats[equipos[id].nombre] = {
                    nombre: equipos[id].nombre,
                    escudo: equipos[id].escudo,
                    pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0
                };
            }

            Object.values(partidos).forEach(partido => {
                const esFaseRegular = !isNaN(partido.jornada);
                if (partido.resultado_confirmado && esFaseRegular) {
                    const loc = partido.equipo_local;
                    const vis = partido.equipo_visitante;
                    const gl = parseInt(partido.goles_local || 0);
                    const gv = parseInt(partido.goles_visitante || 0);

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

            const tablaOrdenada = Object.values(stats).sort((a, b) => {
                if (b.pts !== a.pts) return b.pts - a.pts;
                const difA = a.gf - a.gc;
                const difB = b.gf - b.gc;
                if (difB !== difA) return difB - difA;
                return b.gf - a.gf;
            });

            const fragment = document.createDocumentFragment();
            tablaOrdenada.forEach((team, index) => {
                const difG = team.gf - team.gc;
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-blue-500/5 transition-colors border-b border-slate-800/50';
                tr.innerHTML = `
                    <td class="px-4 py-4 text-center text-slate-500 font-bold text-xs">${index + 1}</td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <img src="${team.escudo || 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png'}" class="size-6 object-contain" onerror="this.src='https://cdn-icons-png.flaticon.com/512/5323/5323982.png'">
                            <span class="text-white font-bold text-xs uppercase tracking-tight">${team.nombre}</span>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center text-slate-300 text-xs">${team.pj}</td>
                    <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">${team.g}</td>
                    <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">${team.e}</td>
                    <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">${team.p}</td>
                    <td class="px-2 py-4 text-center text-emerald-400 font-bold text-xs">${team.pts}</td>
                    <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">${team.gf}</td>
                    <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">${team.gc}</td>
                    <td class="px-2 py-4 text-center ${difG >= 0 ? 'text-emerald-400' : 'text-red-400'} font-bold text-xs">${difG > 0 ? '+' + difG : difG}</td>
                `;
                fragment.appendChild(tr);
            });

            cuerpo.innerHTML = '';
            cuerpo.appendChild(fragment);
            window.cacheTablaPosiciones = cuerpo.innerHTML;
            
            // Guardar datos para backup
            window.datosTablaPosiciones = tablaOrdenada;
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

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 9: CAMPOS/CANCHAS (CRUD, gestión)
    // ═══════════════════════════════════════════════════════════════════════════════
    async function obtenerDatosCampos(forzar = false) {
        // USAR CACHÉ GLOBAL SI ESTÁ DISPONIBLE
        if (!cacheCamposData && !window.cacheCamposData || forzar) {
            const res = await fetch('/api/campos');
            cacheCamposData = await res.json();
            window.cacheCamposData = cacheCamposData;
        }
        return window.cacheCamposData || cacheCamposData;
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

    
    window.limpiarTodo = async function() {
        // 1. Primer aviso de seguridad
        if(!confirm("⚠️ ¿BORRAR TODO? Esta acción eliminará todos los partidos del torneo actual. No se puede deshacer.")) return;

        // 2. Segundo aviso (Seguro de Salón de la Fama)
        if(!confirm("❓ ¿Ya archivaste este torneo en el Salón de la Fama? Si no lo hiciste, perderás las estadísticas de esta copa para siempre.")) return;

        try {
            const res = await fetch('/api/admin/partidos/limpiar-todo', {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            if(res.ok) {
                // --- LO MÁS IMPORTANTE ---
                localStorage.removeItem('torneo_finalizado'); // <--- Esto permite que la siguiente copa se guarde sola
                localStorage.removeItem('pestanaActiva'); 
                
                alert("🧹 Torneo limpiado y sistema reseteado para la próxima copa.");
                location.reload(); // Recargamos para que el generador aparezca limpio
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

        // 1. REFERENCIAS E INICIALIZACIÓN
        const selLocal = document.getElementById('selectLocal');
        const selVisitante = document.getElementById('selectVisitante');
        const contenedorAgenda = document.getElementById('agendaCanchaContenedor');
        const listaAgenda = document.getElementById('listaAgendaCancha');
        
        // Reset visual de la agenda para que no se mezcle con el partido anterior
        if (contenedorAgenda) contenedorAgenda.classList.add('hidden');
        if (listaAgenda) listaAgenda.innerHTML = '';

        // Mostrar modal inmediatamente
        modal.classList.replace('hidden', 'flex');

        // Bloqueo preventivo anti-lag
        if(selLocal) selLocal.disabled = true;
        if(selVisitante) selVisitante.disabled = true;

        try {
            // 2. CARGA DE DATOS (CACHE)
            if (!window.cachePartidosLista || window.cachePartidosLista.length === 0) {
                const res = await fetch('/api/partidos');
                const partidosData = await res.json();
                window.cachePartidosLista = Object.keys(partidosData).map(id => ({ id, ...partidosData[id] }));
            }

            const p = window.cachePartidosLista.find(item => item.id === partidoId);
            if (!p) return alert("No se encontraron los datos del partido.");

            // 3. CARGA DE CATÁLOGOS (SELECTS)
            await Promise.all([window.llenarSelectsEquipos(), window.llenarSelectsCampos()]);

            // 4. ASIGNACIÓN DE VALORES FIJOS
            if(selLocal) { selLocal.value = p.equipo_local; selLocal.disabled = true; }
            if(selVisitante) { selVisitante.value = p.equipo_visitante; selVisitante.disabled = true; }

            const inputFecha = modal.querySelector('input[name="fecha"]');
            const inputHora = modal.querySelector('input[name="hora"]');
            const selCancha = document.getElementById('selectCampos');
            const btnSubmit = modal.querySelector('button[type="submit"]');

            // 5. LÓGICA DE BLOQUEO POR ESTATUS
            const estaBloqueado = p.estatus === 'en_curso' || p.estatus === 'finalizado' || p.resultado_confirmado;

            if (estaBloqueado) {
                modal.querySelector('h3').innerText = "🚫 PROGRAMACIÓN BLOQUEADA";
                if(btnSubmit) btnSubmit.classList.add('hidden');
                [inputFecha, inputHora, selCancha].forEach(el => { if(el) el.disabled = true; });
            } else {
                modal.querySelector('h3').innerText = "📅 GESTIONAR PROGRAMACIÓN";
                if(btnSubmit) {
                    btnSubmit.classList.remove('hidden');
                    btnSubmit.innerText = "GUARDAR CAMBIOS ⚽";
                }
                [inputFecha, inputHora, selCancha].forEach(el => { if(el) el.disabled = false; });
            }

            // 6. FORMATEO DE CAMPOS (Evita el error de "PENDIENTE")
            if (inputFecha) {
                inputFecha.value = (p.fecha && !p.fecha.includes('PENDIENTE')) ? p.fecha : '';
            }
            if (inputHora) {
                inputHora.value = (p.hora && p.hora !== '00:00') ? p.hora : '';
            }
            if (selCancha) {
                selCancha.value = p.campo_id || '';
            }

            window.idPartidoSorteo = partidoId;

            // 7. DISPARAR AGENDA (Solo si hay datos suficientes)
            if (selCancha.value && inputFecha.value) {
                // Un pequeño delay asegura que el DOM se asentó antes de la consulta pesada
                setTimeout(() => { 
                    if (typeof window.verificarConflictosInteligentes === 'function') {
                        window.verificarConflictosInteligentes(); 
                    }
                }, 100);
            }

        } catch (e) {
            console.error("Error en asignación rápida:", e);
        }
    };

    window.pintarFixtureVisual = function(partidos) {
        const contenedor = document.getElementById('contenedorFixture');
        if (!contenedor) return;

        // 1. Cabecera y Leyenda (Sin cambios)
        let leyenda = `
            <div class="flex flex-wrap justify-center gap-4 mb-8 text-[10px] uppercase font-black tracking-widest text-slate-500">
                <div class="flex items-center gap-2"><span class="size-2 rounded-full bg-slate-700"></span> Pendiente</div>
                <div class="flex items-center gap-2"><span class="size-2 rounded-full bg-slate-500"></span> Programado</div>
                <div class="flex items-center gap-2"><span class="size-2 rounded-full bg-red-500 animate-pulse"></span> En Vivo</div>
                <div class="flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-500"></span> Finalizado</div>
            </div>
        `;

        contenedor.innerHTML = `
            <h3 class="text-white font-black uppercase text-center mt-8 mb-4 tracking-tighter text-xl italic">⚽ Torneo de Copa</h3>
            ${leyenda}
        `;

        // 2. Agrupamiento de jornadas
        const jornadas = {};
        partidos.forEach(p => {
            const jor = p.jornada || 1;
            if (!jornadas[jor]) jornadas[jor] = [];
            jornadas[jor].push(p);
        });

        // --- 3. LÓGICA DE ORDENAMIENTO (EL TRUCO) ---
        const jerarquiaLiguilla = { 'FINAL': 1, 'SEMIFINAL': 2, 'CUARTOS': 3, 'OCTAVOS': 4 };
        
        const jornadasOrdenadas = Object.keys(jornadas).sort((a, b) => {
            const pesoA = jerarquiaLiguilla[String(a).toUpperCase()] || 99;
            const pesoB = jerarquiaLiguilla[String(b).toUpperCase()] || 99;

            // Si uno es liguilla y el otro no, la liguilla (menor peso) va primero
            if (pesoA !== pesoB) return pesoA - pesoB;

            // Si ambos son semanas numéricas, la mayor va primero (Semana 9 -> Semana 1)
            return parseInt(b) - parseInt(a);
        });

        // 4. Renderizado siguiendo el nuevo orden
        jornadasOrdenadas.forEach(numJor => {
            const esFaseFinal = jerarquiaLiguilla[String(numJor).toUpperCase()];
            const textoTitulo = isNaN(numJor) ? numJor : `Semana ${numJor}`;

            let htmlJornada = `
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl mb-8">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="${esFaseFinal ? 'bg-amber-600' : 'bg-blue-600'} text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg"> 
                            ${textoTitulo} 
                        </span>
                        <div class="h-px bg-slate-800 flex-1"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            `;

            jornadas[numJor].forEach(p => {
                // ... (Toda tu lógica de statusConfig, estaFinalizado, gl, gv se queda EXACTAMENTE IGUAL)
                let statusConfig = {
                    claseBorde: 'border-slate-800/50',
                    claseBg: 'bg-slate-950/50',
                    claseTexto: 'text-slate-400',
                    badge: `<span class="text-[8px] bg-slate-800 text-slate-600 px-2 py-0.5 rounded-md uppercase font-bold">Sin Horario</span>`,
                    indicador: 'text-slate-700'
                };

                const estaFinalizado = p.estatus === 'finalizado' || p.resultado_confirmado === true;
                let claseGanadorLocal = "";
                let claseGanadorVisitante = "";
                const gl = parseInt(p.goles_local || 0);
                const gv = parseInt(p.goles_visitante || 0);
                const mostrarBadgeCampeon = p.jornada === 'FINAL' && p.resultado_confirmado === true && gl !== gv;

                if (estaFinalizado) {
                    statusConfig.claseBorde = p.jornada === 'FINAL' ? 'border-amber-500/50' : 'border-emerald-500/40';
                    statusConfig.claseBg = p.jornada === 'FINAL' ? 'bg-amber-500/5' : 'bg-emerald-500/10';
                    statusConfig.claseTexto = 'text-white';
                    statusConfig.indicador = 'text-emerald-400 font-black';
                    statusConfig.badge = `<span class="text-[8px] bg-emerald-600 text-white px-2 py-0.5 rounded-md uppercase font-black">Finalizado</span>`;
                    if (gl > gv) { claseGanadorLocal = "text-amber-400 font-black scale-110 origin-right"; claseGanadorVisitante = "text-slate-600 font-normal opacity-50"; }
                    else if (gv > gl) { claseGanadorVisitante = "text-amber-400 font-black scale-110 origin-left"; claseGanadorLocal = "text-slate-600 font-normal opacity-50"; }
                } else if (p.estatus === 'en_curso') {
                    statusConfig.claseBorde = 'border-red-500/50'; statusConfig.claseBg = 'bg-red-500/5';
                    statusConfig.claseTexto = 'text-white'; statusConfig.indicador = 'text-red-500 animate-pulse';
                    statusConfig.badge = `<span class="text-[8px] bg-red-600 text-white px-2 py-0.5 rounded-md uppercase font-black animate-pulse">En Vivo</span>`;
                } else if (p.fecha && p.hora) {
                    statusConfig.claseBorde = 'border-slate-600'; statusConfig.claseBg = 'bg-slate-800/40';
                    statusConfig.claseTexto = 'text-white'; statusConfig.indicador = 'text-blue-500';
                    statusConfig.badge = `<span class="text-[8px] bg-slate-700 text-slate-300 px-2 py-0.5 rounded-md uppercase font-bold">${p.hora} HS</span>`;
                }

                htmlJornada += `
                    <button onclick="window.abrirAsignacionRapida('${p.equipo_local}', '${p.equipo_visitante}', '${p.id}')" 
                            class="w-full flex flex-col gap-1 ${statusConfig.claseBg} p-4 rounded-2xl border ${statusConfig.claseBorde} hover:scale-[1.02] transition-all group relative overflow-hidden">
                        ${mostrarBadgeCampeon ? '<div class="absolute -top-1 -right-1 text-[10px] bg-amber-500 text-black px-2 font-black rotate-12 shadow-lg">CAMPEÓN</div>' : ''}
                        <div class="flex items-center justify-between w-full">
                            <div class="flex-1 text-right text-[11px] uppercase truncate ${claseGanadorLocal || statusConfig.claseTexto}">${p.equipo_local}</div>
                            <div class="px-4 ${statusConfig.indicador} font-black text-[10px] italic">${estaFinalizado ? gl + ' - ' + gv : 'VS'}</div>
                            <div class="flex-1 text-left text-[11px] uppercase truncate ${claseGanadorVisitante || statusConfig.claseTexto}">${p.equipo_visitante}</div>
                        </div>
                        <div class="flex justify-center items-center mt-1">${statusConfig.badge}</div>
                    </button>
                `;
            });

            htmlJornada += `</div></div>`;
            contenedor.innerHTML += htmlJornada;
        });
    };

    // ═══════════════════════════════════════════════════════════════════════════════
    // SECCIÓN 8: LIGUILLA/TORNEO (Generación, progreso, archivado)
    // ═══════════════════════════════════════════════════════════════════════════════
    window.recuperarFixtureGuardado = async function() {
        try {
            // USAR CACHÉ GLOBAL SI ESTÁ DISPONIBLE
            let partidosData = window.cachePartidosData;
            if (!partidosData) {
                const res = await fetch('/api/partidos');
                if (!res.ok) throw new Error('Error cargando partidos');
                partidosData = await res.json();
                window.cachePartidosData = partidosData;
            }
            
            const partidos = Object.keys(partidosData).map(id => ({ id, ...partidosData[id] }));

            if (partidos.length > 0) {
                // --- 1. SEPARAR Y ORDENAR GRUPOS ---
                const ordenLiguilla = { 'FINAL': 1, 'SEMIFINAL': 2, 'CUARTOS': 3, 'OCTAVOS': 4 };

                // Grupo A: Liguilla (Ordenada por importancia)
                const liguilla = partidos
                    .filter(p => ordenLiguilla[String(p.jornada).toUpperCase()])
                    .sort((a, b) => ordenLiguilla[String(a.jornada).toUpperCase()] - ordenLiguilla[String(b.jornada).toUpperCase()]);

                // Grupo B: Fase Regular (Ordenada de Semana mayor a menor)
                const regular = partidos
                    .filter(p => !ordenLiguilla[String(p.jornada).toUpperCase()])
                    .sort((a, b) => parseInt(b.jornada) - parseInt(a.jornada));

                // Unimos ambos: Primero Liguilla, luego Regular
                const partidosOrdenadosFinal = [...liguilla, ...regular];

                // 2. Pintamos con el nuevo orden UX
                window.pintarFixtureVisual(partidosOrdenadosFinal);
                
                // --- LÓGICA AUTOMÁTICA (INTACTA) ---
                const tieneLiguilla = liguilla.length > 0;
                if (!tieneLiguilla) {
                    const pendientesRegulares = regular.filter(p => p.estatus !== 'finalizado');
                    if (pendientesRegulares.length === 0) await window.verificarFinFaseRegular();
                } else {
                    await window.verificarProgresoLiguilla();
                }
            }
        } catch (e) { 
            console.error("Error al recuperar el fixture:", e); 
        }
    };

    window.verificarFinFaseRegular = async function() {
        try {
            console.log("Verificando fin de fase...");
            const res = await fetch('/api/partidos');
            const partidos = await res.json();
            const listaPartidos = Object.values(partidos);

            // 1. Filtrar solo fase regular (jornadas numéricas)
            const partidosRegulares = listaPartidos.filter(p => !isNaN(p.jornada));
            
            if (partidosRegulares.length === 0) return;

            // 2. CRUCIAL: Solo avanzar si TODOS tienen resultado_confirmado === true
            const actasPendientes = partidosRegulares.filter(p => p.resultado_confirmado !== true);

            if (actasPendientes.length === 0) {
                // Un pequeño delay para que la tabla en el server se asiente
                setTimeout(async () => {
                    if (confirm("🏆 ¡Fase Regular terminada y actas cerradas! ¿Deseas generar la Liguilla basada en la tabla de posiciones?")) {
                        await window.prepararYGenerarLiguilla(listaPartidos);
                    }
                }, 800);
            } else {
                console.log(`Actas pendientes en fase regular: ${actasPendientes.length}. Esperando cierres...`);
            }
        } catch (error) {
            console.error("Error en verificador:", error);
        }
    };
    
    // --- 3. PROCESADOR DE LIGUILLA ---
    window.isGenerandoLiguilla = false;

    window.prepararYGenerarLiguilla = async function(partidosActuales) {
        if (window.isGenerandoLiguilla) return; // Si ya hay uno en marcha, abortamos
        
        console.log("Calculando inicio de liguilla...");

        const calcularTablaLocal = (partidos) => {
            const stats = {};
            partidos.forEach(p => {
                if (p.resultado_confirmado === true && !isNaN(p.jornada)) {
                    const loc = p.equipo_local, vis = p.equipo_visitante;
                    const gl = parseInt(p.goles_local || 0), gv = parseInt(p.goles_visitante || 0);
                    if (!stats[loc]) stats[loc] = { nombre: loc, pts: 0, dg: 0, gf: 0 };
                    if (!stats[vis]) stats[vis] = { nombre: vis, pts: 0, dg: 0, gf: 0 };
                    stats[loc].gf += gl; stats[vis].gf += gv;
                    stats[loc].dg += (gl - gv); stats[vis].dg += (gv - gl);
                    if (gl > gv) stats[loc].pts += 3; else if (gv > gl) stats[vis].pts += 3; else { stats[loc].pts += 1; stats[vis].pts += 1; }
                }
            });
            return Object.values(stats).sort((a, b) => b.pts - a.pts || b.dg - a.dg || b.gf - a.gf);
        };

        const tabla = calcularTablaLocal(partidosActuales);
        if (tabla.length < 2) return alert("No hay suficientes equipos con actas cerradas.");

        let clasificadosCount = 0;
        let nombreFase = '';

        if (tabla.length >= 16) { clasificadosCount = 16; nombreFase = 'OCTAVOS'; } 
        else if (tabla.length >= 8) { clasificadosCount = 8; nombreFase = 'CUARTOS'; } 
        else if (tabla.length >= 4) { clasificadosCount = 4; nombreFase = 'SEMIFINAL'; } 
        else if (tabla.length >= 2) { clasificadosCount = 2; nombreFase = 'FINAL'; }

        if (clasificadosCount === 0) return alert("No hay suficientes equipos para generar liguilla.");

        const clasificados = tabla.slice(0, clasificadosCount);
        const llaves = [];
        for (let i = 0; i < clasificadosCount / 2; i++) {
            llaves.push({
                equipo_local: clasificados[i].nombre,
                equipo_visitante: clasificados[clasificadosCount - 1 - i].nombre,
                jornada: nombreFase,
                estatus: 'programado'
            });
        }

        await window.enviarLiguilla(llaves);
    };


    // Función auxiliar para enviar los partidos generados
   window.enviarLiguilla = async function(llaves) {
  
    if (window.isGenerandoLiguilla) return;

    try {
        window.isGenerandoLiguilla = true; // ACTIVAMOS EL CANDADO

        const res = await fetch('/api/admin/partidos/generar-liguilla', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ partidos: llaves })
        });

        if(res.ok) {
            alert("🏆 Siguiente fase generada con éxito");
            location.reload(); 
        } else {
            window.isGenerandoLiguilla = false; // Si falló el servidor, liberamos para reintentar
            alert("Error al generar liguilla");
        }
    } catch (err) {
        window.isGenerandoLiguilla = false; // Si hubo error de red, liberamos
        console.error("Error al enviar liguilla:", err);
    }
    };

    
    window.archivarTorneo = async function(campeon, todosLosPartidos) {
        // VALIDACIÓN: No archivar si no hay partidos o si ya está archivado este año
        const idTorneoActual = "torneo_" + new Date().getFullYear();
        
        if (localStorage.getItem('torneo_ya_archivado_' + idTorneoActual)) {
            console.log("⏭️ Este torneo ya fue archivado anteriormente. Saltando...");
            return;
        }
        
        // Verificar que hay partidos reales para archivar
        const partidosReales = Object.values(todosLosPartidos || {}).filter(p => 
            p.equipo_local && p.equipo_visitante
        );
        
        if (partidosReales.length === 0) {
            console.log("⏭️ No hay partidos reales para archivar. Saltando...");
            return;
        }
        
        // Verificar que la mayoría tienen resultado confirmado
        const confirmados = partidosReales.filter(p => p.resultado_confirmado === true);
        if (confirmados.length < partidosReales.length * 0.5) {
            console.log("⏭️ Menos del 50% de partidos confirmados. Saltando...");
            return;
        }
        
        const loader = document.getElementById('overlay-carga');
        if(loader) loader.style.display = 'flex'; 

        // Extraer tabla_final solo si existe el elemento
        let tablaFinal = [];
        const tablaCuerpo = document.querySelector('#tablaCuerpoPosiciones');
        if (tablaCuerpo && tablaCuerpo.querySelectorAll('tr').length > 0) {
            tablaFinal = Array.from(tablaCuerpo.querySelectorAll('tr')).map(tr => {
                const span = tr.querySelector('td:nth-child(2) span');
                return {
                    nombre: span ? span.innerText : 'Unknown',
                    pj: tr.querySelector('td:nth-child(3)')?.innerText || 0,
                    pts: tr.querySelector('td:nth-child(7)')?.innerText || 0,
                    gf: tr.querySelector('td:nth-child(8)')?.innerText || 0
                };
            }).filter(t => t.nombre && t.nombre !== 'Unknown');
        }

        const dataHistorial = {
            nombre_torneo: "Torneo de Copa " + new Date().getFullYear(),
            fecha_finalizacion: new Date().toISOString(),
            campeon: campeon,
            resumen_partidos: todosLosPartidos,
            stats_finales: "Generado automáticamente",
            tabla_final: tablaFinal
        };

        try {
            const res = await fetch('/api/admin/guardar-podio', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify(dataHistorial)
            });

            if (res.ok) {
                if(loader) loader.style.display = 'none';
                // Marcar como archivado para esta sesión y año
                localStorage.setItem('torneo_ya_archivado_' + idTorneoActual, 'true');
                alert("📁 El torneo ha sido archivado en el historial. ¡Felicidades al campeón: " + campeon + "!");
            }
        } catch (err) {
            if(loader) loader.style.display = 'none';
            console.error("Error al archivar:", err);
        }
    };

    window.verificarProgresoLiguilla = async function() {
        // Evitar ejecuciones múltiples simultáneas
        if (verificandoLiguilla) {
            console.log("⏳ Verificación de liguilla ya en progreso, saltando...");
            return;
        }
        verificandoLiguilla = true;
        
        try {
            const res = await fetch('/api/partidos');
            const partidosData = await res.json();
            const lista = Object.values(partidosData);

            const fasesOrder = ['OCTAVOS', 'CUARTOS', 'SEMIFINAL', 'FINAL'];
            let faseActual = "";
            for (let f of fasesOrder) {
                if (lista.some(p => String(p.jornada).toUpperCase() === f)) faseActual = f;
            }

            if (!faseActual) {
                verificandoLiguilla = false;
                return window.verificarFinFaseRegular();
            }
        
        // --- LÓGICA DE CIERRE DE TORNEO (FINAL) ---
        if (faseActual === 'FINAL') {
            const pFinal = lista.find(p => String(p.jornada).toUpperCase() === 'FINAL');
            if (pFinal && pFinal.resultado_confirmado) {
                const gl = parseInt(pFinal.goles_local || 0);
                const gv = parseInt(pFinal.goles_visitante || 0);
                const campeon = gl > gv ? pFinal.equipo_local : pFinal.equipo_visitante;

                const idTorneoActual = "torneo_" + new Date().getFullYear(); 
                if (localStorage.getItem('torneo_finalizado') !== idTorneoActual) {
                    await window.archivarTorneo(campeon, lista);
                    localStorage.setItem('torneo_finalizado', idTorneoActual);
                }
                
                console.log("🏆 Torneo finalizado. Campeón: " + campeon);
            }
            return;
        }

        // 2. Verificar si la fase actual terminó (Para generar la siguiente)
        const partidosDeFase = lista.filter(p => String(p.jornada).toUpperCase() === faseActual);
        const todosTerminados = partidosDeFase.every(p => p.resultado_confirmado === true);

        if (todosTerminados) {
            const siguienteFase = fasesOrder[fasesOrder.indexOf(faseActual) + 1];

            // --- BLOQUE DE SEGURIDAD ANTI-DUPLICADOS ---
            // Si ya existen partidos registrados con el nombre de la siguiente fase, abortamos.
            if (lista.some(p => String(p.jornada).toUpperCase() === siguienteFase)) {
                console.log(`🚀 La fase ${siguienteFase} ya está generada. Deteniendo ejecución doble.`);
                return; 
            }

            // Solo si NO existe la siguiente fase, pedimos confirmación
            if (confirm(`🔥 Fase de ${faseActual} terminada. ¿Generar llaves de ${siguienteFase}?`)) {
                const ganadores = partidosDeFase.map(p => {
                    // Lógica de desempate simple: el que metió más goles o el local por posición (según tu código)
                    return (parseInt(p.goles_local || 0) >= parseInt(p.goles_visitante || 0)) ? p.equipo_local : p.equipo_visitante;
                });

                const nuevasLlaves = [];
                for (let i = 0; i < ganadores.length / 2; i++) {
                    nuevasLlaves.push({
                        equipo_local: ganadores[i * 2],
                        equipo_visitante: ganadores[i * 2 + 1],
                        jornada: siguienteFase,
                        estatus: 'programado',
                        fecha: 'PENDIENTE',
                        hora: '00:00',
                        goles_local: 0,
                        goles_visitante: 0,
                        resultado_confirmado: false
                    });
                }
                
// Llamamos a la función de envío que ya tiene el candado 'isGenerandoLiguilla'
                await window.enviarLiguilla(nuevasLlaves);
            }
        }
        } finally {
            verificandoLiguilla = false;
        }
    };
    
    
    window.verHistorial = async function() {
        try {
            const res = await fetch('/api/historial');
            const historial = await res.json();
            
            const contenedor = document.getElementById('seccionHistorial');
            contenedor.innerHTML = '<h2 class="text-2xl font-bold text-white mb-6">Archivo de Torneos</h2>';

            Object.values(historial).reverse().forEach(torneo => {
                contenedor.innerHTML += `
                    <div class="bg-slate-900 p-6 rounded-3xl border border-slate-800 mb-4 shadow-lg">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-amber-500 font-black uppercase">${torneo.nombre_torneo}</h3>
                                <p class="text-slate-400 text-xs">Finalizado el: ${new Date(torneo.fecha_finalizacion).toLocaleDateString()}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-500 uppercase font-bold block">Campeón</span>
                                <span class="text-white font-black text-xl italic">🏆 ${torneo.campeon}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
        } catch (e) {
            console.error("Error al cargar historial:", e);
        }
    };


    window.cargarHistorialSaloDeLaFama = async function() {
        const contenedor = document.getElementById('contenedor-historial-tab');
        if (!contenedor) return;

        // USAR CACHÉ GLOBAL SI ESTÁ DISPONIBLE
        if (window.cacheHistorialData) {
            renderizarHistorial(window.cacheHistorialData);
            return;
        }

        try {
            const res = await fetch('/api/historial');
            const data = await res.json();
            
            // Guardar en cache
            window.cacheHistorialData = data;
            renderizarHistorial(data);
        } catch (e) {
            console.error("Error al cargar historial:", e);
        }
    };

    function renderizarHistorial(data) {
        const contenedor = document.getElementById('contenedor-historial-tab');
        if (!contenedor) return;
        
        contenedor.innerHTML = '';

        if (!data || Object.keys(data).length === 0) {
            contenedor.innerHTML = '<p class="text-slate-700 uppercase font-black text-[10px]">No hay torneos archivados aún.</p>';
            return;
        }

        // Cambiamos Object.values por Object.entries para obtener la CLAVE (ID) y el VALOR (Torneo)
        Object.entries(data).reverse().forEach(([idTorneo, torneo]) => {
                contenedor.innerHTML += `
                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-[30px] relative overflow-hidden group hover:border-amber-500/50 transition-all shadow-xl">
                        <div class="absolute -top-10 -right-10 size-32 bg-amber-500/5 blur-[40px] group-hover:bg-amber-500/10 transition-all"></div>
                        
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[9px] bg-amber-600 text-white px-3 py-1 rounded-full font-black uppercase tracking-widest">
                                ${new Date(torneo.fecha_finalizacion).getFullYear()}
                            </span>
                            <span class="text-slate-600 text-[9px] font-bold uppercase italic">
                                Final: ${new Date(torneo.fecha_finalizacion).toLocaleDateString()}
                            </span>
                        </div>

                        <h3 class="text-slate-500 font-bold uppercase text-[9px] mb-1 tracking-widest">Campeón</h3>
                        <div class="text-white text-2xl font-black uppercase italic tracking-tighter mb-4">
                            🏆 ${torneo.campeon}
                        </div>

                        <div class="pt-4 border-t border-slate-800 flex justify-between items-center">
                            <span class="text-slate-500 text-[8px] font-black uppercase tracking-widest">
                                ${Object.keys(torneo.resumen_partidos || {}).length} Partidos
                            </span>
                            <button onclick="verDetallesTorneo('${idTorneo}')" class="text-amber-500 text-[8px] font-black uppercase tracking-widest hover:text-white transition">Ver Detalles →</button>
                        </div>
                    </div>
                `;
});
        }
    };

    window.verDetallesTorneo = async function(id) {
        const modal = document.getElementById('modalDetallesHistorial');
        if (!modal) return;
        
        try {
            if (!cacheHistorialCompleto) {
                const res = await fetch('/api/historial');
                cacheHistorialCompleto = await res.json();
            }
            
            const torneo = cacheHistorialCompleto[id];
            if (!torneo) return alert("No se encontraron detalles.");

            // 1. HEADER
            document.getElementById('historial-titulo').innerText = torneo.nombre_torneo || `Torneo ${new Date(torneo.fecha_finalizacion).getFullYear()}`;
            document.getElementById('historial-subtitulo').innerText = `CAMPEÓN: ${torneo.campeon}`;

            // 2. LÓGICA DE TABLA DE POSICIONES
            const tablaBody = document.getElementById('historial-tabla-body');
            tablaBody.innerHTML = '';
            
            let tablaData = torneo.tabla_final || torneo.posiciones || [];

            // SI LA TABLA ESTÁ VACÍA, LA CALCULAMOS CON LOS PARTIDOS DEL HISTORIAL
            if (tablaData.length === 0 && torneo.resumen_partidos) {
                let statsAuto = {};
                Object.values(torneo.resumen_partidos).forEach(p => {
                    const loc = p.local || p.equipo_local;
                    const vis = p.visitante || p.equipo_visitante;
                    const gl = parseInt(p.goles_local || 0);
                    const gv = parseInt(p.goles_visitante || 0);

                    if (!statsAuto[loc]) statsAuto[loc] = { nombre: loc, pj: 0, pts: 0, gf: 0 };
                    if (!statsAuto[vis]) statsAuto[vis] = { nombre: vis, pj: 0, pts: 0, gf: 0 };

                    statsAuto[loc].pj++; statsAuto[vis].pj++;
                    statsAuto[loc].gf += gl; statsAuto[vis].gf += gv;

                    if (gl > gv) statsAuto[loc].pts += 3;
                    else if (gv > gl) statsAuto[vis].pts += 3;
                    else { statsAuto[loc].pts += 1; statsAuto[vis].pts += 1; }
                });
                tablaData = Object.values(statsAuto).sort((a, b) => b.pts - a.pts);
            }

            // DIBUJAR TABLA
            tablaData.forEach(eq => {
                tablaBody.innerHTML += `
                    <tr class="border-b border-slate-800/50 hover:bg-white/5 transition">
                        <td class="px-4 py-3 font-bold text-white text-[11px] uppercase">${eq.nombre || eq.equipo}</td>
                        <td class="px-4 py-3 text-center text-slate-400 font-bold">${eq.pj || 0}</td>
                        <td class="px-4 py-3 text-center text-amber-500 font-black text-xs">${eq.pts || eq.puntos || 0}</td>
                        <td class="px-4 py-3 text-center text-slate-400 font-bold">${eq.gf || eq.goles_favor || 0}</td>
                    </tr>
                `;
            });

            // 3. RESUMEN DE PARTIDOS (Este ya te funcionaba, lo mantenemos igual)
            const partidosContenedor = document.getElementById('historial-partidos');
            partidosContenedor.innerHTML = '';
            const partidos = torneo.resumen_partidos || torneo.partidos || {};
            
            Object.values(partidos).forEach(p => {
                const local = p.local || p.equipo_local;
                const visitante = p.visitante || p.equipo_visitante;
                const gl = p.goles_local ?? 0;
                const gv = p.goles_visitante ?? 0;

                partidosContenedor.innerHTML += `
                    <div class="flex items-center justify-between bg-slate-800/30 p-3 rounded-xl border border-slate-800 mb-2">
                        <span class="text-slate-500 text-[8px] font-black uppercase w-16">${p.jornada || 'Match'}</span>
                        <div class="flex-1 flex justify-center items-center gap-4">
                            <span class="text-white font-bold text-[10px] uppercase w-24 text-right">${local}</span>
                            <span class="bg-slate-900 px-3 py-1 rounded-lg text-amber-500 font-black italic text-xs min-w-[50px] text-center">${gl} - ${gv}</span>
                            <span class="text-white font-bold text-[10px] uppercase w-24 text-left">${visitante}</span>
                        </div>
                    </div>
                `;
            });

            // 4. ESTADÍSTICAS FINALES (TOP GOLEADORES CON NOMBRES)
            const statsFinales = document.getElementById('historial-stats');
            statsFinales.innerHTML = '';

            let goleadoresTorneo = {};
            
            // Obtenemos los jugadores actuales para cruzar el nombre (si los tienes en cache)
            // Si no los tienes, intentaremos sacarlos del DOM de la tabla de jugadores
            const listaJugadoresActuales = Array.from(document.querySelectorAll('#content-jugadores tbody tr'));

            Object.values(partidos).forEach(partido => {
                const detalle = partido.detalle_jugadores || {};
                Object.entries(detalle).forEach(([tel, info]) => {
                    if (info.goles > 0) {
                        // BUSCAMOS EL NOMBRE:
                        // 1. Intentar sacarlo del 'info' si se grabó al archivar
                        // 2. Si no, buscarlo en la tabla de jugadores actual por el teléfono
                        let nombreReal = info.nombre; 
                        
                        if (!nombreReal) {
                            const filaJugador = listaJugadoresActuales.find(tr => 
                                tr.querySelector('[data-field="telefono"]')?.innerText.trim() === tel
                            );
                            nombreReal = filaJugador ? filaJugador.querySelector('[data-field="nombre"]')?.innerText.split('\n')[0] : `Jugador (${tel})`;
                        }

                        if (!goleadoresTorneo[tel]) {
                            goleadoresTorneo[tel] = { nombre: nombreReal, goles: 0 };
                        }
                        goleadoresTorneo[tel].goles += info.goles;
                    }
                });
            });

            const rankingGoleadores = Object.values(goleadoresTorneo)
                .sort((a, b) => b.goles - a.goles)
                .slice(0, 6); // Top 6

            if (rankingGoleadores.length > 0) {
                let htmlGoleadores = `
                    <div class="col-span-full">
                        <h4 class="text-amber-500 text-[8px] font-black uppercase tracking-[0.2em] mb-3 italic">Máximos Goleadores de la Temporada</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                `;

                rankingGoleadores.forEach((jugador, i) => {
                    const esPrimero = i === 0;
                    htmlGoleadores += `
                        <div class="flex justify-between items-center ${esPrimero ? 'bg-amber-500/10 border-amber-500/30 shadow-lg shadow-amber-900/20' : 'bg-slate-800/40 border-slate-700/50'} p-3 rounded-xl border transition-all">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : '⚽'}</span>
                                <div class="flex flex-col">
                                    <span class="text-white font-black text-[11px] uppercase italic tracking-tighter">${jugador.nombre}</span>
                                    <span class="text-[8px] text-slate-500 font-bold uppercase">${i + 1}° Posición</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="${esPrimero ? 'text-amber-500' : 'text-white'} font-black text-sm italic">${jugador.goles}</span>
                                <span class="text-[7px] text-slate-500 font-black uppercase block">Goles</span>
                            </div>
                        </div>
                    `;
                });

                htmlGoleadores += `</div></div>`;
                statsFinales.innerHTML = htmlGoleadores;
            } else {
                statsFinales.innerHTML = `<p class="text-slate-600 text-[10px] italic">No hay datos de goleadores para este torneo.</p>`;
            }

            modal.classList.remove('hidden');

        } catch (e) { console.error("Error modal historial:", e); }
    };

window.cerrarModalHistorial = function() {
        document.getElementById('modalDetallesHistorial').classList.add('hidden');
    };

// Logout function - use GET /logout
window.logout = function() {
    const btn = document.getElementById('logout-btn');
    const icon = document.getElementById('logout-icon');
    
    // Show loading
    btn.disabled = true;
    icon.innerHTML = '<span class="animate-spin">⟳</span> Cerrando...';
    
    // Clear localStorage
    localStorage.removeItem('admin_token');
    
    // Fade out animation
    document.body.style.transition = 'opacity 0.3s ease-out';
    document.body.style.opacity = '0';
    
    setTimeout(() => {
        // Use window.location.href to force GET request (clears POST state)
        window.location.href = '/logout';
    }, 300);
};

    window.verJugadoresEquipo = async function(equipoId, nombreEquipo) {
        const modal = document.getElementById('modalVerJugadores');
        const contenedor = document.getElementById('contenedorVerJugadores');
        const titulo = document.getElementById('tituloVerJugadores');
        const subtitulo = document.getElementById('subtituloVerJugadores');
        
        if(!modal || !contenedor) return;
        
        titulo.innerText = 'Jugadores del Equipo';
        subtitulo.innerHTML = `${nombreEquipo} <span class="text-slate-400 text-sm ml-1">Cargando...</span>`;
        contenedor.innerHTML = '<p class="text-slate-500 text-center p-4">Cargando...</p>';
        
        // Hide add panel when reopening
        const panel = document.getElementById('panelAgregarJugador');
        if(panel) panel.classList.add('hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        try {
            // Add timestamp to prevent caching
            const res = await fetch('/api/jugadores?_=' + Date.now());
            const jugadores = await res.json();
            
            const jugadoresEquipo = Object.entries(jugadores)
                .filter(([_, j]) => j.equipo === nombreEquipo)
                .sort((a, b) => (a[1].numero || 99) - (b[1].numero || 99));
            
            const totalJugadores = jugadoresEquipo.length;
            const minJugadores = 11;
            const countLabel = totalJugadores >= minJugadores 
                ? `<span class="text-emerald-400">(${totalJugadores}/${minJugadores}+)</span>`
                : `<span class="text-amber-400">(${totalJugadores}/${minJugadores})</span>`;
            
            // Update subtitle with count
            if (subtitulo) {
                subtitulo.innerHTML = `${nombreEquipo} <span class="text-slate-400 text-sm ml-1">${countLabel}</span>`;
            }
            
            if (jugadoresEquipo.length === 0) {
                contenedor.innerHTML = `
                    <div class="text-center p-6">
                        <p class="text-slate-500 mb-4">No hay jugadores en este equipo.</p>
                        <button onclick="cerrarModalVerJugadores(); abrirModalNuevoJugador()" class="text-emerald-500 hover:underline text-sm mr-3">+ Agregar Jugador</button>
                        <button onclick="cerrarModalVerJugadores()" class="text-blue-500 hover:underline text-sm">Cerrar</button>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="grid grid-cols-1 gap-2">';
            
            jugadoresEquipo.forEach(([telefono, j]) => {
                // Usar 'estatus' (con 's') que es lo que guarda Laravel
                const est = (j.estatus || j.estado || 'activo').toString().toLowerCase();
                const activo = est === 'activo';
                const lesionado = est === 'lesionado';
                const suspendido = est === 'suspendido';
                const estadoLabel = activo ? '🟢 Activo' : lesionado ? '🟡 Lesionado' : suspendido ? '🔴 Suspendido' : '🔴 Inactivo';
                const estadoClase = activo ? 'text-emerald-400' : lesionado ? 'text-yellow-400' : 'text-red-400';
                const estadoBg = activo ? 'bg-emerald-600/20' : lesionado ? 'bg-yellow-600/20' : 'bg-red-600/20';
                
                const nombreSafe = (j.nombre || 'Sin nombre').toLowerCase();
                const dirSafe = (j.direccion || '').replace(/'/g, "\\'");
                const estadoSafe = (j.estatus || j.estado || 'Activo').toString();
                
                html += `
                    <div class="jugador-card bg-slate-800 border border-slate-700 rounded-lg p-3 flex items-center justify-between hover:border-blue-500/50 cursor-pointer transition" data-nombre="${nombreSafe}" data-dorsal="${j.numero || ''}" data-estado="${est}" onclick="cerrarModalVerJugadores(); setTimeout(() => editarJugador('${telefono}', '${j.nombre || ''}', '${j.equipo || ''}', ${j.edad || 18}, '${dirSafe}', ${j.numero || 0}, ${j.partidos_jugados || 0}, '${estadoSafe}', ${j.partidos_suspension || 0}), 100)">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 ${estadoBg} rounded-full flex items-center justify-center font-bold ${estadoClase} text-sm">${j.numero || '-'}</div>
                            <div>
                                <p class="font-bold text-white text-sm">${j.nombre}</p>
                                <p class="text-[10px] ${estadoClase}">${estadoLabel}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-emerald-400">${j.goles || 0} goles</p>
                            <p class="text-[10px] text-slate-500">${j.partidos_jugados || 0} PJ</p>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            html += `
                <div class="mt-4 pt-4 border-t border-slate-700 text-center">
                    <p class="text-[10px] text-slate-500">Toca un jugador para editarlo</p>
                </div>
            `;
            
            contenedor.innerHTML = html;
        } catch (e) {
            console.error(e);
            contenedor.innerHTML = '<p class="text-red-500 text-center p-4">Error al cargar jugadores</p>';
        }
    };

window.cerrarModalVerJugadores = function() {
        const modal = document.getElementById('modalVerJugadores');
        if(modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    };

    window.togglePanelAgregarJugador = async function() {
        const panel = document.getElementById('panelAgregarJugador');
        const contenedor = document.getElementById('contenedorJugadoresDisponibles');
        const equipoActual = document.getElementById('subtituloVerJugadores')?.innerText;
        
        if (!panel || !contenedor || !equipoActual) return;
        
        if (panel.classList.contains('hidden')) {
            // Show panel and load available players
            panel.classList.remove('hidden');
            contenedor.innerHTML = '<p class="text-slate-500 text-xs text-center py-2">Cargando...</p>';
            
            try {
                const [resJug, resEq] = await Promise.all([
                    fetch('/api/jugadores?_=' + Date.now()),
                    fetch('/api/equipos')
                ]);
                const jugadores = await resJug.json();
                const equipos = await resEq.json();
                
                // Count players per team
                const jugadoresPorEquipo = {};
                Object.values(jugadores).forEach(j => {
                    const eq = j.equipo || 'Libre';
                    jugadoresPorEquipo[eq] = (jugadoresPorEquipo[eq] || 0) + 1;
                });
                
                // Find available players (Libre or team has < 11)
                const disponibles = Object.entries(jugadores).filter(([tel, j]) => {
                    if (j.equipo === equipoActual) return false; // Already on this team
                    if (!j.equipo || j.equipo === 'Libre') return true; // Free agents
                    
                    // Check if team has room (less than 11)
                    const count = jugadoresPorEquipo[j.equipo] || 0;
                    return count < 11;
                });
                
                if (disponibles.length === 0) {
                    contenedor.innerHTML = '<p class="text-slate-500 text-xs text-center py-2">No hay jugadores disponibles</p>';
                    return;
                }
                
                let html = '';
                disponibles.forEach(([tel, j]) => {
                    const estado = (j.estatus || j.estado || 'activo').toString().toLowerCase();
                    const estadoLabel = estado === 'activo' ? '🟢' : estado === 'lesionado' ? '🟡' : '🔴';
                    html += `
                        <div class="flex items-center justify-between bg-slate-800/50 rounded px-2 py-1.5 hover:bg-slate-700/50">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-emerald-400 w-6">${j.numero || '-'}</span>
                                <span class="text-white text-xs">${j.nombre}</span>
                                <span class="text-[10px] text-slate-500">${j.equipo || 'Libre'} ${estadoLabel}</span>
                            </div>
                            <button onclick="asignarJugadorAEquipo('${tel}', '${j.nombre}', '${equipoActual}')" class="text-emerald-400 hover:text-emerald-300 text-xs font-bold">
                                ➕
                            </button>
                        </div>
                    `;
                });
                
                contenedor.innerHTML = html;
            } catch (e) {
                console.error(e);
                contenedor.innerHTML = '<p class="text-red-500 text-xs text-center py-2">Error al cargar</p>';
            }
        } else {
            panel.classList.add('hidden');
        }
    };

    window.asignarJugadorAEquipo = async function(telefono, nombre, equipo) {
        if (!confirm(`¿Asignar a ${nombre} al equipo ${equipo}?`)) return;
        
        try {
            const res = await fetch('/api/admin/jugadores/actualizar/' + telefono, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ equipo: equipo })
            });
            
            const data = await res.json();
            
            if (res.ok) {
                alert(`✅ ${nombre} asignado a ${equipo}`);
                window.verJugadoresEquipo(null, equipo);
                // Also refresh available players panel
                window.togglePanelAgregarJugador();
            } else {
                alert('❌ ' + (data.error || 'Error al asignar'));
            }
        } catch (e) {
            console.error(e);
            alert('❌ Error al asignar');
        }
    };

</script>
</body>
</html>