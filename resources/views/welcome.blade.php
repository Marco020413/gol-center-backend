@extends('layouts.app')

@section('content')
<main class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-2">
        <div class="flex border-b border-slate-800 mb-6 gap-8 overflow-x-auto">
            <button onclick="changeTab('jugadores')" class="tab-btn pb-4 text-blue-500 border-b-2 border-blue-500 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Jugadores</button>
            <button onclick="changeTab('equipos_gest')" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Gestionar Equipos</button>
            <button onclick="changeTab('partidos')" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Partidos</button>
            <button onclick="changeTab('posiciones')" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Posiciones</button>
            <button onclick="changeTab('general')" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">General</button>
        </div>

        <div id="tab-content">
            <div id="content-jugadores" class="tab-pane">
                <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" id="busquedaJugador" onkeyup="filtrarTabla()" placeholder="Buscar por nombre o teléfono..." class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-10 pr-4 py-2 text-sm text-white outline-none focus:border-blue-500 transition">
                    </div>
                    
                    <select id="filtroEquipo" onchange="filtrarTabla()" class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white outline-none focus:border-blue-500">
                        <option value="">Todos los equipos</option>
                        <option value="Libre">Agentes Libres (Sin Equipo)</option>
                    </select>

                    <select id="ordenarPor" onchange="filtrarTabla()" class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white outline-none focus:border-blue-500">
                        <option value="nombre">Ordenar por Nombre</option>
                        <option value="goles">Más Goleadores</option>
                        <option value="pj">Más Partidos</option>
                        <option value="dorsal">Por Dorsal (#)</option>
                    </select>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
                    <table id="tablaPrincipalJugadores" class="w-full text-left">
                        <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase text-center">
                            <tr>
                                <th class="px-6 py-4 text-left">Jugador</th>
                                <th class="px-6 py-4">Equipo</th>
                                <th class="px-6 py-4 text-center">PJ</th>
                                <th class="px-6 py-4 text-center">Goles</th>
                                <th class="px-6 py-4 text-center">Acciones</th> 
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-sm">
                            @forelse($jugadores as $telefono => $j)
                            <tr class="hover:bg-blue-900/5 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 bg-blue-600/20 text-blue-500 rounded-full flex items-center justify-center font-bold text-xs border border-blue-500/30">
                                            {{ $j['numero'] ?? '00' }}
                                        </div>
                                        <div class="text-left">
                                            <div class="font-medium text-white" data-field="nombre">{{ $j['nombre'] ?? 'Sin Nombre' }}</div>
                                            <div class="text-[10px] text-slate-500" data-field="telefono">{{ $telefono }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center" data-field="equipo" data-valor="{{ $j['equipo'] ?? 'Libre' }}">
                                    @if(($j['equipo'] ?? 'Libre') === 'Libre')
                                        <span class="bg-amber-500/10 text-amber-500 border border-amber-500/20 px-3 py-1 rounded-full text-[10px] font-black uppercase animate-pulse">
                                            ⚠️ Sin Equipo
                                        </span>
                                    @else
                                        <span class="text-blue-400 font-semibold uppercase tracking-wider text-xs">
                                            {{ $j['equipo'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center {{ ($j['partidos_jugados'] ?? 0) >= 5 ? 'text-red-500 font-bold' : 'text-slate-400' }}">
                                    {{ $j['partidos_jugados'] ?? 0 }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-white">{{ $j['goles'] ?? 0 }}</td>
                                <td class="px-6 py-4 text-center flex justify-center gap-2">
                                    <button onclick="editarJugador('{{ $telefono }}', '{{ $j['nombre'] ?? '' }}', '{{ $j['equipo'] ?? '' }}', '{{ $j['edad'] ?? 0 }}', '{{ $j['direccion'] ?? '' }}', '{{ $j['numero'] ?? 0 }}')" class="text-blue-500 hover:text-blue-400 p-1 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button onclick="eliminarJugador('{{ $telefono }}')" class="text-red-500 hover:text-red-400 p-1 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="p-12 text-center text-slate-500 italic">No hay jugadores registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="content-equipos_gest" class="tab-pane hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="listaEquiposCards">
                    <p class="text-slate-500 italic p-10 text-center col-span-2">Cargando equipos registrados...</p>
                </div>
            </div>

            <div id="content-partidos" class="tab-pane hidden space-y-4">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-white uppercase tracking-tighter">Partidos de la Jornada</h2>
                    <button onclick="abrirModalCrearPartido()" class="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-blue-500 shadow-lg shadow-blue-900/20">
                        + PROGRAMAR PARTIDO
                    </button>
                </div>
                <div id="contenedorListaPartidos"></div>
            </div>

            <div id="content-posiciones" class="tab-pane hidden p-10 text-center border-2 border-dashed border-slate-800 rounded-xl text-slate-600">Posiciones...</div>
            <div id="content-general" class="tab-pane hidden p-10 text-center border-2 border-dashed border-slate-800 rounded-xl text-slate-600">General...</div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-blue-600 p-6 rounded-xl shadow-lg text-white">
            <h4 class="font-bold text-lg mb-4 text-center uppercase tracking-tighter text-white">Panel de Gestión</h4>
            <div class="space-y-3">
                <button onclick="abrirModal()" class="w-full bg-white text-blue-600 font-bold py-3 rounded-lg text-sm hover:shadow-xl transition">Registrar Jugador</button>
                <button onclick="abrirModalEquipo()" class="w-full bg-blue-700 text-white font-bold py-3 rounded-lg text-sm border border-blue-400/30 hover:bg-blue-800 transition">Crear Equipo</button>
            </div>
        </div>
    </div>
</main>

<div id="modalJugador" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[100] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-blue-600/10">
            <h3 id="tituloModalJugador" class="text-xl font-bold text-white uppercase tracking-tighter">Nuevo Jugador</h3>
            <button onclick="cerrarModal()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form id="formRegistroJugador" class="p-6 space-y-4 text-sm">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Nombre</label>
                    <input type="text" name="nombre" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Dorsal (#)</label>
                    <input type="number" name="numero" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Edad</label>
                    <input type="number" name="edad" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Teléfono</label>
                    <input type="number" name="telefono" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Dirección</label>
                <input type="text" name="direccion" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Equipo</label>
                <select id="selectEquipos" name="equipo" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                    <option value="">Selecciona un equipo</option>
                </select>
            </div>
            <div id="mensajeError" class="hidden text-red-500 text-[10px] bg-red-500/10 p-2 rounded border border-red-500/20 text-center uppercase font-bold"></div>
            <button type="submit" id="btnGuardar" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl shadow-lg transition">GUARDAR JUGADOR</button>
        </form>
    </div>
</div>

<div id="modalEquipo" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[110] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-blue-600/10">
            <h3 id="tituloModalEquipo" class="text-xl font-bold text-white uppercase tracking-tighter">Gestión de Equipo</h3>
            <button onclick="cerrarModalEquipo()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form id="formRegistroEquipo" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 text-sm">
            @csrf
            <input type="hidden" name="equipo_id_edit" id="equipo_id_edit">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Nombre</label>
                <input type="text" name="nombre" id="nombreEquipoInput" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-2">Escudo</label>
                <div id="previewContenedor" class="hidden mb-4 p-4 bg-blue-600/10 border border-blue-500/30 rounded-xl flex items-center gap-4">
                    <img id="imgPreview" src="" class="size-16 object-contain rounded-lg bg-white/10">
                    <p id="namePreview" class="text-white font-bold text-sm truncate"></p>
                </div>
                <div id="contenedorEscudos" class="grid grid-cols-4 gap-3 bg-slate-950/50 p-3 rounded-xl border border-slate-800 text-center text-white font-bold max-h-40 overflow-y-auto"></div>
                <div class="mt-3">
                    <label class="cursor-pointer flex items-center justify-center border-2 border-dashed border-slate-700 rounded-lg hover:border-blue-500 transition py-2">
                        <span class="text-[10px] text-blue-500 font-bold" id="fileName">+ SUBIR NUEVO ESCUDO</span>
                        <input type="file" name="escudo_file" id="inputEscudo" class="hidden" accept="image/*" onchange="updateFileName(this)">
                    </label>
                </div>
            </div>
            <button type="submit" id="btnGuardarEquipo" class="w-full bg-blue-600 font-bold py-3 rounded-xl transition uppercase text-xs">Guardar Equipo</button>
        </form>
    </div>
</div>

<div id="modalCrearPartido" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[120] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-blue-600/10">
            <h3 class="text-xl font-bold text-white uppercase tracking-tighter">Programar Partido</h3>
            <button onclick="cerrarModalCrearPartido()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form id="formCrearPartido" class="p-6 space-y-4 text-sm">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Local</label>
                    <select name="local" id="selectLocal" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500"></select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Visitante</label>
                    <select name="visitante" id="selectVisitante" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500"></select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Fecha</label>
                    <input type="date" name="fecha" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Hora</label>
                    <input type="time" name="hora" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl shadow-lg transition uppercase text-xs">Crear Encuentro ⚽</button>
        </form>
    </div>
</div>

<div id="modalActualizarMarcador" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[130] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 bg-blue-600/10 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white uppercase tracking-tighter">Gestionar Partido</h3>
            <button onclick="cerrarModalMarcador()" class="text-slate-500 hover:text-white">&times;</button>
        </div>
        <form id="formActualizarMarcador" class="p-6 space-y-6">
            <input type="hidden" id="edit_partido_id">
            
            <div class="text-center">
                <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Estado del Sistema</span>
                <span id="display_estatus" class="text-blue-500 font-black uppercase text-xs tracking-widest bg-blue-500/10 px-3 py-1 rounded-full border border-blue-500/20">Cargando...</span>
            </div>

            <div class="flex items-center justify-around gap-4">
                <div class="text-center">
                    <label id="edit_labelLocal" class="block text-[10px] font-bold text-slate-500 uppercase mb-2">LOCAL</label>
                    <input type="number" id="goles_local" required class="size-16 bg-slate-800 border border-slate-700 rounded-xl text-center text-2xl font-black text-white outline-none focus:border-blue-500">
                </div>
                <span class="text-slate-700 font-black text-2xl mt-6">VS</span>
                <div class="text-center">
                    <label id="edit_labelVisitante" class="block text-[10px] font-bold text-slate-500 uppercase mb-2">VISITANTE</label>
                    <input type="number" id="goles_visitante" required class="size-16 bg-slate-800 border border-slate-700 rounded-xl text-center text-2xl font-black text-white outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="bg-blue-900/20 p-3 rounded-lg border border-blue-500/30">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="confirmar_final" class="size-4 accent-blue-600">
                    <span class="text-[10px] text-blue-300 font-bold uppercase leading-tight">
                        Confirmar resultado final y cerrar acta (Bloquea edición)
                    </span>
                </label>
            </div>

            <div class="space-y-3 pt-4 border-t border-slate-800">
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl text-xs hover:bg-blue-500 transition shadow-lg uppercase tracking-widest">Guardar Cambios</button>
                <button type="button" onclick="eliminarPartido()" class="w-full text-[10px] text-red-500 font-bold py-2 hover:bg-red-500/10 rounded-lg transition uppercase tracking-widest">
                    🗑️ Eliminar Partido
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateFileName(input) {
        const label = document.getElementById('fileName');
        if (input.files.length > 0) {
            const file = input.files[0];
            label.innerText = "LISTO ✅";
            const urlTemp = URL.createObjectURL(file);
            mostrarPreview(urlTemp, file.name);
            document.querySelectorAll('input[name="escudo_url"]').forEach(r => r.checked = false);
        }
    }

    function mostrarPreview(url, nombre) {
        const contenedor = document.getElementById('previewContenedor');
        const img = document.getElementById('imgPreview');
        const txt = document.getElementById('namePreview');
        if(contenedor) {
            contenedor.classList.remove('hidden');
            contenedor.classList.add('flex');
            img.src = url;
            txt.innerText = nombre;
        }
    }
</script>
@endsection