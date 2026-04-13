@extends('layouts.app')

@section('content')
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-4 lg:py-10 grid grid-cols-1 lg:grid-cols-3 gap-6">    
    <div class="lg:col-span-2">
        <div class="flex border-b border-slate-800 mb-6 gap-4 overflow-x-auto pb-1 scrollbar-hide whitespace-nowrap">
            <button onclick="changeTab('jugadores')" id="btn-tab-jugadores" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Jugadores</button>
            <button onclick="changeTab('posiciones')" id="btn-tab-posiciones" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Posiciones</button>
            <button onclick="changeTab('equipos_gest')" id="btn-tab-equipos_gest" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Gestionar Equipos</button>
            <button onclick="changeTab('partidos')" id="btn-tab-partidos" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Partidos</button>
            <button onclick="changeTab('campos')" id="btn-tab-campos" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Canchas</button>
            <button onclick="changeTab('roles')" id="btn-tab-roles" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Roles</button>
            <button onclick="changeTab('historial')" id="btn-tab-historial" class="tab-btn pb-4 text-slate-500 hover:text-slate-300 font-bold text-sm uppercase tracking-wider whitespace-nowrap">Salón de la Fama</button>
        </div>
    <div id="podio-final" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"></div>

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
                        <option value="">Todos los jugadores</option>
                        <option value="Libre">Agentes Libres (Sin Equipo)</option>
                        <option value="SUSPENDIDO">Jugadores Sancionados 🚫</option>
                        <option value="LESIONADO">Jugadores Lesionados 🚑</option>
                    </select>
                    
                    <select id="ordenarPor" onchange="filtrarTabla()" class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white outline-none focus:border-blue-500">
                        <option value="goles">Más Goleadores</option>
                        <option value="nombre">Ordenar por Nombre</option>
                        <option value="pj">Más Partidos</option>
                        <option value="dorsal">Por Dorsal (#)</option>
                    </select>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl overflow-x-auto">
                    <table id="tablaPrincipalJugadores" class="w-full text-left min-w-[600px]">
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
                            @if(!isset($j['nombre'])) @continue @endif
                            <tr class="hover:bg-blue-900/5 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 flex-shrink-0 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-[15px] font-black text-blue-400">
                                            {{ $j['numero'] ?? '0' }}
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-white font-bold" data-field="nombre">{{ $j['nombre'] }}</span>
                                            @if(($j['estatus'] ?? 'activo') === 'lesionado')
                                                <span class="bg-blue-500 text-[7px] px-1.5 py-0.5 rounded text-white font-black">LESIONADO 🚑</span>
                                            @endif
                                        </div>
                                        
                                        @if(($j['estatus'] ?? '') === 'suspendido')
                                            @php $resto = (int)($j['partidos_suspension'] ?? 0); @endphp
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="bg-red-600 text-[7px] px-2 py-0.5 rounded text-white font-black animate-pulse">SUSPENDIDO 🚫</span>
                                                <span class="text-[9px] font-black uppercase text-amber-500 tracking-tighter">
                                                    {{ $resto > 0 ? "Restan: $resto partidos" : "Sanción Manual" }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-slate-500" data-field="telefono">{{ $telefono }}</div>
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
                                    <button onclick="editarJugador('{{ $telefono }}', '{{ $j['nombre'] ?? '' }}', '{{ $j['equipo'] ?? '' }}', '{{ $j['edad'] ?? 0 }}', '{{ $j['direccion'] ?? '' }}', '{{ $j['numero'] ?? 0 }}', '{{ $j['partidos_jugados'] ?? 0 }}', '{{ $j['estatus'] ?? 'activo' }}', '{{ $j['partidos_suspension'] ?? 0 }}')" class="text-blue-500 hover:text-blue-400 p-1 transition">
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="relative">
                        <input type="text" id="filtroEquipoPartido" onkeyup="aplicarFiltrosPartidos()" placeholder="Filtrar por equipo..." class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-xs text-white outline-none focus:border-blue-500">
                    </div>
                    
                    <select id="filtroEstatusPartido" onchange="aplicarFiltrosPartidos()" class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-xs text-white outline-none focus:border-blue-500">
                        <option value="todos">Todos</option>
                        <option value="programado">Próximos Partidos</option>
                        <option value="en_curso">En Vivo 🟢</option>
                        <option value="finalizado">Por Subir Acta ⚠️</option>
                        <option value="confirmado">Actas Cerradas 🔒</option>
                    </select>

                    <select id="ordenarPartidos" onchange="aplicarFiltrosPartidos()" class="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-xs text-white outline-none focus:border-blue-500">
                        <option value="recientes">Más Recientes primero</option>
                        <option value="antiguos">Más Antiguos primero</option>
                    </select>
                </div>

                <div id="contenedorListaPartidos"></div>
            </div>

            <div id="content-posiciones" class="tab-pane hidden">
                <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
                    <div class="flex justify-end p-4 border-b border-slate-800">
                        <button onclick="window.descargarBackupPDF()" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            📥 Backup PDF
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-950/50 text-[10px] text-slate-500 uppercase tracking-widest border-b border-slate-800">
                                    <th class="px-4 py-4 text-center w-12">#</th>
                                    <th class="px-4 py-4">Club</th>
                                    <th class="px-2 py-4 text-center">PJ</th>
                                    <th class="px-2 py-4 text-center hidden md:table-cell">G</th>
                                    <th class="px-2 py-4 text-center hidden md:table-cell">E</th>
                                    <th class="px-2 py-4 text-center hidden md:table-cell">P</th>
                                    <th class="px-3 py-4 text-center font-black text-white">Pts</th>
                                    <th class="px-2 py-4 text-center">GF</th>
                                    <th class="px-2 py-4 text-center">GC</th>
                                    <th class="px-2 py-4 text-center">DG</th>
                                </tr>
                            </thead>
                            <tbody id="tablaCuerpoPosiciones" class="divide-y divide-slate-800/50">
                                @if(isset($tablaPosiciones) && count($tablaPosiciones) > 0)
                                    @foreach($tablaPosiciones as $index => $team)
                                    <tr class="hover:bg-blue-500/5 transition-colors border-b border-slate-800/50">
                                        <td class="px-4 py-4 text-center text-slate-500 font-bold text-xs">{{ $index + 1 }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ $team['escudo'] ?? 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png' }}" class="size-6 object-contain">
                                                <span class="text-white font-bold text-xs uppercase tracking-tight">{{ $team['nombre'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-4 text-center text-slate-300 text-xs">{{ $team['pj'] }}</td>
                                        <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">{{ $team['g'] }}</td>
                                        <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">{{ $team['e'] }}</td>
                                        <td class="px-2 py-4 text-center text-slate-300 text-xs hidden md:table-cell">{{ $team['p'] }}</td>
                                        <td class="px-3 py-4 text-center font-black text-emerald-400 text-sm">{{ $team['pts'] }}</td>
                                        <td class="px-2 py-4 text-center text-slate-300 text-xs">{{ $team['gf'] }}</td>
                                        <td class="px-2 py-4 text-center text-slate-300 text-xs">{{ $team['gc'] }}</td>
                                        <td class="px-2 py-4 text-center font-bold text-xs {{ ($team['gf'] - $team['gc']) >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ ($team['gf'] - $team['gc']) > 0 ? '+' . ($team['gf'] - $team['gc']) : ($team['gf'] - $team['gc']) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="content-roles" class="tab-pane hidden space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center space-y-4 shadow-2xl">
                    <div class="size-16 bg-blue-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="size-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-white uppercase tracking-tighter">Generador de Rol de Juegos</h2>
                    <p class="text-slate-400 text-xs max-w-sm mx-auto leading-relaxed">
                        Esta herramienta creará automáticamente los enfrentamientos entre todos los equipos registrados usando el sistema <b>"Todos contra Todos"</b>.
                    </p>
                    
                    <div class="pt-4">
                        <button onclick="window.generarTorneoAleatorio()" class="bg-blue-600 hover:bg-blue-500 text-white font-black py-4 px-8 rounded-2xl text-xs uppercase tracking-widest transition-all shadow-lg shadow-blue-900/20 active:scale-95">
                            🎲 Sortear y Generar Jornadas
                        </button>

                        <button onclick="window.limpiarTodo()" 
                                class="bg-slate-800 hover:bg-red-600 text-slate-400 hover:text-white font-black py-4 px-8 rounded-2xl text-xs uppercase tracking-widest transition-all active:scale-95">
                            🗑️ Limpiar Torneo
                        </button>
                    </div>
                </div>

                <div id="contenedorFixture" class="space-y-8 pb-10">
                    </div>
            </div>

            <div id="content-campos" class="tab-pane hidden space-y-4">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-white uppercase tracking-tighter">Gestión de Canchas</h2>
                    <button onclick="abrirModalCampo()" class="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-blue-500 shadow-lg">
                        + NUEVA CANCHA
                    </button>
                </div>
                <div id="listaCamposCards" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    </div>
            </div>

            <div id="content-historial" class="tab-pane hidden animate-fade-in">
                <div class="mb-6">
                    <h2 class="text-2xl font-black text-white uppercase tracking-tighter italic">Salón de la Fama</h2>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Historial de Campeones y Torneos Archivados</p>
                </div>
                
                <div id="contenedor-historial-tab" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <p class="text-slate-600 animate-pulse uppercase text-[10px] font-black">Consultando archivos de la liga...</p>
                </div>
            </div>
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
                    <input type="text" name="nombre" oninput="if(this.value.length > 55) this.value = this.value.slice(0, 55);" placeholder="Máx 55 Caracteres"  required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Dorsal (#)</label>
                    <input type="number" name="numero" min="1" max="99" oninput="if(this.value > 99) this.value = 99;" placeholder="Máx 99" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Edad</label>
                    <input type="number" name="edad" min="1" max="99" oninput="if(this.value > 99) this.value = 99;" placeholder="Máx 99" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Teléfono</label>
                    <input type="number" name="telefono" oninput="if(this.value.length > 10) this.value = this.value.slice(0, 10);" placeholder="10 dígitos" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Dirección</label>
                <input type="text" name="direccion" oninput="if(this.value.length > 255) this.value = this.value.slice(0, 255);" placeholder="Máx 255 Caracteres" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Equipo</label>
                <select id="selectEquipos" name="equipo" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                    <option value="">Selecciona un equipo</option>
                </select>
            </div>
           <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Estado del Jugador</label>
                    <select name="estatus" id="edit_estatus" onchange="window.toggleCamposSuspension()" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500 transition">
                        <option value="activo">ACTIVO ✅</option>
                        <option value="suspendido">SUSPENDIDO 🚫</option>
                        <option value="lesionado">LESIONADO 🚑</option>
                    </select>
                </div>

                <div id="contenedorPartidosSuspension" class="hidden">
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Partidos de Castigo</label>
                    <input type="number" id="partidos_suspension" name="partidos_suspension" min="0" max="99" value="0" 
                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500 transition appearance-none"
                        style="-moz-appearance: textfield;">
                    <p class="text-[7px] text-amber-500 mt-1 italic font-medium">0 = Sin límite (manual)</p>
                </div>
            </div>

            <p id="avisoEquipoBloqueado" class="hidden text-[9px] text-amber-500 italic font-bold mt-1">
                ⚠️ El equipo está bloqueado porque el jugador ya tiene historial de partidos.
            </p>
            <div id="mensajeError" class="hidden text-red-500 text-[10px] bg-red-500/10 p-2 rounded border border-red-500/20 text-center uppercase font-bold"></div>
            <button type="submit" id="btnGuardar" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl shadow-lg transition">GUARDAR JUGADOR</button>
        </form>
    </div>
</div>

<div id="modalEquipo" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[110] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden max-h-[90vh]">
        <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-blue-600/10 shrink-0">
            <h3 id="tituloModalEquipo" class="text-lg font-bold text-white uppercase tracking-tighter">Gestión de Equipo</h3>
            <button onclick="cerrarModalEquipo()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form id="formRegistroEquipo" method="POST" enctype="multipart/form-data" class="p-4 space-y-3 text-sm overflow-y-auto max-h-[calc(90vh-60px)]" onsubmit="event.preventDefault(); registrarNuevoEquipo();">
            @csrf
            <input type="hidden" name="equipo_id_edit" id="equipo_id_edit">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Nombre</label>
                <input type="text" name="nombre" id="nombreEquipoInput" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500" oninput="validarFormularioEquipo()">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-2">Escudo</label>
                <div id="previewContenedor" class="hidden mb-2 p-2 bg-blue-600/10 border border-blue-500/30 rounded-lg flex items-center gap-2">
                    <img id="imgPreview" src="" class="size-10 object-contain rounded bg-white/10">
                    <p id="namePreview" class="text-white font-bold text-xs truncate"></p>
                </div>
                <div id="contenedorEscudos" class="grid grid-cols-4 gap-2 bg-slate-950/50 p-2 rounded-lg border border-slate-800 text-center text-white font-bold max-h-24 overflow-y-auto"></div>
                <div class="mt-2">
                    <label class="cursor-pointer flex items-center justify-center border-2 border-dashed border-slate-700 rounded-lg hover:border-blue-500 transition py-1">
                        <span class="text-[10px] text-blue-500 font-bold" id="fileName">+ SUBIR ESCUDO</span>
                        <input type="file" name="escudo_file" id="inputEscudo" class="hidden" accept="image/*" onchange="updateFileName(this)">
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Asignar Portero 🧤</label>
                <select name="portero_id" id="selectPortero" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-xs outline-none focus:border-blue-500">
                    <option value="">-- Seleccionar jugador --</option>
                </select>
            </div>
            <div id="equipoJugadoresInfo" class="hidden bg-amber-500/10 border border-amber-500/30 rounded-lg p-2">
                <div class="flex items-center gap-2">
                    <span class="text-amber-400 text-xs font-bold">⚠️ IMPORTANTE</span>
                </div>
                <p class="text-[10px] text-amber-300/70 mt-1">
                    Este equipo tiene <span id="eqJugadoresCount" class="font-bold">0</span>/11 jugadores. 
                    Asigna al menos 11 jugadores y define un portero antes de guardar.
                </p>
            </div>
            <button type="submit" id="btnGuardarEquipo" class="w-full bg-blue-600 font-bold py-3 rounded-lg transition uppercase text-xs shrink-0 mt-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Guardar Equipo</button>
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
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Sede / Cancha</label>
                <select name="campo_id" id="selectCampos" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                    <option value="">Cargando canchas...</option>
                </select>
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
        <div id="agendaCanchaContenedor" class="mt-4 hidden bg-slate-950/50 border border-slate-800 rounded-xl p-4 overflow-hidden">
            <div class="flex items-center gap-2 mb-3">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                <h4 class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Disponibilidad de la Sede</h4>
            </div>
            
            <div id="listaAgendaCancha" class="space-y-2 max-h-40 overflow-y-auto pr-2">
                </div>

            <div id="alertaConflicto" class="mt-3 hidden bg-red-500/10 border border-red-500/20 p-2 rounded-lg">
                <p class="text-[10px] text-red-400 font-bold text-center uppercase tracking-tighter">
                    ⚠️ Horario ocupado. El botón de registro se ha bloqueado.
                </p>
            </div>
        </div>
    
    </div>
</div>

<div id="modalVerJugadores" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[130] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden max-h-[80vh] flex flex-col">
        <div class="p-4 border-b border-slate-800 bg-emerald-600/10 flex justify-between items-center">
            <div>
                <h3 id="tituloVerJugadores" class="text-lg font-bold text-white uppercase tracking-tighter">Jugadores del Equipo</h3>
                <p id="subtituloVerJugadores" class="text-xs text-slate-400">Equipo</p>
            </div>
            <button onclick="cerrarModalVerJugadores()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
        </div>
        <div class="p-3 border-b border-slate-800 bg-slate-900/50">
            <div class="flex gap-2 items-center">
                <input type="text" id="filtroNombreJugador" placeholder="Buscar por nombre..." class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-white text-xs outline-none focus:border-emerald-500" onkeyup="filtrarJugadoresModal()">
                <input type="number" id="filtroDorsalJugador" placeholder="Dorsal" class="w-16 bg-slate-800 border border-slate-700 rounded-lg px-2 py-1.5 text-white text-xs outline-none focus:border-emerald-500" onkeyup="filtrarJugadoresModal()">
                <select id="filtroEstadoJugador" class="bg-slate-800 border border-slate-700 rounded-lg px-2 py-1.5 text-white text-xs outline-none focus:border-emerald-500" onchange="filtrarJugadoresModal()">
                    <option value="">Todos</option>
                    <option value="activo">🟢 Activo</option>
                    <option value="suspendido">🔴 Suspendido</option>
                    <option value="lesionado">🟡 Lesionado</option>
                </select>
                <button onclick="togglePanelAgregarJugador()" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap">
                    + Agregar
                </button>
            </div>
        </div>
        <div id="panelAgregarJugador" class="hidden p-3 bg-amber-500/10 border-b border-amber-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-amber-400">➕ JUGADORES DISPONIBLES</span>
                <button onclick="togglePanelAgregarJugador()" class="text-slate-500 hover:text-white text-xs">✕ Cerrar</button>
            </div>
            <div id="contenedorJugadoresDisponibles" class="max-h-32 overflow-y-auto space-y-1">
                <p class="text-slate-500 text-xs text-center py-2">Cargando...</p>
            </div>
        </div>
        <div id="contenedorVerJugadores" class="p-4 overflow-y-auto flex-1">
            <p class="text-slate-500 text-center p-4">Cargando jugadores...</p>
        </div>
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

            <div class="flex items-center justify-around gap-4 bg-slate-950/50 p-4 rounded-2xl border border-slate-800">
                <div class="text-center">
                    <label id="edit_labelLocal" class="block text-[10px] font-bold text-slate-500 uppercase mb-2">LOCAL</label>
                    <input type="number" id="goles_local" value="0" readonly class="size-16 bg-slate-900 border border-slate-700 rounded-xl text-center text-2xl font-black text-white outline-none">
                </div>
                <span class="text-slate-700 font-black text-2xl">VS</span>
                <div class="text-center">
                    <label id="edit_labelVisitante" class="block text-[10px] font-bold text-slate-500 uppercase mb-2">VISITANTE</label>
                    <input type="number" id="goles_visitante" value="0" readonly class="size-16 bg-slate-900 border border-slate-700 rounded-xl text-center text-2xl font-black text-white outline-none">
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cédula de Jugadores</h4>
                    <span class="text-[9px] text-slate-500 italic">Desmarca para inasistencia</span>
                </div>
                
                <div id="contenedorCedulaJugadores" class="space-y-6 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                    <p class="text-center text-slate-600 text-xs animate-pulse">Cargando listas de equipos...</p>
                </div>
            </div>

            <div class="bg-blue-900/20 p-3 rounded-lg border border-blue-500/30">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="confirmar_final" class="size-4 accent-blue-600">
                    <span class="text-[10px] text-blue-300 font-bold uppercase leading-tight">
                        Confirmar resultado final y cerrar acta (Bloquea edición y suma estadísticas)
                    </span>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button type="button" onclick="cerrarModalMarcador()" class="bg-slate-800 text-slate-400 font-bold py-3 rounded-xl text-xs uppercase">Cancelar</button>
                <button type="submit" class="bg-blue-600 text-white font-bold py-3 rounded-xl text-xs uppercase shadow-lg shadow-blue-900/20">Guardar Cambios</button>
            </div>

            <div class="pt-4 border-t border-slate-800">
                <button type="button" onclick="eliminarPartido()" class="w-full text-[10px] text-red-500 font-bold py-2 hover:bg-red-500/10 rounded-lg transition uppercase tracking-widest">
                    🗑️ Eliminar Partido
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modalDetallePartido" class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm hidden items-center justify-center z-[150] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div class="flex flex-col">
                <span id="det_fecha" class="text-[10px] text-slate-300 font-black uppercase tracking-widest"></span>
                <span id="det_rango_hora" class="text-[9px] text-slate-500 font-bold"></span>
            </div>
            <button onclick="cerrarDetalle()" class="text-slate-500 hover:text-white text-2xl">&times;</button>
        </div>

        <div class="overflow-y-auto custom-scrollbar p-6 space-y-6">
            <div class="grid grid-cols-3 items-center">
                <div class="text-center space-y-2">
                    <img id="det_escudo_local" src="" class="size-16 mx-auto object-contain drop-shadow-[0_0_10px_rgba(255,255,255,0.1)]">
                    <h3 id="det_nombre_local" class="text-xs font-black text-white uppercase tracking-tighter"></h3>
                </div>
                
                <div class="text-center">
                    <div class="flex items-center justify-center gap-3">
                        <span id="det_goles_local" class="text-5xl font-black text-white"></span>
                        <span class="text-slate-700 text-2xl font-light">-</span>
                        <span id="det_goles_visitante" class="text-5xl font-black text-white"></span>
                    </div>
                    <div class="mt-2">
                        <span id="det_estatus" class="text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-widest"></span>
                    </div>
                </div>

                <div class="text-center space-y-2">
                    <img id="det_escudo_visitante" src="" class="size-16 mx-auto object-contain drop-shadow-[0_0_10px_rgba(255,255,255,0.1)]">
                    <h3 id="det_nombre_visitante" class="text-xs font-black text-white uppercase tracking-tighter"></h3>
                </div>
            </div>

            <div class="bg-slate-950/30 rounded-2xl border border-slate-800/50 p-4">
                <p class="text-center text-[8px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Anotadores del encuentro</p>
                <div class="grid grid-cols-2 gap-4 relative">
                    <div class="absolute inset-y-0 left-1/2 w-px bg-slate-800/50"></div>
                    
                    <div id="lista_goleadores_local" class="space-y-3 pr-2"></div>
                    <div id="lista_goleadores_visitante" class="space-y-3 pl-2"></div>
                </div>
            </div>

            <div class="flex items-center gap-4 bg-blue-600/5 rounded-2xl p-4 border border-blue-500/20">
                <div class="size-10 rounded-xl bg-blue-600/10 flex items-center justify-center text-blue-500">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-width="2"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2"/></svg>
                </div>
                <div class="flex-1 text-left">
                    <p class="text-[8px] text-slate-500 uppercase font-black tracking-widest">Sede del encuentro</p>
                    <p id="det_cancha" class="text-xs text-white font-bold uppercase"></p>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="modalCrearCampo" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[140] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 bg-blue-600/10">
            <h3 class="text-lg font-bold text-white uppercase tracking-tighter">Registrar Sede</h3>
        </div>
        <form id="formCrearCampo" class="p-6 space-y-4">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Nombre</label>
                <input type="text" name="nombre" required placeholder="Ej. Campo Central" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Ubicación</label>
                <input type="text" name="lugar" required placeholder="Ej. Sector Norte" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
            </div>

            <div id="estadoCampoContainer" class="hidden">
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Estado de la Cancha</label>
                <select id="selectEstadoCampo" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500 text-xs">
                    <option value="disponible"> DISPONIBLE</option>
                    <option value="mantenimiento"> EN MANTENIMIENTO</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="cerrarModalCampo()" class="flex-1 bg-slate-800 text-slate-400 font-bold py-3 rounded-xl text-xs">CANCELAR</button>
                <button type="submit" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl text-xs uppercase">REGISTRAR</button>
            </div>
        </form>
    </div>
</div>

<div id="modalReasignarSede" class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm hidden items-center justify-center z-[200] p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 bg-slate-950/50">
            <h3 id="reasignarTitulo" class="text-white font-black uppercase tracking-tighter text-lg">⚠️ Reasignación Obligatoria</h3>
            <p id="reasignarDesc" class="text-slate-500 text-[10px] mt-1 italic">...</p>
        </div>
        
        <div class="p-6 space-y-4">
            <div class="space-y-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Partidos que deben moverse:</label>
                <div id="listaPartidosAfectados" class="max-h-32 overflow-y-auto bg-slate-950/50 rounded-xl p-3 border border-slate-800 space-y-2 custom-scrollbar">
                    </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nueva sede de destino:</label>
                <select id="selectNuevaSedeBorrado" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white outline-none focus:border-blue-500 text-sm italic">
                    </select>
            </div>
        </div>

        <div class="p-4 bg-slate-950/50 flex gap-3">
            <button onclick="window.cerrarModalReasignar()" class="flex-1 px-4 py-3 text-slate-400 font-bold text-[10px] uppercase hover:text-white transition">Cancelar</button>
            <button id="btnConfirmarBorradoEspecial" class="flex-1 font-bold text-[10px] py-3 rounded-xl uppercase transition shadow-lg"></button>
        </div>
    </div>
</div>

<div id="modalDetallesHistorial" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-black/90 backdrop-blur-md p-4">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-4xl max-h-[90vh] rounded-[40px] shadow-2xl overflow-hidden flex flex-col">
        
        <div class="p-8 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <div>
                <h2 id="historial-titulo" class="text-3xl font-black text-white uppercase italic tracking-tighter">Detalles del Torneo</h2>
                <p id="historial-subtitulo" class="text-amber-500 text-[10px] font-black uppercase tracking-[0.2em] mt-1"></p>
            </div>
            <button onclick="cerrarModalHistorial()" class="size-10 flex items-center justify-center rounded-full bg-slate-800 text-white hover:bg-red-600 transition">✕</button>
        </div>

        <div class="p-8 overflow-y-auto custom-scrollbar space-y-10">
            
            <div>
                <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="size-2 bg-amber-500 rounded-full"></span> Estadísticas Finales
                </h3>
                <div id="historial-stats" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    </div>
            </div>

            <div>
                <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="size-2 bg-blue-500 rounded-full"></span> Tabla de Posiciones al Cierre
                </h3>
                <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden">
                    <table class="w-full text-left text-[11px]">
                        <thead class="bg-slate-800/50 text-slate-400 uppercase font-black">
                            <tr>
                                <th class="px-4 py-3">Equipo</th>
                                <th class="px-4 py-3 text-center">PJ</th>
                                <th class="px-4 py-3 text-center">PTS</th>
                                <th class="px-4 py-3 text-center">GF</th>
                            </tr>
                        </thead>
                        <tbody id="historial-tabla-body" class="text-slate-300"></tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="size-2 bg-green-500 rounded-full"></span> Resumen de Partidos
                </h3>
                <div id="historial-partidos" class="grid grid-cols-1 gap-2">
                    </div>
            </div>
        </div>
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
            window.escudoSeleccionado = url;
            if (typeof equipoState !== 'undefined') {
                equipoState.escudoSeleccionado = url;
            }
        }
    }
    
    // Wrapper para el backup PDF - espera a que cargue el layout
    window.descargarBackupPDF = async function() {
        // Mostrar pantalla de carga
        if (window.mostrarCarga) window.mostrarCarga('Generando Backup PDF...');
        
        // Si no existen los datos, trying cargar desde el layout o desde API
        if (!window.datosTablaPosiciones || window.datosTablaPosiciones.length === 0) {
            // Intentar obtener datos directamente
            try {
                const resP = await fetch('/api/partidos');
                const resE = await fetch('/api/equipos');
                const partidos = await resP.json();
                const equipos = await resE.json();
                
                // Calcular stats
                let stats = {};
                Object.values(equipos || {}).forEach(eq => {
                    stats[eq.nombre] = { nombre: eq.nombre, pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0 };
                });
                
                Object.values(partidos || {}).forEach(p => {
                    if (p.resultado_confirmado && !isNaN(p.jornada)) {
                        const gl = parseInt(p.goles_local || 0), gv = parseInt(p.goles_visitante || 0);
                        if (stats[p.equipo_local] && stats[p.equipo_visitante]) {
                            stats[p.equipo_local].pj++; stats[p.equipo_visitante].pj++;
                            stats[p.equipo_local].gf += gl; stats[p.equipo_local].gc += gv;
                            stats[p.equipo_visitante].gf += gv; stats[p.equipo_visitante].gc += gl;
                            if (gl > gv) { stats[p.equipo_local].g++; stats[p.equipo_local].pts += 3; stats[p.equipo_visitante].p++; }
                            else if (gl < gv) { stats[p.equipo_visitante].g++; stats[p.equipo_visitante].pts += 3; stats[p.equipo_local].p++; }
                            else { stats[p.equipo_local].e++; stats[p.equipo_visitante].e++; stats[p.equipo_local].pts++; stats[p.equipo_visitante].pts++; }
                        }
                    }
                });
                
                window.datosTablaPosiciones = Object.values(stats).sort((a,b) => b.pts - a.pts);
                window.cacheEquiposData = equipos;
                window.cachePartidosData = partidos;
            } catch(e) { console.error('Error:', e); }
        }
        
        const datos = window.datosTablaPosiciones;
        const equipos = window.cacheEquiposData || {};
        const partidos = window.cachePartidosData || {};
        
        const porterosData = {};
        Object.values(equipos || {}).forEach(eq => {
            if (eq.portero_id) {
                // Guardar ID para luego resolver a nombre
                porterosData[eq.nombre] = { id: eq.portero_id, nombre: eq.portero_nombre || '' };
            }
        });
        
        let jugadoresData = {};
        try {
            const resJ = await fetch('/api/jugadores');
            jugadoresData = await resJ.json();
        } catch(e) {}
        
        // Resolver nombres de porteros usando jugadoresData
        Object.keys(porterosData).forEach(eqNombre => {
            const portero = porterosData[eqNombre];
            if (portero.id && jugadoresData[portero.id]) {
                portero.nombre = jugadoresData[portero.id].nombre || portero.id;
            } else if (!portero.nombre) {
                portero.nombre = portero.id; // Fallback al ID si no hay nombre
            }
        });
        
        const fecha = new Date().toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
        
        // Agrupar partidos por jornada
        const jornadas = {};
        Object.values(partidos || {}).forEach(p => {
            if (!p.jornada) return;
            if (!jornadas[p.jornada]) jornadas[p.jornada] = [];
            jornadas[p.jornada].push(p);
        });
        
        // Partidos próximos (sin resultado)
        const proximos = Object.values(partidos || {}).filter(p => !p.resultado_confirmado && p.jornada && p.fecha);
        
        let html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Resumen Gol Center - ${fecha}</title>
            <style>
                @page { size: A4; margin: 1cm; }
                body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
                h1 { font-size: 20px; text-align: center; margin-bottom: 5px; }
                h2 { font-size: 14px; margin-top: 20px; border-bottom: 1px solid #000; padding-bottom: 5px; }
                h3 { font-size: 12px; margin-top: 15px; color: #333; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
                th, td { border: 1px solid #000; padding: 4px 8px; text-align: center; }
                th { background: #eee; }
                .text-left { text-align: left; }
                .text-right { text-align: right; }
                .page-break { page-break-after: always; }
                .score { font-weight: bold; font-size: 12px; }
                
                @media print {
                    body > * { display: none !important; }
                    #print-area-welcome { display: block !important; position: static !important; left: 0 !important; }
                }
            </style>
        </head>
        <body>
            <div id="print-area-welcome">
            <h1>⚽ GOL CENTER - RESUMEN</h1>
            <p style="text-align:center; margin-bottom: 20px;">Fecha: ${fecha}</p>
            
            <h2>📊 TABLA DE POSICIONES</h2>
            <table>
                <tr><th>#</th><th class="text-left">Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>PTS</th><th>GF</th><th>GC</th><th>DG</th></tr>
                ${(datos || []).map((t, i) => `<tr>
                    <td>${i+1}</td>
                    <td class="text-left">${t.nombre}</td>
                    <td>${t.pj}</td>
                    <td>${t.g}</td>
                    <td>${t.e}</td>
                    <td>${t.p}</td>
                    <td><b>${t.pts}</b></td>
                    <td>${t.gf}</td>
                    <td>${t.gc}</td>
                    <td>${t.gf - t.gc >= 0 ? '+'+(t.gf - t.gc) : t.gf - t.gc}</td>
                </tr>`).join('')}
            </table>
            
            <div class="page-break"></div>
            
            <h2>⚽ PARTIDOS JUGADOS</h2>`;
            
        // Agregar cada jornada
        Object.keys(jornadas).sort((a,b) => parseInt(a) - parseInt(b)).forEach(j => {
            const partidosJornada = jornadas[j].filter(p => p.resultado_confirmado);
            if (partidosJornada.length === 0) return;
            html += `
            <h3>Jornada ${j}</h3>
            <table>
                <tr><th class="text-left">Local</th><th>Score</th><th class="text-left">Visitante</th><th>Fecha</th></tr>
                ${partidosJornada.map(p => `<tr>
                    <td class="text-left">${p.equipo_local || '-'}</td>
                    <td class="score">${p.goles_local} - ${p.goles_visitante}</td>
                    <td class="text-left">${p.equipo_visitante || '-'}</td>
                    <td>${p.fecha || '-'}</td>
                </tr>`).join('')}
            </table>`;
        });
        
        // Calcular estadísticas por jornada
        const statsJornada = {};
        
        Object.values(partidos || {}).forEach(p => {
            if (!p.jornada || !p.resultado_confirmado) return;
            const jornadaNum = parseInt(p.jornada);
            if (isNaN(jornadaNum)) return;
            
            if (!statsJornada[p.jornada]) {
                statsJornada[p.jornada] = {
                    goleadores: {},
                    porteros: {},
                    equipos: {}
                };
            }
            
            // Usar detalle_jugadores para obtener goleadores
            if (p.detalle_jugadores) {
                Object.entries(p.detalle_jugadores).forEach(([tel, stats]) => {
                    if (stats.asistio && stats.goles > 0) {
                        const nombre = jugadoresData[tel]?.nombre || tel;
                        const equipo = jugadoresData[tel]?.equipo || '';
                        if (!statsJornada[p.jornada].goleadores[nombre]) {
                            statsJornada[p.jornada].goleadores[nombre] = { goles: 0, equipo: equipo };
                        }
                        statsJornada[p.jornada].goleadores[nombre].goles += parseInt(stats.goles || 0);
                    }
                });
            }
            
            const local = p.equipo_local;
            const visitante = p.equipo_visitante;
            if (local) {
                if (!statsJornada[p.jornada].equipos[local]) {
                    statsJornada[p.jornada].equipos[local] = { gf: 0, gc: 0 };
                }
                statsJornada[p.jornada].equipos[local].gf += parseInt(p.goles_local || 0);
                statsJornada[p.jornada].equipos[local].gc += parseInt(p.goles_visitante || 0);
            }
            if (visitante) {
                if (!statsJornada[p.jornada].equipos[visitante]) {
                    statsJornada[p.jornada].equipos[visitante] = { gf: 0, gc: 0 };
                }
                statsJornada[p.jornada].equipos[visitante].gf += parseInt(p.goles_visitante || 0);
                statsJornada[p.jornada].equipos[visitante].gc += parseInt(p.goles_local || 0);
            }
            
            // Atribuir GC a porteros
            Object.entries(statsJornada[p.jornada].equipos).forEach(([eqNama, eqStats]) => {
                const portero = porterosData[eqNama];
                if (portero && eqStats.gc > 0) {
                    if (!statsJornada[p.jornada].porteros[portero.nombre]) {
                        statsJornada[p.jornada].porteros[portero.nombre] = { gc: 0, equipo: eqNama };
                    }
                    statsJornada[p.jornada].porteros[portero.nombre].gc += eqStats.gc;
                }
            });
        });
        
        html += `
            <div class="page-break"></div>
            
            <h2>📊 ESTADÍSTICAS POR JORNADA</h2>`;
            
        Object.keys(jornadas).sort((a,b) => parseInt(a) - parseInt(b)).forEach(j => {
            const stats = statsJornada[j];
            if (!stats) return;
            
            // Top goleadores - incluir todos los empatados en las posiciones 1, 2, 3
            const goleadoresOrdenados = Object.entries(stats.goleadores || {})
                .sort((a, b) => b[1].goles - a[1].goles);
            
            let topGoleadores = [];
            if (goleadoresOrdenados.length > 0) {
                const maxGoles = goleadoresOrdenados[0][1].goles;
                topGoleadores = goleadoresOrdenados.filter(g => g[1].goles === maxGoles);
                
                if (goleadoresOrdenados.length > 1) {
                    const segundoMax = goleadoresOrdenados[1][1].goles;
                    if (segundoMax > 0 && segundoMax < maxGoles) {
                        goleadoresOrdenados.slice(1).filter(g => g[1].goles === segundoMax).forEach(g => topGoleadores.push(g));
                    }
                }
            }
            
            // Equipo más goleador - incluir todos los que igualen el máximo GF
            const equiposArr = Object.entries(stats.equipos || {});
            const maxGF = Math.max(...equiposArr.map(e => e[1].gf));
            const equiposMasGoleadores = equiposArr.filter(e => e[1].gf === maxGF);
            
            // Equipo menos goleado - incluir todos los que igualen el mínimo GC
            const minGC = Math.min(...equiposArr.map(e => e[1].gc));
            const equiposMenosGoleados = equiposArr.filter(e => e[1].gc === minGC);
            
            // Top porteros - menos GC (Guante de Oro)
            const porterosOrdenados = Object.entries(stats.porteros || {})
                .sort((a, b) => a[1].gc - b[1].gc);
            
            let topPorteros = [];
            if (porterosOrdenados.length > 0) {
                const minGCPortero = porterosOrdenados[0][1].gc;
                topPorteros = porterosOrdenados.filter(p => p[1].gc === minGCPortero);
                
                if (porterosOrdenados.length > 1) {
                    const segundoMinGC = porterosOrdenados[1][1].gc;
                    if (segundoMinGC > minGCPortero) {
                        porterosOrdenados.slice(1).filter(p => p[1].gc === segundoMinGC).forEach(p => topPorteros.push(p));
                    }
                }
            }
            
            html += `
            <h3>Jornada ${j}</h3>
            <table>
                <tr><th colspan="3" style="background:#e0e7ff">🏆 Top Goleadores</th></tr>
                <tr><th class="text-left">Jugador</th><th class="text-left">Equipo</th><th>Goles</th></tr>
                ${topGoleadores.length > 0 ? topGoleadores.map(([nombre, data]) => `<tr><td class="text-left">${nombre}</td><td class="text-left">${data.equipo || '-'}</td><td><b>${data.goles}</b></td></tr>`).join('') : '<tr><td colspan="3" class="text-left">Sin datos</td></tr>'}
            </table>
            <table>
                <tr><th colspan="3" style="background:#d1fae5">🧤 Porteros Menos Goleados</th></tr>
                <tr><th class="text-left">Portero</th><th class="text-left">Equipo</th><th>GC</th></tr>
                ${topPorteros.length > 0 ? topPorteros.map(([nombre, data]) => `<tr><td class="text-left">${nombre}</td><td class="text-left">${data.equipo || '-'}</td><td><b>${data.gc}</b></td></tr>`).join('') : '<tr><td colspan="3" class="text-left">Sin datos</td></tr>'}
            </table>
            <table>
                <tr>
                    <th style="background:#dcfce7">🔥 Más Goleador (GF: ${maxGF})</th>
                    <th style="background:#fee2e2">🛡️ Menos Goleado (GC: ${minGC})</th>
                </tr>
                <tr>
                    <td class="text-left">${equiposMasGoleadores.map(e => e[0]).join(', ') || '-'}</td>
                    <td class="text-left">${equiposMenosGoleados.map(e => e[0]).join(', ') || '-'}</td>
                </tr>
            </table>`;
        });
        
        html += `
            <div class="page-break"></div>
            
            <h2>📅 PRÓXIMOS PARTIDOS</h2>`;
            
        if (proximos.length > 0) {
            html += `<table>
                <tr><th>Jornada</th><th class="text-left">Local</th><th class="text-left">Visitante</th><th>Fecha</th><th>Hora</th></tr>
                ${proximos.sort((a,b) => parseInt(a.jornada) - parseInt(b.jornada)).map(p => `<tr>
                    <td>${p.jornada}</td>
                    <td class="text-left">${p.equipo_local || '-'}</td>
                    <td class="text-left">${p.equipo_visitante || '-'}</td>
                    <td>${p.fecha || '-'}</td>
                    <td>${p.hora || '-'}</td>
                </tr>`).join('')}
            </table>`;
        } else {
            html += `<p>No hay partidos programados</p>`;
        }
        
        html += `
            <p style="margin-top:20px; font-size:8px; text-align:center">Generado por Gol Center - ${new Date().toISOString()}</p>
            </div>
        </body>
        </html>`;
        
        // Crear contenido de impresión en la misma página
        const printDiv = document.createElement('div');
        printDiv.id = 'print-area-welcome';
        printDiv.innerHTML = html;
        printDiv.style.cssText = 'position:absolute; left:-9999px; top:0; width:100%;';
        document.body.appendChild(printDiv);
        
        // Ocultar pantalla de carga
        if (window.ocultarCarga) window.ocultarCarga();
        
        // Imprimir y luego limpiar
        window.print();
        setTimeout(() => printDiv.remove(), 1000);
    };
</script>
@endsection